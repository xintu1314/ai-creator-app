#!/usr/bin/env php
<?php
/**
 * OSS 上传测试脚本
 * 用法: php test_oss_upload.php
 */
echo "=== OSS 上传测试 ===\n\n";

// 1. 检查 vendor
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "❌ 错误: 请先运行 composer install 安装依赖\n";
    echo "   cd " . __DIR__ . " && composer install\n\n";
    exit(1);
}
echo "✓ vendor/autoload.php 存在\n";

// 2. 检查 OSS 配置
$config = require __DIR__ . '/api/config/oss.php';
if (empty($config['access_key_id']) || empty($config['bucket'])) {
    echo "❌ 错误: OSS 配置不完整，请检查 api/config/oss.local.php 或环境变量\n\n";
    exit(1);
}
echo "✓ OSS 配置已加载 (bucket: {$config['bucket']})\n";

// 3. 创建测试图片 (1x1 透明 PNG)
$testImage = __DIR__ . '/tmp_test_upload.png';
$pngData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
file_put_contents($testImage, $pngData);
echo "✓ 已创建测试图片\n";

// 4. 调用上传
require_once __DIR__ . '/api/common/oss.php';
$objectKey = oss_object_key('assets/images/references', 'png');
$url = oss_upload_file($testImage, $objectKey, 'image/png');

// 5. 清理
unlink($testImage);

if ($url) {
    echo "\n✅ 上传成功!\n";
    echo "   URL: $url\n";
    exit(0);
} else {
    echo "\n❌ 上传失败，请检查上方错误日志或 OSS 配置\n";
    exit(1);
}
