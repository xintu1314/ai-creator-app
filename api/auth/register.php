<?php
/**
 * POST /api/auth/register.php
 * 手机号 + 验证码 + 密码注册（并自动登录）
 */
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/response.php';
require_once __DIR__ . '/../common/db.php';
require_once __DIR__ . '/../common/auth.php';
require_once __DIR__ . '/../common/sms.php';
auth_ensure_user_admin_columns();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
    exit;
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?? [];

$phone = sms_normalize_phone((string)($input['phone'] ?? ''));
$code = trim((string)($input['code'] ?? ''));
$password = (string)($input['password'] ?? '');
$nickname = trim((string)($input['nickname'] ?? ''));

if (!sms_is_valid_phone($phone)) {
    json_error('请输入正确的11位手机号');
    exit;
}
if (!preg_match('/^\d{6}$/', $code)) {
    json_error('请输入6位验证码');
    exit;
}
if (strlen($password) < 6 || strlen($password) > 64) {
    json_error('密码长度需为6-64位');
    exit;
}
if ($nickname === '') {
    $nickname = '用户' . substr($phone, 0, 3) . '****' . substr($phone, -4);
}
if (function_exists('mb_substr')) {
    $nickname = mb_substr($nickname, 0, 100);
} else {
    $nickname = substr($nickname, 0, 100);
}

try {
    $cfg = sms_config();
    $maxVerifyAttempts = max(1, (int)($cfg['max_verify_attempts'] ?? 5));

    $pdo = get_db();
    $pdo->beginTransaction();

    $check = $pdo->prepare("SELECT id FROM users WHERE phone = :phone LIMIT 1 FOR UPDATE");
    $check->execute(['phone' => $phone]);
    if ($check->fetch(PDO::FETCH_ASSOC)) {
        $pdo->rollBack();
        json_error('手机号已注册，请直接登录');
        exit;
    }

    $codeStmt = $pdo->prepare("
        SELECT id, code, expires_at, fail_count
        FROM sms_verification_codes
        WHERE phone = :phone
          AND purpose = 'register'
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

    $account = 'u' . $phone;
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("
        INSERT INTO users (account, phone, password_hash, nickname)
        VALUES (:account, :phone, :password_hash, :nickname)
        RETURNING id, account, phone, nickname, role, status
    ");
    $stmt->execute([
        'account' => $account,
        'phone' => $phone,
        'password_hash' => $hash,
        'nickname' => $nickname,
    ]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        $pdo->rollBack();
        json_error('注册失败，请稍后重试', 500);
        exit;
    }

    $pdo->commit();

    auth_login($user);
    json_success([
        'user' => [
            'id' => (int)$user['id'],
            'account' => (string)$user['account'],
            'phone' => (string)$user['phone'],
            'nickname' => (string)$user['nickname'],
        ],
    ], '注册成功');
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ((string)$e->getCode() === '23505') {
        json_error('手机号已注册，请直接登录');
        exit;
    }
    json_exception('注册失败，请稍后重试', $e, 500);
}
