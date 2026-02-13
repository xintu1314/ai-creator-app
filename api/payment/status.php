<?php
/**
 * GET /api/payment/status.php?outTradeNo=xxx
 * 查询当前用户支付订单状态
 */
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/response.php';
require_once __DIR__ . '/../common/auth.php';
require_once __DIR__ . '/../common/payment.php';
require_once __DIR__ . '/../common/points.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Method not allowed', 405);
    exit;
}

$userId = auth_get_current_user_id();
if ($userId <= 0) {
    json_error('请先登录', 401);
    exit;
}

$outTradeNo = trim((string)($_GET['outTradeNo'] ?? ''));
if ($outTradeNo === '') {
    json_error('缺少订单号');
    exit;
}

$order = payment_get_order_by_out_trade_no($outTradeNo);
if (!$order || (int)$order['user_id'] !== $userId) {
    json_error('订单不存在', 404);
    exit;
}

$status = (string)$order['status'];
if ($status !== 'done') {
    // 本地开发环境常见：notify_url 无法被公网回调，轮询时主动对单补偿。
    $sync = payment_try_sync_by_query($outTradeNo);
    if (!empty($sync['success'])) {
        $order = payment_get_order_by_out_trade_no($outTradeNo) ?: $order;
        $status = (string)$order['status'];
    }
}

$done = $status === 'done';
$data = [
    'outTradeNo' => $outTradeNo,
    'status' => $status,
    'done' => $done,
    'payType' => (string)($order['pay_type'] ?? ''),
    'tradeNo' => (string)($order['trade_no'] ?? ''),
];
if ($done) {
    $data['wallet'] = points_get_wallet_summary($userId);
}

json_success($data);
