<?php
/**
 * GET /api/categories/index.php
 * 获取分类列表（用于发布模板）
 */
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/response.php';

$categories = ['室内', '景观', '建筑', '电商', '人物', '动物', '自然'];

json_success($categories);
