<?php
/**
 * 每日签到积分清理任务（建议通过 crontab 在每天 12:00 执行）
 *
 * 用法:
 *   php api/cron/daily_bonus.php
 */
require_once __DIR__ . '/../common/db.php';
require_once __DIR__ . '/../common/points.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "forbidden\n";
    exit(1);
}

$cfg = points_get_pricing_config();
$cycleKey = points_current_cycle_key();

try {
    $pdo = get_db();
    $pdo->beginTransaction();

    // 确保每个用户都有钱包记录
    $insert = $pdo->exec("
        INSERT INTO user_wallets (user_id, paid_balance, bonus_balance, bonus_cycle_key)
        SELECT id, 0, 0, NULL
        FROM users
        ON CONFLICT (user_id) DO NOTHING
    ");

    // 清理过期签到积分（当天未用，次日清零）
    $upd = $pdo->prepare("
        UPDATE user_wallets
        SET bonus_balance = 0,
            bonus_cycle_key = NULL,
            updated_at = CURRENT_TIMESTAMP
        WHERE bonus_cycle_key IS NOT NULL
          AND bonus_cycle_key <> :cycle_key
    ");
    $upd->execute([
        'cycle_key' => $cycleKey,
    ]);

    $updatedRows = $upd->rowCount();
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'cycleKey' => $cycleKey,
        'insertedWallets' => (int)$insert,
        'clearedWallets' => $updatedRows,
        'at' => date('Y-m-d H:i:s'),
    ], JSON_UNESCAPED_UNICODE) . PHP_EOL;
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "daily bonus cron failed: " . $e->getMessage() . PHP_EOL);
    exit(1);
}
