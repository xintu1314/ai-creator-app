<?php
/**
 * 阿里云 OSS 工具类
 * 参考文档：https://help.aliyun.com/zh/oss/developer-reference/simple-upload-using-oss-sdk-for-php-v2
 */

/**
 * 上传本地文件到 OSS
 * @param string $tmpPath 本地临时文件路径（如 $_FILES['file']['tmp_name']）
 * @param string $objectKey OSS 对象路径，如 assets/images/2025/02/10/uuid.jpg
 * @param string|null $contentType MIME 类型，如 image/jpeg
 * @return string|null 成功返回公网可访问的 URL，失败返回 null
 */
function oss_upload_file(string $tmpPath, string $objectKey, ?string $contentType = null): ?string {
    $config = require __DIR__ . '/../config/oss.php';
    if (empty($config['access_key_id']) || empty($config['access_key_secret']) || empty($config['bucket'])) {
        error_log('OSS config incomplete: access_key_id, access_key_secret, bucket are required');
        return null;
    }

    // 从配置设置环境变量，供 EnvironmentVariableCredentialsProvider 读取
    // OSS SDK 使用 OSS_ACCESS_KEY_ID、OSS_ACCESS_KEY_SECRET（见 README-CN.md）
    putenv('OSS_ACCESS_KEY_ID=' . $config['access_key_id']);
    putenv('OSS_ACCESS_KEY_SECRET=' . $config['access_key_secret']);

    if (!file_exists($tmpPath)) {
        error_log('OSS upload: file not exists ' . $tmpPath);
        return null;
    }

    try {
        require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

        $credentialsProvider = new \AlibabaCloud\Oss\V2\Credentials\EnvironmentVariableCredentialsProvider();
        $cfg = \AlibabaCloud\Oss\V2\Config::loadDefault();
        $cfg->setCredentialsProvider($credentialsProvider);
        $cfg->setRegion($config['region']);
        if (!empty($config['endpoint'])) {
            $cfg->setEndpoint($config['endpoint']);
        }

        $client = new \AlibabaCloud\Oss\V2\Client($cfg);
        $body = \AlibabaCloud\Oss\V2\Utils::streamFor(fopen($tmpPath, 'r'));
        $request = new \AlibabaCloud\Oss\V2\Models\PutObjectRequest(bucket: $config['bucket'], key: $objectKey);
        $request->body = $body;
        if ($contentType) {
            $request->contentType = $contentType;
        }
        $request->contentDisposition = 'inline';

        $client->putObject($request);

        // 生成公网访问 URL
        if (!empty($config['custom_domain'])) {
            $baseUrl = rtrim($config['custom_domain'], '/');
            return $baseUrl . '/' . ltrim($objectKey, '/');
        }
        $endpoint = $config['endpoint'] ?: 'oss-' . $config['region'] . '.aliyuncs.com';
        return sprintf('https://%s.%s/%s', $config['bucket'], $endpoint, ltrim($objectKey, '/'));
    } catch (Throwable $e) {
        error_log('OSS upload error: ' . $e->getMessage());
        return null;
    }
}

/**
 * 生成 OSS 对象路径
 * @param string $prefix 前缀，如 assets/images/templates、assets/images/references、assets/images/frames
 * @param string $ext 扩展名
 * @return string
 */
function oss_object_key(string $prefix, string $ext = 'jpg'): string {
    $date = date('Y/m/d');
    $uuid = bin2hex(random_bytes(8));
    return $prefix . '/' . $date . '/' . $uuid . '.' . $ext;
}
