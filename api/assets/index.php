<?php
/**
 * GET /api/assets/index.php?filter=all|image|video&page=1&limit=20
 * 获取用户资产列表
 */
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/response.php';
require_once __DIR__ . '/../common/auth.php';
require_once __DIR__ . '/../data/assets.php';

$filter = $_GET['filter'] ?? 'all';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));

$userId = auth_get_current_user_id();
$items = get_assets($filter, $page, $limit, $userId);
$total = count($items);
$list = array_slice($items, ($page - 1) * $limit, $limit);

json_success([
    'list' => $list,
    'total' => $total,
    'page' => $page,
    'limit' => $limit,
]);
