<?php
/**
 * 初始化数据库：创建表 + 导入初始数据
 * 使用: php api/db/init.php
 */
require_once __DIR__ . '/../common/db.php';

$schema = file_get_contents(__DIR__ . '/schema.sql');
$pdo = get_db();
$pdo->exec($schema);
echo "✅ 表结构创建完成\n";

// 检查 assets 是否为空，空则导入 seed
$count = $pdo->query("SELECT COUNT(*) FROM assets")->fetchColumn();
if ($count == 0) {
    $seed = file_get_contents(__DIR__ . '/seed.sql');
    $pdo->exec($seed);
    echo "✅ 资产初始数据导入完成\n";
} else {
    echo "⏭  assets 表已有数据，跳过 seed\n";
}

// 模板迁移：添加 image、model_name 列
$pdo->exec(file_get_contents(__DIR__ . '/migrate_templates.sql'));
echo "✅ 模板表结构迁移完成\n";

// 模板 seed：若不存在 seed 模板则导入
$tplCount = $pdo->query("SELECT COUNT(*) FROM publish_templates WHERE title = '周四周四，生不如死'")->fetchColumn();
if ($tplCount == 0) {
    $pdo->exec(file_get_contents(__DIR__ . '/seed_templates.sql'));
    echo "✅ 模板初始数据导入完成\n";
} else {
    echo "⏭  模板已有数据，跳过 seed\n";
}

echo "完成\n";
