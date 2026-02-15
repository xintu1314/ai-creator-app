--
-- PostgreSQL database dump
--

\restrict tKlzm9ZrfN1nyeSS9rcf7Q0Mz7LrZ5kowFI1neww2tLKWZ4kZV6ASjPgPBb6ytj

-- Dumped from database version 16.11 (Homebrew)
-- Dumped by pg_dump version 16.11 (Homebrew)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Data for Name: publish_templates; Type: TABLE DATA; Schema: public; Owner: believer
--

INSERT INTO public.publish_templates (id, user_id, content_type, model_id, category, title, content, created_at, image, model_name, review_status, is_online, review_note) VALUES (1, 0, 'image', 'banana-pro', '室内', '测试模板', '测试提示词', '2026-02-10 16:48:24.859218', NULL, NULL, 'approved', true, NULL);
INSERT INTO public.publish_templates (id, user_id, content_type, model_id, category, title, content, created_at, image, model_name, review_status, is_online, review_note) VALUES (2, 0, 'image', 'banana-pro', '室内', '周四周四，生不如死', '周四周四，生不如死', '2026-02-10 16:57:58.021242', 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=400&h=500&fit=crop', '全能图片模型V2', 'approved', true, NULL);
INSERT INTO public.publish_templates (id, user_id, content_type, model_id, category, title, content, created_at, image, model_name, review_status, is_online, review_note) VALUES (3, 0, 'image', 'banana-pro', '室内', '圣诞海报', '圣诞主题海报设计', '2026-02-10 16:57:58.028293', 'https://images.unsplash.com/photo-1576919228236-a097c32a5cd4?w=400&h=500&fit=crop', '全能图片模型V2', 'approved', true, NULL);
INSERT INTO public.publish_templates (id, user_id, content_type, model_id, category, title, content, created_at, image, model_name, review_status, is_online, review_note) VALUES (4, 0, 'image', 'banana-pro', '自然', '大雪猫猫节气海报', '大雪节气猫猫海报', '2026-02-10 16:57:58.031587', 'https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?w=400&h=500&fit=crop', '全能图片模型V2', 'approved', true, NULL);
INSERT INTO public.publish_templates (id, user_id, content_type, model_id, category, title, content, created_at, image, model_name, review_status, is_online, review_note) VALUES (5, 0, 'image', 'banana-pro', '人物', 'Z-Image-3D卡通', '3D卡通风格', '2026-02-10 16:57:58.032256', 'https://images.unsplash.com/photo-1634017839464-5c339ebe3cb4?w=400&h=500&fit=crop', 'Z-Image Turbo', 'approved', true, NULL);
INSERT INTO public.publish_templates (id, user_id, content_type, model_id, category, title, content, created_at, image, model_name, review_status, is_online, review_note) VALUES (6, 0, 'image', 'banana-pro', '景观', '山水画风格', '中国山水画风格', '2026-02-10 16:57:58.032544', 'https://images.unsplash.com/photo-1515405295579-ba7b45403062?w=400&h=500&fit=crop', 'Seedream 4.5', 'approved', true, NULL);
INSERT INTO public.publish_templates (id, user_id, content_type, model_id, category, title, content, created_at, image, model_name, review_status, is_online, review_note) VALUES (7, 0, 'image', 'banana-pro', '电商', '产品展示图', '产品展示图设计', '2026-02-10 16:57:58.032858', 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=400&h=500&fit=crop', 'Seedream 4.5', 'approved', true, NULL);
INSERT INTO public.publish_templates (id, user_id, content_type, model_id, category, title, content, created_at, image, model_name, review_status, is_online, review_note) VALUES (8, 0, 'image', 'banana-pro', '动物', '充气汽车', '充气汽车造型', '2026-02-10 16:57:58.03355', 'https://images.unsplash.com/photo-1544636331-e26879cd4d9b?w=400&h=500&fit=crop', '全能图片模型V2', 'approved', true, NULL);
INSERT INTO public.publish_templates (id, user_id, content_type, model_id, category, title, content, created_at, image, model_name, review_status, is_online, review_note) VALUES (9, 0, 'image', 'banana-pro', '电商', '护肤品海报合成图', '护肤品海报', '2026-02-10 16:57:58.034217', 'https://images.unsplash.com/photo-1556228720-195a672e8a03?w=400&h=500&fit=crop', 'Seedream 4.5', 'approved', true, NULL);
INSERT INTO public.publish_templates (id, user_id, content_type, model_id, category, title, content, created_at, image, model_name, review_status, is_online, review_note) VALUES (18, 0, 'image', 'banana-pro', '电商', '测试持久化模板', '验证数据库写入', '2026-02-10 17:08:43.810548', NULL, 'banana pro', 'approved', true, NULL);
INSERT INTO public.publish_templates (id, user_id, content_type, model_id, category, title, content, created_at, image, model_name, review_status, is_online, review_note) VALUES (19, 4, 'image', 'banana-pro', '室内', '111', 'test', '2026-02-11 18:47:58.900036', 'https://ai-design-vedio.oss-cn-beijing.aliyuncs.com/assets/images/templates/2026/02/11/73497c675367b7b8.png', 'banana pro', 'approved', true, NULL);
INSERT INTO public.publish_templates (id, user_id, content_type, model_id, category, title, content, created_at, image, model_name, review_status, is_online, review_note) VALUES (20, 5, 'image', 'banana', '室内', '自动化测试模板', '自动化测试内容', '2026-02-12 17:27:33.702062', NULL, 'banana', 'pending', true, NULL);
INSERT INTO public.publish_templates (id, user_id, content_type, model_id, category, title, content, created_at, image, model_name, review_status, is_online, review_note) VALUES (21, 5, 'image', 'banana', '室内', '测试发布', '测试内容', '2026-02-12 17:28:11.651913', NULL, 'banana', 'pending', true, NULL);
INSERT INTO public.publish_templates (id, user_id, content_type, model_id, category, title, content, created_at, image, model_name, review_status, is_online, review_note) VALUES (22, 4, 'video', 'doubao-video', '电商', '电商带货视频', '111', '2026-02-13 21:40:15.66916', 'https://ai-design-vedio.oss-cn-beijing.aliyuncs.com/assets/videos/templates/2026/02/13/2aa82d0384d686c1.mp4', '豆包视频', 'approved', true, '');
INSERT INTO public.publish_templates (id, user_id, content_type, model_id, category, title, content, created_at, image, model_name, review_status, is_online, review_note) VALUES (23, 4, 'image', 'banana-pro', '电商', '营销图', '1111', '2026-02-14 10:26:35.716233', 'https://ai-design-vedio.oss-cn-beijing.aliyuncs.com/assets/images/templates/2026/02/14/79dfe48b6a9d7e3d.png', 'banana pro', 'approved', true, NULL);


--
-- Name: publish_templates_id_seq; Type: SEQUENCE SET; Schema: public; Owner: believer
--

SELECT pg_catalog.setval('public.publish_templates_id_seq', 23, true);


--
-- PostgreSQL database dump complete
--

\unrestrict tKlzm9ZrfN1nyeSS9rcf7Q0Mz7LrZ5kowFI1neww2tLKWZ4kZV6ASjPgPBb6ytj

