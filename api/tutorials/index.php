<?php
/**
 * GET /api/tutorials/index.php
 * 获取教程列表
 */
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/response.php';

// 模拟教程数据，后续可从数据库或 CMS 加载
$tutorials = [
    // ['id' => '1', 'title' => '入门教程', 'video' => '', 'description' => '教程内容将通过后台管理上传'],
];

json_success($tutorials);
