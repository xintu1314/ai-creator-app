<?php
/**
 * 短信配置示例 - 复制为 sms.local.php
 *
 * 阿里云短信需在控制台申请：
 * 1. 短信签名（SignName）- 如「你的产品名」
 * 2. 短信模板（TemplateCode）- 验证码模板，变量名为 code
 * 3. AccessKey（建议使用 RAM 子账号，权限：dysms:SendSms）
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
        'template_code_login' => 'SMS_332125186',
        'code_ttl_seconds' => 300,
        'send_cooldown_seconds' => 60,
        'daily_limit_per_phone' => 20,
        'ip_limit_per_minute' => 5,
        'ip_limit_per_hour' => 30,
        'ip_limit_per_day' => 200,
        'max_verify_attempts' => 5,
    ],
];

