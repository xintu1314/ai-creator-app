<?php
/**
 * POST /api/publish/create.php
 * 发布模板（占位接口，后续可接入数据库存储）
 *
 * 请求体 JSON:
 * - contentType: image|video
 * - modelId: 模型 ID
 * - category: 分类
 * - title: 模板标题
 * - content: 模板内容（提示词）
 */
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
    exit;
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?? [];

$contentType = $input['contentType'] ?? '';
$modelId = $input['modelId'] ?? '';
$category = $input['category'] ?? '';
$title = trim($input['title'] ?? '');
$content = trim($input['content'] ?? '');

if (empty($modelId) || empty($category) || empty($title) || empty($content)) {
    json_error('请填写完整：模型、分类、标题、内容');
    exit;
}

if (!in_array($contentType, ['image', 'video'])) {
    json_error('无效的 contentType，应为 image 或 video');
    exit;
}

// TODO: 存入数据库
// 当前仅做占位，返回成功

$publishId = 'pub_' . uniqid() . '_' . time();

json_success([
    'id' => $publishId,
    'message' => '发布成功，等待接入数据库存储',
]);
