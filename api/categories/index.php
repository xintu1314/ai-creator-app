<?php
/**
 * GET /api/categories/index.php
 * 获取分类列表（用于发布模板）
 */
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/response.php';
require_once __DIR__ . '/../data/categories.php';

$data = get_categories();
set_public_cache_headers(300, 60);
json_success($data);
