<?php
/**
 * 短信配置
 * 优先级：sms.local.php > 环境变量
 */
$default = [
    'aliyun' => [
        'enabled' => getenv('SMS_ENABLED') === '1',
        'debug_return_code' => getenv('SMS_DEBUG_RETURN_CODE') === '1',
        'access_key_id' => getenv('ALIYUN_SMS_ACCESS_KEY_ID') ?: '',
        'access_key_secret' => getenv('ALIYUN_SMS_ACCESS_KEY_SECRET') ?: '',
        'region' => getenv('ALIYUN_SMS_REGION') ?: 'cn-hangzhou',
        'endpoint' => getenv('ALIYUN_SMS_ENDPOINT') ?: 'dysmsapi.aliyuncs.com',
        'sign_name' => getenv('ALIYUN_SMS_SIGN_NAME') ?: '',
        'template_code_login' => getenv('ALIYUN_SMS_TEMPLATE_CODE_LOGIN') ?: '',
        'code_ttl_seconds' => (int)(getenv('ALIYUN_SMS_CODE_TTL_SECONDS') ?: 300),
        'send_cooldown_seconds' => (int)(getenv('ALIYUN_SMS_SEND_COOLDOWN_SECONDS') ?: 60),
        'daily_limit_per_phone' => (int)(getenv('ALIYUN_SMS_DAILY_LIMIT_PER_PHONE') ?: 20),
    ],
];

$localFile = __DIR__ . '/sms.local.php';
if (file_exists($localFile)) {
    $local = require $localFile;
    if (isset($local['aliyun']) && is_array($local['aliyun'])) {
        $default['aliyun'] = array_merge($default['aliyun'], $local['aliyun']);
    }
}
return $default;

