<?php
function get_models($type = 'image') {
    $imageModels = [
        ['id' => 'banana-pro', 'name' => 'banana pro', 'description' => '高性能图片生成模型', 'icon' => 'banana', 'tags' => ['图片生成']],
    ];
    $videoModels = [
        ['id' => 'kling', 'name' => '可灵', 'description' => '高质量视频生成模型', 'icon' => 'kling', 'tags' => ['视频生成', '首尾帧']],
        ['id' => 'sora2', 'name' => 'sora2', 'description' => '先进的视频生成模型', 'icon' => 'sora', 'tags' => ['视频生成', '首尾帧']],
    ];
    return $type === 'video' ? $videoModels : $imageModels;
}
