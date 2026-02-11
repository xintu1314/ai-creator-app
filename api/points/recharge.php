<?php
/**
 * POST /api/points/recharge.php
 * 积分充值（开发版：直接入账，后续可替换支付回调）
 */
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/response.php';
require_once __DIR__ . '/../common/auth.php';
require_once __DIR__ . '/../common/points.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
    exit;
}

$userId = auth_get_current_user_id();
if ($userId <= 0) {
    json_error('请先登录', 401);
    exit;
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?? [];
$packageId = trim((string)($input['packageId'] ?? ''));

$cfg = points_get_pricing_config();
$target = null;
foreach ($cfg['packages'] as $pkg) {
    if ($pkg['id'] === $packageId) {
        $target = $pkg;
        break;
    }
}

if (!$target) {
    json_error('无效的积分套餐');
    exit;
}

$ok = points_add_paid(
    $userId,
    (int)$target['points'],
    'recharge',
    '购买积分包：' . $target['name'],
    [
        'packageId' => $target['id'],
        'price' => $target['price'],
        'points' => $target['points'],
    ]
);

if (!$ok) {
    json_error('充值失败，请稍后重试', 500);
    exit;
}

$wallet = points_get_wallet_summary($userId);
json_success([
    'wallet' => $wallet,
    'package' => $target,
], '充值成功');
