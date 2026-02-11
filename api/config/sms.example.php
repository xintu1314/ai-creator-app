<?php
/**
 * 短信配置示例 - 复制为 sms.local.php
 */
return [
    'aliyun' => [
        'enabled' => true,
        'debug_return_code' => false,
        'access_key_id' => 'LTAIxxxxxxxxxxxx',
        'access_key_secret' => 'xxxxxxxxxxxxxxxxxxxxxxxx',
        'region' => 'cn-hangzhou',
        'endpoint' => 'dysmsapi.aliyuncs.com',
        'sign_name' => '你的短信签名',
        'template_code_login' => 'SMS_123456789',
        'code_ttl_seconds' => 300,
        'send_cooldown_seconds' => 60,
        'daily_limit_per_phone' => 20,
    ],
];

