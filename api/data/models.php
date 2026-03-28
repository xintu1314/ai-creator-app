<?php
function get_models($type = 'image') {
    $imageModels = [
        ['id' => 'nanobanana2', 'name' => 'nanobanana2', 'description' => 'GrsAI Nano Banana 2（/v1/draw/nano-banana）', 'icon' => 'banana', 'tags' => ['图片生成', '参考图']],
        ['id' => 'banana', 'name' => 'banana', 'description' => 'NanoBanana 图片生成，支持参考图', 'icon' => 'banana', 'tags' => ['图片生成', '参考图']],
        ['id' => 'banana-pro', 'name' => 'banana pro', 'description' => 'NanoBanana Pro 高性能图片生成', 'icon' => 'banana', 'tags' => ['图片生成', '参考图']],
    ];
    $videoModels = [
        ['id' => 'doubao-video', 'name' => 'seedance1.5模型', 'description' => 'seedance1.5 视频生成模型', 'icon' => 'doubao', 'tags' => ['视频生成', '首尾帧']],
        ['id' => 'veo3.1-pro', 'name' => 'veo3.1_pro', 'description' => '无形科技 veo3.1_pro 视频生成', 'icon' => 'doubao', 'tags' => ['视频生成', '首尾帧', '有声']],
    ];
    return $type === 'video' ? $videoModels : $imageModels;
}
