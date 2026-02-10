-- 初始模板数据（与原有静态数据一致）
-- 仅当 publish_templates 为空时导入

INSERT INTO publish_templates (content_type, model_id, model_name, category, title, content, image)
SELECT 'image', 'banana-pro', '全能图片模型V2', '室内', '周四周四，生不如死', '周四周四，生不如死', 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=400&h=500&fit=crop'
WHERE NOT EXISTS (SELECT 1 FROM publish_templates WHERE title = '周四周四，生不如死');

INSERT INTO publish_templates (content_type, model_id, model_name, category, title, content, image)
SELECT 'image', 'banana-pro', '全能图片模型V2', '室内', '圣诞海报', '圣诞主题海报设计', 'https://images.unsplash.com/photo-1576919228236-a097c32a5cd4?w=400&h=500&fit=crop'
WHERE NOT EXISTS (SELECT 1 FROM publish_templates WHERE title = '圣诞海报');

INSERT INTO publish_templates (content_type, model_id, model_name, category, title, content, image)
SELECT 'image', 'banana-pro', '全能图片模型V2', '自然', '大雪猫猫节气海报', '大雪节气猫猫海报', 'https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?w=400&h=500&fit=crop'
WHERE NOT EXISTS (SELECT 1 FROM publish_templates WHERE title = '大雪猫猫节气海报');

INSERT INTO publish_templates (content_type, model_id, model_name, category, title, content, image)
SELECT 'image', 'banana-pro', 'Z-Image Turbo', '人物', 'Z-Image-3D卡通', '3D卡通风格', 'https://images.unsplash.com/photo-1634017839464-5c339ebe3cb4?w=400&h=500&fit=crop'
WHERE NOT EXISTS (SELECT 1 FROM publish_templates WHERE title = 'Z-Image-3D卡通');

INSERT INTO publish_templates (content_type, model_id, model_name, category, title, content, image)
SELECT 'image', 'banana-pro', 'Seedream 4.5', '景观', '山水画风格', '中国山水画风格', 'https://images.unsplash.com/photo-1515405295579-ba7b45403062?w=400&h=500&fit=crop'
WHERE NOT EXISTS (SELECT 1 FROM publish_templates WHERE title = '山水画风格');

INSERT INTO publish_templates (content_type, model_id, model_name, category, title, content, image)
SELECT 'image', 'banana-pro', 'Seedream 4.5', '电商', '产品展示图', '产品展示图设计', 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=400&h=500&fit=crop'
WHERE NOT EXISTS (SELECT 1 FROM publish_templates WHERE title = '产品展示图');

INSERT INTO publish_templates (content_type, model_id, model_name, category, title, content, image)
SELECT 'image', 'banana-pro', '全能图片模型V2', '动物', '充气汽车', '充气汽车造型', 'https://images.unsplash.com/photo-1544636331-e26879cd4d9b?w=400&h=500&fit=crop'
WHERE NOT EXISTS (SELECT 1 FROM publish_templates WHERE title = '充气汽车');

INSERT INTO publish_templates (content_type, model_id, model_name, category, title, content, image)
SELECT 'image', 'banana-pro', 'Seedream 4.5', '电商', '护肤品海报合成图', '护肤品海报', 'https://images.unsplash.com/photo-1556228720-195a672e8a03?w=400&h=500&fit=crop'
WHERE NOT EXISTS (SELECT 1 FROM publish_templates WHERE title = '护肤品海报合成图');

INSERT INTO publish_templates (content_type, model_id, model_name, category, title, content, image)
SELECT 'video', 'kling', 'PixVerse V5', '人物', '蝴蝶香氛', '蝴蝶香氛视频', 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=400&h=500&fit=crop'
WHERE NOT EXISTS (SELECT 1 FROM publish_templates WHERE title = '蝴蝶香氛');

INSERT INTO publish_templates (content_type, model_id, model_name, category, title, content, image)
SELECT 'video', 'kling', 'PixVerse V5', '动物', '生活不易，猫猫打工', '猫猫打工视频', 'https://images.unsplash.com/photo-1513245543132-31f507417b26?w=400&h=500&fit=crop'
WHERE NOT EXISTS (SELECT 1 FROM publish_templates WHERE title = '生活不易，猫猫打工');

INSERT INTO publish_templates (content_type, model_id, model_name, category, title, content, image)
SELECT 'video', 'kling', 'PixVerse V5', '动物', '万物皆可猫猫头', '猫猫头视频', 'https://images.unsplash.com/photo-1573865526739-10659fec78a5?w=400&h=500&fit=crop'
WHERE NOT EXISTS (SELECT 1 FROM publish_templates WHERE title = '万物皆可猫猫头');

INSERT INTO publish_templates (content_type, model_id, model_name, category, title, content, image)
SELECT 'video', 'kling', 'PixVerse V5', '人物', '打累了就休息', '打累了休息视频', 'https://images.unsplash.com/photo-1583511655857-d19b40a7a54e?w=400&h=500&fit=crop'
WHERE NOT EXISTS (SELECT 1 FROM publish_templates WHERE title = '打累了就休息');

INSERT INTO publish_templates (content_type, model_id, model_name, category, title, content, image)
SELECT 'video', 'kling', '海螺 2.3', '动物', '3d小猫蹲厕所', '3D小猫视频', 'https://images.unsplash.com/photo-1533738363-b7f9aef128ce?w=400&h=500&fit=crop'
WHERE NOT EXISTS (SELECT 1 FROM publish_templates WHERE title = '3d小猫蹲厕所');

INSERT INTO publish_templates (content_type, model_id, model_name, category, title, content, image)
SELECT 'video', 'kling', '海螺 2.3', '人物', 'eyes on you', 'eyes on you 视频', 'https://images.unsplash.com/photo-1494869042583-f6c911f04b4c?w=400&h=500&fit=crop'
WHERE NOT EXISTS (SELECT 1 FROM publish_templates WHERE title = 'eyes on you');

INSERT INTO publish_templates (content_type, model_id, model_name, category, title, content, image)
SELECT 'video', 'kling', '海螺 2.3', '景观', '水织幻境', '水织幻境视频', 'https://images.unsplash.com/photo-1518837695005-2083093ee35b?w=400&h=500&fit=crop'
WHERE NOT EXISTS (SELECT 1 FROM publish_templates WHERE title = '水织幻境');

INSERT INTO publish_templates (content_type, model_id, model_name, category, title, content, image)
SELECT 'video', 'kling', '海螺 2.3', '动物', '砸晕了', '砸晕了视频', 'https://images.unsplash.com/photo-1535083783855-76ae62b2914e?w=400&h=500&fit=crop'
WHERE NOT EXISTS (SELECT 1 FROM publish_templates WHERE title = '砸晕了');
