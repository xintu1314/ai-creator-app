<?php
/**
 * POST /api/auth/login.php
 * 手机号登录（支持：验证码 / 密码）
 */
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/response.php';
require_once __DIR__ . '/../common/db.php';
require_once __DIR__ . '/../common/auth.php';
require_once __DIR__ . '/../common/sms.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
    exit;
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?? [];

$phone = trim((string)($input['phone'] ?? ''));
$code = trim((string)($input['code'] ?? ''));
$password = (string)($input['password'] ?? '');

if (!sms_is_valid_phone($phone)) {
    json_error('请输入正确的11位手机号');
    exit;
}
if (!preg_match('/^\d{6}$/', $code) && (strlen($password) < 6 || strlen($password) > 64)) {
    json_error('请输入6位验证码或6-64位密码');
    exit;
}

try {
    $pdo = get_db();
    $pdo->beginTransaction();

    $userStmt = $pdo->prepare("
        SELECT id, account, phone, nickname, password_hash
        FROM users
        WHERE phone = :phone
        LIMIT 1
        FOR UPDATE
    ");
    $userStmt->execute(['phone' => $phone]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    $usingCode = preg_match('/^\d{6}$/', $code) === 1;
    if ($usingCode) {
        $codeStmt = $pdo->prepare("
            SELECT id, code, expires_at
            FROM sms_verification_codes
            WHERE phone = :phone
              AND purpose = 'login'
              AND status = 'pending'
            ORDER BY created_at DESC
            LIMIT 1
            FOR UPDATE
        ");
        $codeStmt->execute(['phone' => $phone]);
        $codeRow = $codeStmt->fetch(PDO::FETCH_ASSOC);

        if (!$codeRow) {
            $pdo->rollBack();
            json_error('验证码不存在或已失效', 401);
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
            $pdo->rollBack();
            json_error('验证码错误', 401);
            exit;
        }

        $useStmt = $pdo->prepare("
            UPDATE sms_verification_codes
            SET status = 'used', used_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        $useStmt->execute(['id' => (int)$codeRow['id']]);

        if (!$user) {
            $masked = substr($phone, 0, 3) . '****' . substr($phone, -4);
            $account = 'u' . $phone;
            $dummyPwd = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
            $createStmt = $pdo->prepare("
                INSERT INTO users (account, phone, password_hash, nickname)
                VALUES (:account, :phone, :password_hash, :nickname)
                RETURNING id, account, phone, nickname, password_hash
            ");
            $createStmt->execute([
                'account' => $account,
                'phone' => $phone,
                'password_hash' => $dummyPwd,
                'nickname' => '用户' . $masked,
            ]);
            $user = $createStmt->fetch(PDO::FETCH_ASSOC);
        }
    } else {
        if (!$user || !password_verify($password, (string)$user['password_hash'])) {
            $pdo->rollBack();
            json_error('手机号或密码错误', 401);
            exit;
        }
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
    ], '登录成功');
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_error('登录失败：' . $e->getMessage(), 500);
}

