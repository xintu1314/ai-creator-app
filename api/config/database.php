<?php
/**
 * 数据库配置 - PostgreSQL
 * 优先级：环境变量 > database.local.php > 默认值
 */
$config = [
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'port' => (int)(getenv('DB_PORT') ?: 5432),
    'dbname' => getenv('DB_NAME') ?: 'ai_creator',
    'user' => getenv('DB_USER') ?: 'believer',
    'password' => getenv('DB_PASSWORD') ?: '',
    'charset' => 'UTF8',
];

$localFile = __DIR__ . '/database.local.php';
if (file_exists($localFile)) {
    $local = require $localFile;
    if (is_array($local)) {
        $config = array_merge($config, $local);
    }
}

return $config;
