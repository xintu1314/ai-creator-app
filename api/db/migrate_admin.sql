-- 管理后台相关迁移

-- users 权限字段
ALTER TABLE users ADD COLUMN IF NOT EXISTS role VARCHAR(20) NOT NULL DEFAULT 'user';
ALTER TABLE users ADD COLUMN IF NOT EXISTS status VARCHAR(20) NOT NULL DEFAULT 'active';
CREATE INDEX IF NOT EXISTS idx_users_role_status ON users(role, status);

-- publish_templates 审核与展示字段
ALTER TABLE publish_templates ADD COLUMN IF NOT EXISTS model_name VARCHAR(100);
ALTER TABLE publish_templates ADD COLUMN IF NOT EXISTS image VARCHAR(500);
ALTER TABLE publish_templates ADD COLUMN IF NOT EXISTS review_status VARCHAR(20) NOT NULL DEFAULT 'approved';
ALTER TABLE publish_templates ADD COLUMN IF NOT EXISTS is_online BOOLEAN NOT NULL DEFAULT true;
ALTER TABLE publish_templates ADD COLUMN IF NOT EXISTS review_note TEXT;

-- 旧数据兜底
UPDATE publish_templates SET review_status = 'approved' WHERE review_status IS NULL;
UPDATE publish_templates SET is_online = true WHERE is_online IS NULL;

-- 教程表
CREATE TABLE IF NOT EXISTS tutorials (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT DEFAULT '',
    cover_url VARCHAR(500) DEFAULT '',
    video_url VARCHAR(500) NOT NULL,
    is_published BOOLEAN NOT NULL DEFAULT true,
    sort_order INTEGER NOT NULL DEFAULT 0,
    created_by INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_tutorials_published_sort ON tutorials(is_published, sort_order, created_at DESC);

-- 管理员审计表
CREATE TABLE IF NOT EXISTS admin_audit_logs (
    id BIGSERIAL PRIMARY KEY,
    admin_user_id INTEGER NOT NULL,
    action VARCHAR(80) NOT NULL,
    target_type VARCHAR(40) DEFAULT '',
    target_id VARCHAR(64) DEFAULT '',
    payload_json JSONB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_admin_audit_admin_created ON admin_audit_logs(admin_user_id, created_at DESC);
