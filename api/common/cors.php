<?php
/**
 * CORS 跨域配置
 * 在 API 入口文件顶部 require 即可
 */
// Configure allowlist by env: CORS_ALLOW_ORIGINS="https://a.com,https://b.com"
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowRaw = trim((string)getenv('CORS_ALLOW_ORIGINS'));
$allowlist = array_values(array_filter(array_map('trim', explode(',', $allowRaw))));
if (!empty($allowlist) && $origin !== '' && in_array($origin, $allowlist, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
} elseif (empty($allowlist)) {
    // Keep default wildcard for local/dev compatibility.
    header('Access-Control-Allow-Origin: *');
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Max-Age: 86400');

// 预检请求直接返回
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
