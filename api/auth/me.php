<?php
/**
 * GET /api/auth/me.php
 * 获取当前登录用户
 */
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/response.php';
require_once __DIR__ . '/../common/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Method not allowed', 405);
    exit;
}

$user = auth_get_current_user();
json_success([
    'loggedIn' => $user !== null,
    'user' => $user,
]);

