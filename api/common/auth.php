<?php
/**
 * 认证相关公共方法（Session）
 */

function auth_boot_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function auth_get_current_user(): ?array {
    auth_boot_session();
    $user = $_SESSION['user'] ?? null;
    if (!is_array($user)) {
        return null;
    }
    $id = (int)($user['id'] ?? 0);
    $account = trim((string)($user['account'] ?? ''));
    $phone = trim((string)($user['phone'] ?? ''));
    if ($id <= 0 || ($account === '' && $phone === '')) {
        return null;
    }
    return [
        'id' => $id,
        'account' => $account,
        'phone' => $phone,
        'nickname' => trim((string)($user['nickname'] ?? '')),
    ];
}

function auth_get_current_user_id(): int {
    $user = auth_get_current_user();
    return $user ? (int)$user['id'] : 0;
}

function auth_login(array $user): void {
    auth_boot_session();
    $_SESSION['user'] = [
        'id' => (int)$user['id'],
        'account' => (string)($user['account'] ?? ''),
        'phone' => (string)($user['phone'] ?? ''),
        'nickname' => (string)($user['nickname'] ?? ''),
    ];
}

function auth_logout(): void {
    auth_boot_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

