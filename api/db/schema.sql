-- AI创作平台 数据库表结构

-- 资产表（用户生成的图片/视频）
CREATE TABLE IF NOT EXISTS assets (
    id SERIAL PRIMARY KEY,
    user_id INTEGER DEFAULT 0,
    title VARCHAR(255) NOT NULL,
    image VARCHAR(500) NOT NULL,
    type VARCHAR(20) NOT NULL CHECK (type IN ('image', 'video')),
    model VARCHAR(100) NOT NULL,
    prompt TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_assets_user_type ON assets(user_id, type);
CREATE INDEX IF NOT EXISTS idx_assets_created ON assets(created_at DESC);

-- 发布模板表
CREATE TABLE IF NOT EXISTS publish_templates (
    id SERIAL PRIMARY KEY,
    user_id INTEGER DEFAULT 0,
    content_type VARCHAR(20) NOT NULL CHECK (content_type IN ('image', 'video')),
    model_id VARCHAR(100) NOT NULL,
    model_name VARCHAR(100),
    category VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    image VARCHAR(500),
    review_status VARCHAR(20) NOT NULL DEFAULT 'approved' CHECK (review_status IN ('pending', 'approved', 'rejected')),
    is_online BOOLEAN NOT NULL DEFAULT true,
    review_note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_publish_created ON publish_templates(created_at DESC);

-- 生成任务表
CREATE TABLE IF NOT EXISTS tasks (
    id VARCHAR(64) PRIMARY KEY,
    user_id INTEGER DEFAULT 0,
    type VARCHAR(20) NOT NULL CHECK (type IN ('image', 'video')),
    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'processing', 'completed', 'failed')),
    params_json JSONB,
    result_url VARCHAR(500),
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_tasks_user_status ON tasks(user_id, status);
CREATE INDEX IF NOT EXISTS idx_tasks_created ON tasks(created_at DESC);

-- 用户表（账号密码）
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    account VARCHAR(32) NOT NULL UNIQUE,
    phone VARCHAR(20) UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    nickname VARCHAR(100) DEFAULT '',
    role VARCHAR(20) NOT NULL DEFAULT 'user',
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_users_account ON users(account);
CREATE INDEX IF NOT EXISTS idx_users_role_status ON users(role, status);

-- 用户积分钱包
CREATE TABLE IF NOT EXISTS user_wallets (
    user_id INTEGER PRIMARY KEY REFERENCES users(id) ON DELETE CASCADE,
    paid_balance INTEGER NOT NULL DEFAULT 0,
    bonus_balance INTEGER NOT NULL DEFAULT 0,
    bonus_cycle_key VARCHAR(20),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_wallets_updated ON user_wallets(updated_at DESC);

-- 积分流水
CREATE TABLE IF NOT EXISTS points_ledger (
    id BIGSERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    change_amount INTEGER NOT NULL,
    balance_after INTEGER NOT NULL,
    source VARCHAR(40) NOT NULL,
    description VARCHAR(255) DEFAULT '',
    meta_json JSONB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_points_ledger_user_created ON points_ledger(user_id, created_at DESC);

-- 会员状态（单用户一条）
CREATE TABLE IF NOT EXISTS user_memberships (
    user_id INTEGER PRIMARY KEY REFERENCES users(id) ON DELETE CASCADE,
    plan_code VARCHAR(40) NOT NULL,
    daily_bonus_points INTEGER NOT NULL DEFAULT 16,
    started_at TIMESTAMP NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'expired', 'cancelled')),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_memberships_expires ON user_memberships(expires_at DESC);

-- 短信验证码
CREATE TABLE IF NOT EXISTS sms_verification_codes (
    id BIGSERIAL PRIMARY KEY,
    phone VARCHAR(20) NOT NULL,
    purpose VARCHAR(32) NOT NULL DEFAULT 'login',
    code VARCHAR(6) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'used', 'expired')),
    ip VARCHAR(64) DEFAULT '',
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_sms_phone_created ON sms_verification_codes(phone, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_sms_phone_purpose_status ON sms_verification_codes(phone, purpose, status);

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

-- 管理员操作审计日志
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
