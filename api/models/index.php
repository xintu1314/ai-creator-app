<?php
/**
 * GET /api/models/index.php?type=image|video
 * 获取模型列表
 */
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/response.php';
require_once __DIR__ . '/../data/models.php';

$type = $_GET['type'] ?? 'image';
$data = get_models($type);
json_success($data);
