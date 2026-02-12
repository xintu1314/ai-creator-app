<?php
/**
 * POST /api/auth/register.php
 * 手机号 + 密码注册（并自动登录）
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
$password = (string)($input['password'] ?? '');
$nickname = trim((string)($input['nickname'] ?? ''));

if (!sms_is_valid_phone($phone)) {
    json_error('请输入正确的11位手机号');
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
    $pdo = get_db();
    $check = $pdo->prepare("SELECT id FROM users WHERE phone = :phone LIMIT 1");
    $check->execute(['phone' => $phone]);
    if ($check->fetch(PDO::FETCH_ASSOC)) {
        json_error('手机号已注册，请直接登录');
        exit;
    }

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
        json_error('注册失败，请稍后重试', 500);
        exit;
    }

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
    if ((string)$e->getCode() === '23505') {
        json_error('手机号已注册，请直接登录');
        exit;
    }
    json_error('注册失败：' . $e->getMessage(), 500);
}

