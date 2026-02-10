<?php
/**
 * 阿里云 OSS 配置
 * 参考：https://help.aliyun.com/zh/oss/developer-reference/simple-upload-using-oss-sdk-for-php-v2
 *
 * 配置优先级：oss.local.php > 环境变量
 * 环境变量：OSS_ACCESS_KEY_ID、OSS_ACCESS_KEY_SECRET、OSS_BUCKET、OSS_REGION、OSS_ENDPOINT、OSS_CUSTOM_DOMAIN
 */
$default = [
    'access_key_id'     => getenv('OSS_ACCESS_KEY_ID') ?: '',
    'access_key_secret' => getenv('OSS_ACCESS_KEY_SECRET') ?: '',
    'region'            => getenv('OSS_REGION') ?: 'cn-hangzhou',
    'endpoint'          => getenv('OSS_ENDPOINT') ?: 'oss-cn-hangzhou.aliyuncs.com',
    'bucket'            => getenv('OSS_BUCKET') ?: '',
    'custom_domain'     => getenv('OSS_CUSTOM_DOMAIN') ?: '',
];
$localFile = __DIR__ . '/oss.local.php';
if (file_exists($localFile)) {
    return array_merge($default, require $localFile);
}
return $default;
