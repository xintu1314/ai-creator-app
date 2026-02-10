<?php
/**
 * GET /api/models/index.php?type=image|video
 * 获取模型列表
 */
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/response.php';

$type = $_GET['type'] ?? 'image';

$imageModels = [
    ['id' => 'banana-pro', 'name' => 'banana pro', 'description' => '高性能图片生成模型', 'icon' => 'banana', 'tags' => ['图片生成']],
];

$videoModels = [
    ['id' => 'kling', 'name' => '可灵', 'description' => '高质量视频生成模型', 'icon' => 'kling', 'tags' => ['视频生成', '首尾帧']],
    ['id' => 'sora2', 'name' => 'sora2', 'description' => '先进的视频生成模型', 'icon' => 'sora', 'tags' => ['视频生成', '首尾帧']],
];

if ($type === 'video') {
    $data = $videoModels;
} else {
    $data = $imageModels;
}

json_success($data);
