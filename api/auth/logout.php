<?php
/**
 * POST /api/auth/logout.php
 * 退出登录
 */
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/response.php';
require_once __DIR__ . '/../common/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
    exit;
}

auth_logout();
json_success(null, '已退出登录');

