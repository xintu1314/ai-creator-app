<?php
/**
 * 支付配置（易支付兼容）
 * 配置优先级：payment.local.php > 环境变量
 */
$default = [
    'epay' => [
        'api_base' => getenv('EPAY_API_BASE') ?: '',
        'pid' => getenv('EPAY_PID') ?: '',
        'key' => getenv('EPAY_KEY') ?: '',
        'sign_type' => 'MD5',
    ],
];

$localFile = __DIR__ . '/payment.local.php';
if (file_exists($localFile)) {
    $local = require $localFile;
    if (isset($local['epay']) && is_array($local['epay'])) {
        $default['epay'] = array_merge($default['epay'], $local['epay']);
    }
}

return $default;
