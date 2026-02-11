<?php
/**
 * GET /api/points/me.php
 * 当前积分与会员信息
 */
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/response.php';
require_once __DIR__ . '/../common/auth.php';
require_once __DIR__ . '/../common/points.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Method not allowed', 405);
    exit;
}

$userId = auth_get_current_user_id();
if ($userId <= 0) {
    json_success([
        'loggedIn' => false,
        'wallet' => null,
        'pricing' => points_get_pricing_config(),
    ]);
    exit;
}

try {
    $wallet = points_get_wallet_summary($userId);
    json_success([
        'loggedIn' => true,
        'wallet' => $wallet,
        'pricing' => points_get_pricing_config(),
    ]);
} catch (Throwable $e) {
    json_error('查询积分失败：' . $e->getMessage(), 500);
}
