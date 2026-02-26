<?php
/**
 * POST /api/auth/reset_password.php
 * 忘记密码：手机号 + 验证码 + 新密码 重置密码
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
$code = trim((string)($input['code'] ?? ''));
$password = (string)($input['password'] ?? '');

if (!sms_is_valid_phone($phone)) {
    json_error('请输入正确的11位手机号');
    exit;
}
if (!preg_match('/^\d{6}$/', $code)) {
    json_error('请输入6位验证码');
    exit;
}
if (strlen($password) < 6 || strlen($password) > 64) {
    json_error('新密码长度需为6-64位');
    exit;
}

try {
    $cfg = sms_config();
    $maxVerifyAttempts = max(1, (int)($cfg['max_verify_attempts'] ?? 5));

    $pdo = get_db();
    $pdo->beginTransaction();

    $userStmt = $pdo->prepare("SELECT id FROM users WHERE phone = :phone LIMIT 1 FOR UPDATE");
    $userStmt->execute(['phone' => $phone]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        $pdo->rollBack();
        json_error('该手机号未注册，请先注册');
        exit;
    }

    $codeStmt = $pdo->prepare("
        SELECT id, code, expires_at, fail_count
        FROM sms_verification_codes
        WHERE phone = :phone
          AND purpose = 'reset_password'
          AND status = 'pending'
        ORDER BY created_at DESC
        LIMIT 1
        FOR UPDATE
    ");
    $codeStmt->execute(['phone' => $phone]);
    $codeRow = $codeStmt->fetch(PDO::FETCH_ASSOC);

    if (!$codeRow) {
        $pdo->rollBack();
        json_error('验证码不存在或已失效，请先获取验证码', 401);
        exit;
    }
    if (strtotime((string)$codeRow['expires_at']) < time()) {
        $expireStmt = $pdo->prepare("UPDATE sms_verification_codes SET status = 'expired' WHERE id = :id");
        $expireStmt->execute(['id' => (int)$codeRow['id']]);
        $pdo->commit();
        json_error('验证码已过期，请重新获取', 401);
        exit;
    }

    if ((string)$codeRow['code'] !== $code) {
        $nextFailCount = (int)$codeRow['fail_count'] + 1;
        $newStatus = $nextFailCount >= $maxVerifyAttempts ? 'expired' : 'pending';
        $failStmt = $pdo->prepare("
            UPDATE sms_verification_codes
            SET fail_count = :fail_count, status = :status
            WHERE id = :id
        ");
        $failStmt->execute([
            'fail_count' => $nextFailCount,
            'status' => $newStatus,
            'id' => (int)$codeRow['id'],
        ]);
        $pdo->commit();
        if ($newStatus === 'expired') {
            json_error('验证码错误次数过多，请重新获取', 401);
            exit;
        }
        json_error('验证码错误', 401);
        exit;
    }

    $useStmt = $pdo->prepare("
        UPDATE sms_verification_codes
        SET status = 'used', used_at = CURRENT_TIMESTAMP
        WHERE id = :id
    ");
    $useStmt->execute(['id' => (int)$codeRow['id']]);

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $updStmt = $pdo->prepare("UPDATE users SET password_hash = :password_hash WHERE id = :id");
    $updStmt->execute([
        'password_hash' => $hash,
        'id' => (int)$user['id'],
    ]);

    $pdo->commit();

    json_success(null, '密码重置成功，请使用新密码登录');
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_exception('重置失败，请稍后重试', $e, 500);
}
