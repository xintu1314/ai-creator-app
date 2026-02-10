#!/usr/bin/env php
<?php
/**
 * PHP内置服务器启动脚本
 * 使用方法: php start.php
 * 或者: php -S localhost:8000
 */

$host = 'localhost';
$port = 8000;
$root = __DIR__;

echo "========================================\n";
echo "  PHP开发服务器启动\n";
echo "========================================\n";
echo "  访问地址: http://{$host}:{$port}\n";
echo "  项目目录: {$root}\n";
echo "========================================\n";
echo "  按 Ctrl+C 停止服务器\n";
echo "========================================\n\n";

// 启动PHP内置服务器
$command = sprintf(
    'php -S %s:%d -t %s',
    $host,
    $port,
    $root
);

passthru($command);
