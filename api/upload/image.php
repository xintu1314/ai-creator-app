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
require_once __DIR__ . '/../common/oss.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
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
        $msg = $errors[$_FILES['file']['error']] ?? '上传错误码: ' . $_FILES['file']['error'];
    }
    json_error($msg);
    exit;
}

$file = $_FILES['file'];
$tmpPath = $file['tmp_name'];
$mimeType = $file['type'] ?? '';

if (!isset($allowedTypes[$mimeType])) {
    json_error('仅支持 JPG、PNG、GIF、WebP 格式');
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
    json_error('上传失败，请检查 OSS 配置', 500);
}
