<?php
/**
 * 积分与会员公共能力
 */
require_once __DIR__ . '/db.php';

function points_get_pricing_config(): array {
    return [
        'packages' => [
            ['id' => 'pkg_9_9', 'price' => 9.9, 'points' => 165, 'name' => '9.9元积分包'],
            ['id' => 'pkg_19_9', 'price' => 19.9, 'points' => 335, 'name' => '19.9元积分包'],
            ['id' => 'pkg_29_9', 'price' => 29.9, 'points' => 505, 'name' => '29.9元积分包'],
        ],
        'memberships' => [
            ['id' => 'member_first_month', 'price' => 29.9, 'days' => 30, 'name' => '首月会员'],
            ['id' => 'member_renew_month', 'price' => 39.9, 'days' => 30, 'name' => '连续续费月会员'],
            ['id' => 'member_single_month', 'price' => 49.9, 'days' => 30, 'name' => '单月会员'],
            ['id' => 'member_year', 'price' => 299.0, 'days' => 365, 'name' => '年会员'],
        ],
        'daily_member_bonus' => 16,
        'image_cost_points' => [
            // 按目标利润率约 100% 设计，且 4K 相比 2K +80%
            'banana' => ['2k' => 5, '4k' => 9],
            'banana_pro' => ['2k' => 10, '4k' => 18],
        ],
        'video_cost_points' => [
            // 豆包视频（默认有声）：16元/百万tokens，100%利润率
            // 假设5秒≈10万tokens → 成本1.6元 → 收费3.2元 ≈ 53分；取55分便于计算
            // 10秒按2倍；1元≈16.67分
            'doubao_video' => [
                'points_per_5s' => 55,
            ],
        ],
        'profit_target' => [
            'target' => '~100%',
            'note' => 'banana_pro 2K固定10分，按成本0.3测算约100%利润；4K比2K多80%',
        ],
    ];
}

function points_normalize_model(string $model): string {
    $m = strtolower(trim($model));
    if (in_array($m, ['banana pro', 'banana-pro', 'banana_pro'], true)) {
        return 'banana_pro';
    }
    if ($m === 'banana') {
        return 'banana';
    }
    return $m;
}

function points_normalize_quality(string $quality): string {
    $q = strtolower(trim($quality));
    return $q === '4k' ? '4k' : '2k';
}

function points_calculate_image_points(string $model, string $quality): int {
    $cfg = points_get_pricing_config();
    $modelKey = points_normalize_model($model);
    $qualityKey = points_normalize_quality($quality);
    if (!isset($cfg['image_cost_points'][$modelKey])) {
        $modelKey = 'banana';
    }
    return (int)$cfg['image_cost_points'][$modelKey][$qualityKey];
}

function points_calculate_video_points(string $model, int $duration = 5): int {
    $cfg = points_get_pricing_config();
    $modelKey = 'doubao_video';

    $duration = max(1, min(30, $duration));
    $base = (int)($cfg['video_cost_points'][$modelKey]['points_per_5s'] ?? 55);

    // 5 秒为基准，按时长线性放大，最小 1 分
    return max(1, (int)ceil($base * ($duration / 5)));
}

function points_current_cycle_key(?DateTimeImmutable $now = null): string {
    $tz = new DateTimeZone('Asia/Shanghai');
    $now = $now ?: new DateTimeImmutable('now', $tz);
    $hour = (int)$now->format('G');
    $date = $hour >= 12 ? $now : $now->sub(new DateInterval('P1D'));
    return $date->format('Y-m-d');
}

function points_cycle_end_time(?DateTimeImmutable $now = null): string {
    $tz = new DateTimeZone('Asia/Shanghai');
    $now = $now ?: new DateTimeImmutable('now', $tz);
    $todayNoon = $now->setTime(12, 0, 0);
    $nextNoon = $now < $todayNoon ? $todayNoon : $todayNoon->add(new DateInterval('P1D'));
    return $nextNoon->format('Y-m-d H:i:s');
}

