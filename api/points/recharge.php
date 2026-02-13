<?php
/**
 * POST /api/points/recharge.php
 * 积分充值：创建支付订单并返回收银台跳转链接
 */
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/response.php';
require_once __DIR__ . '/../common/auth.php';
require_once __DIR__ . '/../common/points.php';
require_once __DIR__ . '/../common/payment.php';

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
$payType = strtolower(trim((string)($input['payType'] ?? 'alipay')));
if (!in_array($payType, ['alipay', 'wxpay'], true)) {
    $payType = 'alipay';
}

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

try {
    $order = payment_create_order(
        $userId,
        'recharge',
        (string)$target['id'],
        '积分充值-' . $target['name'],
        (float)$target['price'],
        (int)$target['points'],
        0
    );

    $baseUrl = payment_get_base_url();
    if ($baseUrl === '') {
        json_error('无法识别站点地址，请检查反向代理配置', 500);
        exit;
    }

    $notifyUrl = $baseUrl . '/api/payment/notify.php';
    $mapi = payment_create_mapi_trade($order, $payType, $notifyUrl);
    if (empty($mapi['success'])) {
        json_error((string)($mapi['message'] ?? '创建支付订单失败'), 500);
        exit;
    }

    json_success([
        'payInfo' => $mapi['data'],
        'outTradeNo' => $order['outTradeNo'],
        'payType' => $payType,
        'package' => $target,
    ], '订单创建成功');
} catch (Throwable $e) {
    json_exception('创建支付订单失败，请稍后重试', $e, 500);
}
