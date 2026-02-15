<?php
/**
 * 一次性脚本：根据手机号设置管理员
 * 用法: php set_admin_by_phone.php 13485353864
 */
$phone = $argv[1] ?? '';
if (!preg_match('/^1\d{10}$/', $phone)) {
    echo "用法: php set_admin_by_phone.php <11位手机号>\n";
    exit(1);
}

$baseDir = __DIR__;
if (basename($baseDir) === 'deploy') $baseDir = dirname($baseDir);
require_once $baseDir . '/api/common/db.php';

try {
    $pdo = get_db();
    $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE phone = :phone RETURNING id, account, phone, nickname");
    $stmt->execute(['phone' => $phone]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        echo "成功：用户 {$row['phone']} ({$row['nickname']}) 已设置为管理员\n";
    } else {
        echo "未找到该手机号对应的用户，请确保该用户已注册登录过\n";
        exit(1);
    }
} catch (Throwable $e) {
    echo "错误: " . $e->getMessage() . "\n";
    exit(1);
}
