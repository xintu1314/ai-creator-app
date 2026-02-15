<?php
/**
 * 视频上传接口 - 上传到阿里云 OSS
 * POST /api/upload/video.php
 * Content-Type: multipart/form-data
 * 字段: file (必填)
 * 可选: prefix (assets/videos/tutorials)
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
    'video/mp4' => 'mp4',
    'video/webm' => 'webm',
    'video/quicktime' => 'mov',
    'video/x-msvideo' => 'avi',
    'video/mpeg' => 'mpeg',
];
$allowedExts = ['mp4', 'webm', 'mov', 'avi', 'mpeg', 'mpg'];
$maxSize = 200 * 1024 * 1024; // 200MB

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
$browserMime = strtolower(trim((string)($file['type'] ?? '')));
$mimeType = $browserMime;
$detectedMime = '';
if (function_exists('finfo_open')) {
    $finfo = @finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo) {
        $detected = @finfo_file($finfo, $tmpPath);
        @finfo_close($finfo);
        if (is_string($detected) && $detected !== '') {
            $detectedMime = strtolower(trim($detected));
            $mimeType = $detectedMime;
        }
    }
}
$originalName = (string)($file['name'] ?? '');

if ($file['size'] > $maxSize) {
    json_error('视频大小不能超过 200MB');
    exit;
}

$ext = '';
if (isset($allowedTypes[$mimeType])) {
    $ext = $allowedTypes[$mimeType];
} else {
    $nameExt = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (in_array($nameExt, $allowedExts, true)) {
        $ext = $nameExt === 'mpg' ? 'mpeg' : $nameExt;
    }
}

if ($ext === '') {
    $extra = $mimeType ? ('（MIME: ' . $mimeType . '）') : '';
    json_error('仅支持 MP4、WebM、MOV、AVI、MPEG 格式' . $extra);
    exit;
}

$prefix = trim((string)($_POST['prefix'] ?? 'assets/videos/tutorials'));
$allowedPrefixes = ['assets/videos/tutorials', 'assets/videos/templates'];
if (!in_array($prefix, $allowedPrefixes, true)) {
    $prefix = 'assets/videos/tutorials';
}

// 推断 content type，确保 OSS 正确返回媒体类型
$contentType = array_search($ext, $allowedTypes, true);
if (!is_string($contentType) || $contentType === '') {
    $contentType = 'video/' . $ext;
}

$objectKey = oss_object_key($prefix, $ext);
$url = oss_upload_file($tmpPath, $objectKey, $contentType);
if ($url) {
    json_success(['url' => $url], '上传成功');
} else {
    $detail = oss_get_last_error();
    $msg = $detail ? ('上传失败：' . $detail) : '上传失败，请检查 OSS 配置';
    json_error($msg, 500);
}
