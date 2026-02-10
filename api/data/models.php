<?php
function get_models($type = 'image') {
    $imageModels = [
        ['id' => 'banana', 'name' => 'banana', 'description' => 'NanoBanana 图片生成，支持参考图', 'icon' => 'banana', 'tags' => ['图片生成', '参考图']],
        ['id' => 'banana-pro', 'name' => 'banana pro', 'description' => 'NanoBanana Pro 高性能图片生成', 'icon' => 'banana', 'tags' => ['图片生成', '参考图']],
    ];
    $videoModels = [
        ['id' => 'doubao-video', 'name' => '豆包视频', 'description' => '豆包视频生成模型', 'icon' => 'doubao', 'tags' => ['视频生成', '首尾帧']],
    ];
    return $type === 'video' ? $videoModels : $imageModels;
}
