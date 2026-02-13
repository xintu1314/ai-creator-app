<?php
/**
 * GET /api/templates/index.php?type=image|video
 * 获取模板列表
 */
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/response.php';
require_once __DIR__ . '/../data/templates.php';

$type = $_GET['type'] ?? 'image';
$data = get_templates($type);
set_public_cache_headers(60, 30);
json_success($data);
