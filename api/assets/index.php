<?php
/**
 * GET /api/assets/index.php?filter=all|image|video&page=1&limit=20
 * 获取用户资产列表
 */
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/response.php';

$filter = $_GET['filter'] ?? 'all';

$mockHistory = [
    ['id' => 'hist-1', 'title' => '生成的图片1', 'image' => 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=400&h=500&fit=crop', 'type' => 'image', 'model' => 'banana pro', 'prompt' => '一个美丽的风景画', 'createdAt' => '2026-02-05 10:30'],
    ['id' => 'hist-2', 'title' => '生成的视频1', 'image' => 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=400&h=500&fit=crop', 'type' => 'video', 'model' => '可灵', 'prompt' => '一只蝴蝶在花丛中飞舞', 'createdAt' => '2026-02-05 09:15'],
    ['id' => 'hist-3', 'title' => '生成的图片2', 'image' => 'https://images.unsplash.com/photo-1576919228236-a097c32a5cd4?w=400&h=500&fit=crop', 'type' => 'image', 'model' => 'banana pro', 'prompt' => '圣诞主题的海报设计', 'createdAt' => '2026-02-04 16:20'],
];

if ($filter === 'image') {
    $data = array_filter($mockHistory, fn($i) => $i['type'] === 'image');
} elseif ($filter === 'video') {
    $data = array_filter($mockHistory, fn($i) => $i['type'] === 'video');
} else {
    $data = $mockHistory;
}

// 分页（可选）
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
$data = array_values($data);
$total = count($data);
$data = array_slice($data, ($page - 1) * $limit, $limit);

json_success([
    'list' => $data,
    'total' => $total,
    'page' => $page,
    'limit' => $limit,
]);