function points_is_membership_active(PDO $pdo, int $userId): bool {
    $stmt = $pdo->prepare("
        SELECT 1
        FROM user_memberships
        WHERE user_id = :user_id
          AND status = 'active'
          AND expires_at > CURRENT_TIMESTAMP
        LIMIT 1
    ");
    $stmt->execute(['user_id' => $userId]);
    return (bool)$stmt->fetchColumn();
}

function points_ensure_wallet(PDO $pdo, int $userId): array {
    $stmt = $pdo->prepare("
        INSERT INTO user_wallets (user_id, paid_balance, bonus_balance, bonus_cycle_key)
        VALUES (:user_id, 0, 0, NULL)
        ON CONFLICT (user_id) DO NOTHING
    ");
    $stmt->execute(['user_id' => $userId]);

    $walletStmt = $pdo->prepare("
        SELECT user_id, paid_balance, bonus_balance, bonus_cycle_key
        FROM user_wallets
        WHERE user_id = :user_id
        FOR UPDATE
    ");
    $walletStmt->execute(['user_id' => $userId]);
    $wallet = $walletStmt->fetch(PDO::FETCH_ASSOC);
    if (!$wallet) {
        throw new RuntimeException('wallet not found');
    }

    $isActiveMember = points_is_membership_active($pdo, $userId);
    $cfg = points_get_pricing_config();
    $dailyBonus = (int)$cfg['daily_member_bonus'];
    $cycleKey = points_current_cycle_key();

    if ($isActiveMember) {
        if (($wallet['bonus_cycle_key'] ?? null) !== $cycleKey) {
            $upd = $pdo->prepare("
                UPDATE user_wallets
                SET bonus_balance = :bonus_balance,
                    bonus_cycle_key = :cycle_key,
                    updated_at = CURRENT_TIMESTAMP
                WHERE user_id = :user_id
            ");
            $upd->execute([
                'bonus_balance' => $dailyBonus,
                'cycle_key' => $cycleKey,
                'user_id' => $userId,
            ]);
            $wallet['bonus_balance'] = $dailyBonus;
            $wallet['bonus_cycle_key'] = $cycleKey;
        }
    } else {
        if ((int)$wallet['bonus_balance'] !== 0 || !empty($wallet['bonus_cycle_key'])) {
            $upd = $pdo->prepare("
                UPDATE user_wallets
                SET bonus_balance = 0, bonus_cycle_key = NULL, updated_at = CURRENT_TIMESTAMP
                WHERE user_id = :user_id
            ");
            $upd->execute(['user_id' => $userId]);
            $wallet['bonus_balance'] = 0;
            $wallet['bonus_cycle_key'] = null;
        }
    }

    return [
        'user_id' => (int)$wallet['user_id'],
        'paid_balance' => (int)$wallet['paid_balance'],
        'bonus_balance' => (int)$wallet['bonus_balance'],
        'bonus_cycle_key' => $wallet['bonus_cycle_key'],
    ];
}

function points_get_wallet_summary(int $userId): array {
    $pdo = get_db();
    $pdo->beginTransaction();
    try {
        $wallet = points_ensure_wallet($pdo, $userId);
        $membershipStmt = $pdo->prepare("
            SELECT plan_code, daily_bonus_points, started_at, expires_at, status
            FROM user_memberships
            WHERE user_id = :user_id
            LIMIT 1
        ");
        $membershipStmt->execute(['user_id' => $userId]);
        $membership = $membershipStmt->fetch(PDO::FETCH_ASSOC) ?: null;
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    return [
        'paidBalance' => $wallet['paid_balance'],
        'bonusBalance' => $wallet['bonus_balance'],
        'totalBalance' => $wallet['paid_balance'] + $wallet['bonus_balance'],
        'bonusExpireAt' => points_cycle_end_time(),
        'membership' => $membership ? [
            'planCode' => $membership['plan_code'],
            'dailyBonusPoints' => (int)$membership['daily_bonus_points'],
            'startedAt' => $membership['started_at'],
            'expiresAt' => $membership['expires_at'],
            'status' => $membership['status'],
        ] : null,
    ];
}

function points_write_ledger(PDO $pdo, int $userId, int $changeAmount, int $balanceAfter, string $source, string $description, array $meta = []): void {
    $stmt = $pdo->prepare("
        INSERT INTO points_ledger (user_id, change_amount, balance_after, source, description, meta_json)
        VALUES (:user_id, :change_amount, :balance_after, :source, :description, :meta_json)
    ");
    $stmt->execute([
        'user_id' => $userId,
        'change_amount' => $changeAmount,
        'balance_after' => $balanceAfter,
        'source' => $source,
        'description' => $description,
        'meta_json' => json_encode($meta, JSON_UNESCAPED_UNICODE),
    ]);
}

function points_consume(int $userId, int $needPoints, string $source, string $description, array $meta = []): array {
    if ($needPoints <= 0) {
        return ['success' => true, 'paidUsed' => 0, 'bonusUsed' => 0];
    }

    $pdo = get_db();
    $pdo->beginTransaction();
    try {
        $wallet = points_ensure_wallet($pdo, $userId);
        $total = $wallet['paid_balance'] + $wallet['bonus_balance'];
        if ($total < $needPoints) {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => "积分不足：当前{$total}，需要{$needPoints}",
                'current' => $total,
                'required' => $needPoints,
            ];
        }

        $bonusUsed = min($wallet['bonus_balance'], $needPoints);
        $paidUsed = $needPoints - $bonusUsed;
        $newBonus = $wallet['bonus_balance'] - $bonusUsed;
        $newPaid = $wallet['paid_balance'] - $paidUsed;
        $newTotal = $newBonus + $newPaid;

        $upd = $pdo->prepare("
            UPDATE user_wallets
            SET paid_balance = :paid_balance,
                bonus_balance = :bonus_balance,
                updated_at = CURRENT_TIMESTAMP
            WHERE user_id = :user_id
        ");
        $upd->execute([
            'paid_balance' => $newPaid,
            'bonus_balance' => $newBonus,
            'user_id' => $userId,
        ]);

        $meta['paidUsed'] = $paidUsed;
        $meta['bonusUsed'] = $bonusUsed;
        points_write_ledger($pdo, $userId, -$needPoints, $newTotal, $source, $description, $meta);
        $pdo->commit();

        return [
            'success' => true,
            'paidUsed' => $paidUsed,
            'bonusUsed' => $bonusUsed,
            'balanceAfter' => $newTotal,
        ];
    } catch (Throwable $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => '扣积分失败：' . $e->getMessage()];
    }
}

function points_refund_to_paid(int $userId, int $points, string $source, string $description, array $meta = []): bool {
    if ($points <= 0) return true;
    $pdo = get_db();
    $pdo->beginTransaction();
    try {
        $wallet = points_ensure_wallet($pdo, $userId);
        $newPaid = $wallet['paid_balance'] + $points;
        $newBonus = $wallet['bonus_balance'];
        $newTotal = $newPaid + $newBonus;

        $upd = $pdo->prepare("
            UPDATE user_wallets
            SET paid_balance = :paid_balance,
                updated_at = CURRENT_TIMESTAMP
            WHERE user_id = :user_id
        ");
        $upd->execute([
            'paid_balance' => $newPaid,
            'user_id' => $userId,
        ]);

        points_write_ledger($pdo, $userId, $points, $newTotal, $source, $description, $meta);
        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        $pdo->rollBack();
        return false;
    }
}

function points_add_paid(int $userId, int $points, string $source, string $description, array $meta = []): bool {
    if ($points <= 0) return false;
    $pdo = get_db();
    $pdo->beginTransaction();
    try {
        $wallet = points_ensure_wallet($pdo, $userId);
        $newPaid = $wallet['paid_balance'] + $points;
        $newTotal = $newPaid + $wallet['bonus_balance'];

        $upd = $pdo->prepare("
            UPDATE user_wallets
            SET paid_balance = :paid_balance,
                updated_at = CURRENT_TIMESTAMP
            WHERE user_id = :user_id
        ");
        $upd->execute([
            'paid_balance' => $newPaid,
            'user_id' => $userId,
        ]);
        points_write_ledger($pdo, $userId, $points, $newTotal, $source, $description, $meta);
        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        $pdo->rollBack();
        return false;
    }
}

function points_admin_adjust_paid(int $userId, int $delta, string $description, array $meta = []): array {
    if ($delta === 0) {
        return ['success' => false, 'message' => '调整值不能为0'];
    }

    $pdo = get_db();
    $pdo->beginTransaction();
    try {
        $wallet = points_ensure_wallet($pdo, $userId);
        $newPaid = (int)$wallet['paid_balance'] + $delta;
        if ($newPaid < 0) {
            $pdo->rollBack();
            return ['success' => false, 'message' => '付费积分不足，无法扣减'];
        }

        $newBonus = (int)$wallet['bonus_balance'];
        $newTotal = $newPaid + $newBonus;

        $upd = $pdo->prepare("
            UPDATE user_wallets
            SET paid_balance = :paid_balance,
                updated_at = CURRENT_TIMESTAMP
            WHERE user_id = :user_id
        ");
        $upd->execute([
            'paid_balance' => $newPaid,
            'user_id' => $userId,
        ]);

        points_write_ledger($pdo, $userId, $delta, $newTotal, 'admin_adjust', $description, $meta);
        $pdo->commit();

        return [
            'success' => true,
            'wallet' => [
                'paidBalance' => $newPaid,
                'bonusBalance' => $newBonus,
                'totalBalance' => $newTotal,
            ],
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return ['success' => false, 'message' => '管理员积分调整失败：' . $e->getMessage()];
    }
}

function points_subscribe_membership(int $userId, string $planId): array {
    $cfg = points_get_pricing_config();
    $plan = null;
    foreach ($cfg['memberships'] as $item) {
        if ($item['id'] === $planId) {
            $plan = $item;
            break;
        }
    }
    if (!$plan) {
        return ['success' => false, 'message' => '无效的会员套餐'];
    }

    $pdo = get_db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("SELECT user_id, expires_at FROM user_memberships WHERE user_id = :user_id FOR UPDATE");
        $stmt->execute(['user_id' => $userId]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);

        $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Shanghai'));
        $startAt = $now;
        if ($current && !empty($current['expires_at'])) {
            $exp = new DateTimeImmutable($current['expires_at'], new DateTimeZone('Asia/Shanghai'));
            if ($exp > $now) {
                $startAt = $exp;
            }
        }
        $expiresAt = $startAt->add(new DateInterval('P' . ((int)$plan['days']) . 'D'));

        if ($current) {
            $upd = $pdo->prepare("
                UPDATE user_memberships
                SET plan_code = :plan_code,
                    daily_bonus_points = :daily_bonus_points,
                    started_at = :started_at,
                    expires_at = :expires_at,
                    status = 'active',
                    updated_at = CURRENT_TIMESTAMP
                WHERE user_id = :user_id
            ");
            $upd->execute([
                'plan_code' => $plan['id'],
                'daily_bonus_points' => (int)$cfg['daily_member_bonus'],
                'started_at' => $now->format('Y-m-d H:i:s'),
                'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
                'user_id' => $userId,
            ]);
        } else {
            $ins = $pdo->prepare("
                INSERT INTO user_memberships (user_id, plan_code, daily_bonus_points, started_at, expires_at, status)
                VALUES (:user_id, :plan_code, :daily_bonus_points, :started_at, :expires_at, 'active')
            ");
            $ins->execute([
                'user_id' => $userId,
                'plan_code' => $plan['id'],
                'daily_bonus_points' => (int)$cfg['daily_member_bonus'],
                'started_at' => $now->format('Y-m-d H:i:s'),
                'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
            ]);
        }

        $wallet = points_ensure_wallet($pdo, $userId);
        $totalBalance = (int)$wallet['paid_balance'] + (int)$wallet['bonus_balance'];

        points_write_ledger($pdo, $userId, 0, $totalBalance, 'membership_purchase', '购买会员：' . $plan['name'], [
            'planId' => $plan['id'],
            'price' => $plan['price'],
            'days' => $plan['days'],
        ]);
        $pdo->commit();

        return [
            'success' => true,
            'plan' => $plan,
            'wallet' => [
                'paidBalance' => $wallet['paid_balance'],
                'bonusBalance' => $wallet['bonus_balance'],
                'totalBalance' => $wallet['paid_balance'] + $wallet['bonus_balance'],
                'bonusExpireAt' => points_cycle_end_time(),
            ],
            'expiresAt' => $expiresAt->format('Y-m-d H:i:s'),
        ];
    } catch (Throwable $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => '开通会员失败：' . $e->getMessage()];
    }
}

