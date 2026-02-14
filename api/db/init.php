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

// 轻量迁移：users 增加 phone 字段（兼容旧库）
$pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS phone VARCHAR(20)");
$pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_users_phone ON users(phone)");

// 轻量迁移：短信验证码表（兼容旧库）
$pdo->exec("
CREATE TABLE IF NOT EXISTS sms_verification_codes (
    id BIGSERIAL PRIMARY KEY,
    phone VARCHAR(20) NOT NULL,
    purpose VARCHAR(32) NOT NULL DEFAULT 'login',
    code VARCHAR(6) NOT NULL,
    fail_count INTEGER NOT NULL DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'used', 'expired')),
    ip VARCHAR(64) DEFAULT '',
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)
");
$pdo->exec("ALTER TABLE sms_verification_codes ADD COLUMN IF NOT EXISTS fail_count INTEGER NOT NULL DEFAULT 0");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_sms_phone_created ON sms_verification_codes(phone, created_at DESC)");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_sms_phone_purpose_status ON sms_verification_codes(phone, purpose, status)");

// 轻量迁移：支付订单表（兼容旧库）
$pdo->exec("
CREATE TABLE IF NOT EXISTS payment_orders (
    id BIGSERIAL PRIMARY KEY,
    out_trade_no VARCHAR(64) NOT NULL UNIQUE,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    order_type VARCHAR(20) NOT NULL CHECK (order_type IN ('recharge', 'membership')),
    biz_id VARCHAR(64) NOT NULL,
    subject VARCHAR(120) NOT NULL,
    amount NUMERIC(10, 2) NOT NULL,
    points_amount INTEGER NOT NULL DEFAULT 0,
    membership_days INTEGER NOT NULL DEFAULT 0,
    pay_param VARCHAR(255) DEFAULT '',
    pay_type VARCHAR(20) DEFAULT '',
    trade_no VARCHAR(64) DEFAULT '',
    status VARCHAR(20) NOT NULL DEFAULT 'created' CHECK (status IN ('created', 'paid', 'processing', 'done', 'failed')),
    callback_raw JSONB,
    paid_at TIMESTAMP,
    fulfilled_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)
");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_payment_orders_user_created ON payment_orders(user_id, created_at DESC)");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_payment_orders_status_created ON payment_orders(status, created_at DESC)");

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

// 管理后台迁移：权限、教程、审核、审计
$pdo->exec(file_get_contents(__DIR__ . '/migrate_admin.sql'));
echo "✅ 管理后台表结构迁移完成\n";

// 模板 seed：若不存在 seed 模板则导入
$tplCount = $pdo->query("SELECT COUNT(*) FROM publish_templates WHERE title = '周四周四，生不如死'")->fetchColumn();
if ($tplCount == 0) {
    $pdo->exec(file_get_contents(__DIR__ . '/seed_templates.sql'));
    echo "✅ 模板初始数据导入完成\n";
} else {
    echo "⏭  模板已有数据，跳过 seed\n";
}

echo "完成\n";
