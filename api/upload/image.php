<?php
/**
 * 图片上传接口 - 上传到阿里云 OSS
 * POST /api/upload/image.php
 * Content-Type: multipart/form-data
 * 字段: file (必填)
 * 可选: prefix (assets/images/templates | assets/images/references | assets/images/frames)
 *
 * 返回: { success: true, data: { url: "https://xxx.oss-cn-xxx.aliyuncs.com/..." } }
 * 参考：https://help.aliyun.com/zh/oss/developer-reference/simple-upload-using-oss-sdk-for-php-v2
 */
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/response.php';
require_once __DIR__ . '/../common/auth.php';
require_once __DIR__ . '/../common/oss.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
    exit;
}

$uid = auth_get_current_user_id();
if ($uid <= 0) {
    $sessErr = function_exists('auth_get_session_error') ? auth_get_session_error() : null;
    if ($sessErr) {
        json_error('登录状态异常：' . $sessErr . '（请检查 PHP session.save_path 权限/磁盘）', 500);
        exit;
    }
    json_error('请先登录', 401);
    exit;
}

$allowedTypes = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
];

$maxSize = 5 * 1024 * 1024; // 5MB

if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $msg = '未选择文件或上传失败';
    if (!empty($_FILES['file']['error'])) {
        $errors = [
            UPLOAD_ERR_INI_SIZE   => '文件超过服务器限制',
            UPLOAD_ERR_FORM_SIZE  => '文件过大',
            UPLOAD_ERR_PARTIAL    => '文件只上传了部分',
            UPLOAD_ERR_NO_FILE    => '未选择文件',
            UPLOAD_ERR_NO_TMP_DIR => '服务器临时目录不存在',
            UPLOAD_ERR_CANT_WRITE => '无法写入磁盘',
            UPLOAD_ERR_EXTENSION  => '扩展阻止了上传',
        ];
        $errCode = (int)$_FILES['file']['error'];
        $msg = $errors[$errCode] ?? ('上传错误码: ' . $errCode);
        // 补充 php.ini 限制信息，方便线上定位（不包含敏感信息）
        if (in_array($errCode, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            $msg .= '（upload_max_filesize=' . (ini_get('upload_max_filesize') ?: '-') .
                ', post_max_size=' . (ini_get('post_max_size') ?: '-') . '）';
        }
    }
    json_error($msg);
    exit;
}

$file = $_FILES['file'];
$tmpPath = $file['tmp_name'];
// MIME 以服务端检测为准，避免线上某些环境 $file['type'] 为空/不准
$browserMime = trim((string)($file['type'] ?? ''));
$mimeType = $browserMime;
if (function_exists('finfo_open')) {
    $finfo = @finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo) {
        $detected = @finfo_file($finfo, $tmpPath);
        @finfo_close($finfo);
        if (is_string($detected) && $detected !== '') {
            $mimeType = strtolower(trim($detected));
        }
    }
}
$mimeType = strtolower(trim((string)$mimeType));

if (!isset($allowedTypes[$mimeType])) {
    $extra = $browserMime && strtolower($browserMime) !== $mimeType
        ? ('（浏览器: ' . $browserMime . '，服务端: ' . $mimeType . '）')
        : ($mimeType ? ('（MIME: ' . $mimeType . '）') : '');
    json_error('仅支持 JPG、PNG、GIF、WebP 格式' . $extra);
    exit;
}

if ($file['size'] > $maxSize) {
    json_error('文件大小不能超过 5MB');
    exit;
}

$prefix = trim($_POST['prefix'] ?? 'assets/images/references');
$allowedPrefixes = ['assets/images/templates', 'assets/images/references', 'assets/images/frames'];
if (!in_array($prefix, $allowedPrefixes)) {
    $prefix = 'assets/images/references';
}

$ext = $allowedTypes[$mimeType];
$objectKey = oss_object_key($prefix, $ext);
$url = oss_upload_file($tmpPath, $objectKey, $mimeType);

if ($url) {
    json_success(['url' => $url], '上传成功');
} else {
    $detail = oss_get_last_error();
    $msg = $detail ? ('上传失败：' . $detail) : '上传失败，请检查 OSS 配置';
    json_error($msg, 500);
}
