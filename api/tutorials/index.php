<?php
/**
 * GET /api/tutorials/index.php
 * 获取教程列表
 */
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/response.php';
require_once __DIR__ . '/../data/tutorials.php';

$data = get_tutorials();
set_public_cache_headers(60, 30);
json_success($data);
