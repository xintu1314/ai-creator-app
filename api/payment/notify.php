<?php
/**
 * 易支付异步回调
 * 注意：成功必须返回纯字符串 success
 */
require_once __DIR__ . '/../common/payment.php';

$result = payment_handle_callback($_GET);
if (!empty($result['success'])) {
    echo 'success';
} else {
    http_response_code(400);
    echo 'error';
}
