<?php
function get_assets($filter = 'all') {
    $items = [
        ['id' => 'hist-1', 'title' => '生成的图片1', 'image' => 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=400&h=500&fit=crop', 'type' => 'image', 'model' => 'banana pro', 'prompt' => '一个美丽的风景画', 'createdAt' => '2026-02-05 10:30'],
        ['id' => 'hist-2', 'title' => '生成的视频1', 'image' => 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=400&h=500&fit=crop', 'type' => 'video', 'model' => '可灵', 'prompt' => '一只蝴蝶在花丛中飞舞', 'createdAt' => '2026-02-05 09:15'],
        ['id' => 'hist-3', 'title' => '生成的图片2', 'image' => 'https://images.unsplash.com/photo-1576919228236-a097c32a5cd4?w=400&h=500&fit=crop', 'type' => 'image', 'model' => 'banana pro', 'prompt' => '圣诞主题的海报设计', 'createdAt' => '2026-02-04 16:20'],
    ];
    if ($filter === 'image') {
        return array_values(array_filter($items, fn($i) => $i['type'] === 'image'));
    }
    if ($filter === 'video') {
        return array_values(array_filter($items, fn($i) => $i['type'] === 'video'));
    }
    return $items;
}
