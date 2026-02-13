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

/**
 * 将外部媒体文件转存到本项目 OSS，返回新的永久 URL。
 * @param string $sourceUrl 外部媒体链接（仅支持 http/https）
 * @param string $mediaType image|video
 * @return string|null
 */
function oss_mirror_remote_media(string $sourceUrl, string $mediaType = 'image'): ?string {
    $sourceUrl = trim($sourceUrl);
    if ($sourceUrl === '') return null;

    $parts = parse_url($sourceUrl);
    $scheme = strtolower((string)($parts['scheme'] ?? ''));
    if (!in_array($scheme, ['http', 'https'], true)) return null;

    $tmpFile = tempnam(sys_get_temp_dir(), 'oss_mirror_');
    if ($tmpFile === false) return null;

    $fp = fopen($tmpFile, 'wb');
    if (!$fp) {
        @unlink($tmpFile);
        return null;
    }

    $timeout = $mediaType === 'video' ? 120 : 45;
    $ch = curl_init($sourceUrl);
    curl_setopt_array($ch, [
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_FILE => $fp,
        CURLOPT_HEADER => false,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $ok = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $contentType = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $curlError = curl_error($ch);
    @curl_close($ch);
    fclose($fp);

    if (!$ok || $httpCode < 200 || $httpCode >= 300) {
        @unlink($tmpFile);
        error_log('OSS mirror download failed: ' . ($curlError ?: ('HTTP ' . $httpCode)) . ' url=' . $sourceUrl);
        return null;
    }

    $extMap = [
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'video/mp4' => 'mp4',
        'video/webm' => 'webm',
        'video/quicktime' => 'mov',
        'video/x-matroska' => 'mkv',
    ];
    $mime = strtolower(trim(explode(';', $contentType)[0] ?? ''));
    $ext = $extMap[$mime] ?? '';
    // 优先从 URL path 取扩展名（更可靠，尤其是视频带签名参数时）
    if ($ext === '' || $mime === 'application/octet-stream' || $mime === 'binary/octet-stream') {
        $path = (string)($parts['path'] ?? '');
        $pathExt = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($pathExt !== '' && in_array($pathExt, ['jpg','jpeg','png','webp','gif','mp4','webm','mov','mkv'], true)) {
            $ext = $pathExt;
            // 同步修正 MIME
            $mimeFixMap = ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp','gif'=>'image/gif','mp4'=>'video/mp4','webm'=>'video/webm','mov'=>'video/quicktime','mkv'=>'video/x-matroska'];
            $mime = $mimeFixMap[$pathExt] ?? $mime;
        }
    }
    if ($ext === '') {
        $ext = $mediaType === 'video' ? 'mp4' : 'png';
    }

    $prefix = $mediaType === 'video' ? 'assets/videos/generated' : 'assets/images/generated';
    $objectKey = oss_object_key($prefix, $ext);
    $uploadedUrl = oss_upload_file($tmpFile, $objectKey, $mime ?: null);
    @unlink($tmpFile);
    return $uploadedUrl ?: null;
}
