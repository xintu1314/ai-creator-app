<?php
/**
 * POST /api/auth/send_code.php
 * 发送手机验证码（支持 purpose: login | register）
 */
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/response.php';
require_once __DIR__ . '/../common/db.php';
require_once __DIR__ . '/../common/sms.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
    exit;
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?? [];
$phone = sms_normalize_phone((string)($input['phone'] ?? ''));
$purpose = in_array((string)($input['purpose'] ?? 'login'), ['login', 'register'], true)
    ? (string)$input['purpose']
    : 'login';

if (!sms_is_valid_phone($phone)) {
    json_error('请输入正确的11位手机号');
    exit;
}

if ($purpose === 'register') {
    $pdo = get_db();
    $check = $pdo->prepare("SELECT id FROM users WHERE phone = :phone LIMIT 1");
    $check->execute(['phone' => $phone]);
    if ($check->fetch(PDO::FETCH_ASSOC)) {
        json_error('该手机号已注册，请直接登录');
        exit;
    }
}

$cfg = sms_config();
$ttl = max(60, (int)($cfg['code_ttl_seconds'] ?? 300));
$cooldown = max(30, (int)($cfg['send_cooldown_seconds'] ?? 60));
$dailyLimit = max(1, (int)($cfg['daily_limit_per_phone'] ?? 20));
$ipLimitPerMinute = max(1, (int)($cfg['ip_limit_per_minute'] ?? 5));
$ipLimitPerHour = max(1, (int)($cfg['ip_limit_per_hour'] ?? 30));
$ipLimitPerDay = max(1, (int)($cfg['ip_limit_per_day'] ?? 200));
$debugReturnCode = !app_is_production() && !empty($cfg['debug_return_code']);
$ip = substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 64);

try {
    $pdo = get_db();
    $pdo->beginTransaction();

    // 发送冷却检查
    $latestStmt = $pdo->prepare("
        SELECT GREATEST(0, FLOOR(EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - created_at))))::INT AS passed_seconds
        FROM sms_verification_codes
        WHERE phone = :phone
          AND purpose = :purpose
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $latestStmt->execute([
        'phone' => $phone,
        'purpose' => $purpose,
    ]);
    $latest = $latestStmt->fetch(PDO::FETCH_ASSOC);
    if ($latest) {
        $sec = (int)($latest['passed_seconds'] ?? 0);
        if ($sec < $cooldown) {
            $pdo->rollBack();
            json_error('发送过于频繁，请' . ($cooldown - $sec) . '秒后重试');
            exit;
        }
    }

    // 每日限制（手机号）
    $todayStart = (new DateTimeImmutable('today', new DateTimeZone('Asia/Shanghai')))->format('Y-m-d H:i:s');
    $cntStmt = $pdo->prepare("
        SELECT COUNT(*) AS c
        FROM sms_verification_codes
        WHERE phone = :phone
          AND purpose = :purpose
          AND created_at >= :today_start
    ");
    $cntStmt->execute([
        'phone' => $phone,
        'purpose' => $purpose,
        'today_start' => $todayStart,
    ]);
    $todayCount = (int)$cntStmt->fetchColumn();
    if ($todayCount >= $dailyLimit) {
        $pdo->rollBack();
        json_error('今日验证码发送次数已达上限');
        exit;
    }

    // 频控（IP）
    if ($ip !== '') {
        $ipMinuteStmt = $pdo->prepare("
            SELECT COUNT(*) AS c
            FROM sms_verification_codes
            WHERE ip = :ip
              AND created_at >= (CURRENT_TIMESTAMP - INTERVAL '1 minute')
        ");
        $ipMinuteStmt->execute(['ip' => $ip]);
        if ((int)$ipMinuteStmt->fetchColumn() >= $ipLimitPerMinute) {
            $pdo->rollBack();
            json_error('请求过于频繁，请稍后重试');
            exit;
        }

        $ipHourStmt = $pdo->prepare("
            SELECT COUNT(*) AS c
            FROM sms_verification_codes
            WHERE ip = :ip
              AND created_at >= (CURRENT_TIMESTAMP - INTERVAL '1 hour')
        ");
        $ipHourStmt->execute(['ip' => $ip]);
        if ((int)$ipHourStmt->fetchColumn() >= $ipLimitPerHour) {
            $pdo->rollBack();
            json_error('当前网络发送次数过多，请1小时后重试');
            exit;
        }

        $ipDayStmt = $pdo->prepare("
            SELECT COUNT(*) AS c
            FROM sms_verification_codes
            WHERE ip = :ip
              AND created_at >= :today_start
        ");
        $ipDayStmt->execute([
            'ip' => $ip,
            'today_start' => $todayStart,
        ]);
        if ((int)$ipDayStmt->fetchColumn() >= $ipLimitPerDay) {
            $pdo->rollBack();
            json_error('当前网络今日发送次数已达上限');
            exit;
        }
    }

    // 将该手机号历史待使用验证码置为过期，仅保留最新一条有效
    $expirePendingStmt = $pdo->prepare("
        UPDATE sms_verification_codes
        SET status = 'expired'
        WHERE phone = :phone
          AND purpose = :purpose
          AND status = 'pending'
    ");
    $expirePendingStmt->execute([
        'phone' => $phone,
        'purpose' => $purpose,
    ]);

    $code = sms_generate_code();
    $expiresAt = date('Y-m-d H:i:s', time() + $ttl);

    $insStmt = $pdo->prepare("
        INSERT INTO sms_verification_codes (phone, purpose, code, status, ip, expires_at)
        VALUES (:phone, :purpose, :code, 'pending', :ip, :expires_at)
        RETURNING id
    ");
    $insStmt->execute([
        'phone' => $phone,
        'purpose' => $purpose,
        'code' => $code,
        'ip' => substr((string)$ip, 0, 64),
        'expires_at' => $expiresAt,
    ]);
    $codeId = (int)$insStmt->fetchColumn();

    $sendRet = sms_send_aliyun($phone, (string)($cfg['template_code_login'] ?? ''), ['code' => $code]);
    if (!$sendRet['success']) {
        // 调试模式：允许不通短信网关时直接下发（仅返回code便于联调）
        if ($debugReturnCode) {
            $pdo->commit();
            json_success([
                'phone' => $phone,
                'expiresIn' => $ttl,
                'debugCode' => $code,
            ], '验证码已生成（调试模式）');
            exit;
        }
        $delStmt = $pdo->prepare("DELETE FROM sms_verification_codes WHERE id = :id");
        $delStmt->execute(['id' => $codeId]);
        $pdo->rollBack();
        json_error($sendRet['message'] ?? '验证码发送失败', 500);
        exit;
    }

    $pdo->commit();
    json_success([
        'phone' => $phone,
        'expiresIn' => $ttl,
        'resendIn' => $cooldown,
    ], '验证码已发送');
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_exception('发送失败，请稍后重试', $e, 500);
}

