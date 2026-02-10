<?php
/**
 * POST /api/generation/create.php
 * 创建生成任务（占位接口，后续接入真实生图/生视频 API）
 *
 * 请求体 JSON:
 * - prompt: 提示词
 * - model: 模型名称
 * - type: image|video
 * - aspectRatio: 1:1|2:3|3:2|3:4|4:3|9:16|16:9|9:21|21:9
 * - mode: single|multiple (图片)
 * - quality: 2k|4k (图片) | standard|high (视频)
 * - count: 图片张数 (可选)
 * - duration: 视频时长 5|10 (可选)
 */
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
    exit;
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?? [];

$prompt = trim($input['prompt'] ?? '');
if (empty($prompt)) {
    json_error('请输入提示词');
    exit;
}

$type = $input['type'] ?? 'image';
if (!in_array($type, ['image', 'video'])) {
    json_error('无效的 type，应为 image 或 video');
    exit;
}

// TODO: 接入真实的生图/生视频 API
// 根据 model、type 调用对应的第三方接口（可灵、banana pro 等）
// 视频需处理首帧、尾帧上传（multipart/form-data）

$taskId = 'task_' . uniqid() . '_' . time();

json_success([
    'taskId' => $taskId,
    'status' => 'pending',
    'message' => '任务已创建，等待接入真实生成接口',
]);
