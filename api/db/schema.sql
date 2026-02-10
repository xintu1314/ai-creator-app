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
    category VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
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
