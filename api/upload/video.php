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

if (auth_get_current_user_id() <= 0) {
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
        $msg = $errors[$_FILES['file']['error']] ?? ('上传错误码: ' . $_FILES['file']['error']);
    }
    json_error($msg);
    exit;
}

$file = $_FILES['file'];
$tmpPath = $file['tmp_name'];
$mimeType = strtolower((string)($file['type'] ?? ''));
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
    json_error('仅支持 MP4、WebM、MOV、AVI、MPEG 格式');
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
    json_error('上传失败，请检查 OSS 配置', 500);
}
