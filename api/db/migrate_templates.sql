-- 为 publish_templates 添加展示所需字段
-- 发布后作为模板供全站用户查看

ALTER TABLE publish_templates ADD COLUMN IF NOT EXISTS image VARCHAR(500);
ALTER TABLE publish_templates ADD COLUMN IF NOT EXISTS model_name VARCHAR(100);

COMMENT ON COLUMN publish_templates.image IS '预览图 URL，可为空（使用默认占位图）';
COMMENT ON COLUMN publish_templates.model_name IS '模型显示名称';
