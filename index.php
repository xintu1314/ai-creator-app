<?php
session_start();

// 获取参数
$activeTab = $_GET['tab'] ?? 'create';
$creationType = $_GET['type'] ?? 'image';

// 模板数据
$imageTemplates = [
    ['id' => 'img-1', 'title' => '周四周四，生不如死', 'image' => 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=400&h=500&fit=crop', 'model' => '全能图片模型V2', 'type' => 'image'],
    ['id' => 'img-2', 'title' => '圣诞海报', 'image' => 'https://images.unsplash.com/photo-1576919228236-a097c32a5cd4?w=400&h=500&fit=crop', 'model' => '全能图片模型V2', 'type' => 'image'],
    ['id' => 'img-3', 'title' => '大雪猫猫节气海报', 'image' => 'https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?w=400&h=500&fit=crop', 'model' => '全能图片模型V2', 'type' => 'image'],
    ['id' => 'img-4', 'title' => 'Z-Image-3D卡通', 'image' => 'https://images.unsplash.com/photo-1634017839464-5c339ebe3cb4?w=400&h=500&fit=crop', 'model' => 'Z-Image Turbo', 'type' => 'image'],
    ['id' => 'img-5', 'title' => '山水画风格', 'image' => 'https://images.unsplash.com/photo-1515405295579-ba7b45403062?w=400&h=500&fit=crop', 'model' => 'Seedream 4.5', 'type' => 'image'],
    ['id' => 'img-6', 'title' => '产品展示图', 'image' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=400&h=500&fit=crop', 'model' => 'Seedream 4.5', 'type' => 'image'],
    ['id' => 'img-7', 'title' => '充气汽车', 'image' => 'https://images.unsplash.com/photo-1544636331-e26879cd4d9b?w=400&h=500&fit=crop', 'model' => '全能图片模型V2', 'type' => 'image'],
    ['id' => 'img-8', 'title' => '护肤品海报合成图', 'image' => 'https://images.unsplash.com/photo-1556228720-195a672e8a03?w=400&h=500&fit=crop', 'model' => 'Seedream 4.5', 'type' => 'image'],
];

$videoTemplates = [
    ['id' => 'vid-1', 'title' => '蝴蝶香氛', 'image' => 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=400&h=500&fit=crop', 'model' => 'PixVerse V5', 'type' => 'video'],
    ['id' => 'vid-2', 'title' => '生活不易，猫猫打工', 'image' => 'https://images.unsplash.com/photo-1513245543132-31f507417b26?w=400&h=500&fit=crop', 'model' => 'PixVerse V5', 'type' => 'video'],
    ['id' => 'vid-3', 'title' => '万物皆可猫猫头', 'image' => 'https://images.unsplash.com/photo-1573865526739-10659fec78a5?w=400&h=500&fit=crop', 'model' => 'PixVerse V5', 'type' => 'video'],
    ['id' => 'vid-4', 'title' => '打累了就休息', 'image' => 'https://images.unsplash.com/photo-1583511655857-d19b40a7a54e?w=400&h=500&fit=crop', 'model' => 'PixVerse V5', 'type' => 'video'],
    ['id' => 'vid-5', 'title' => '3d小猫蹲厕所', 'image' => 'https://images.unsplash.com/photo-1533738363-b7f9aef128ce?w=400&h=500&fit=crop', 'model' => '海螺 2.3', 'type' => 'video'],
    ['id' => 'vid-6', 'title' => 'eyes on you', 'image' => 'https://images.unsplash.com/photo-1494869042583-f6c911f04b4c?w=400&h=500&fit=crop', 'model' => '海螺 2.3', 'type' => 'video'],
    ['id' => 'vid-7', 'title' => '水织幻境', 'image' => 'https://images.unsplash.com/photo-1518837695005-2083093ee35b?w=400&h=500&fit=crop', 'model' => '海螺 2.3', 'type' => 'video'],
    ['id' => 'vid-8', 'title' => '砸晕了', 'image' => 'https://images.unsplash.com/photo-1535083783855-76ae62b2914e?w=400&h=500&fit=crop', 'model' => '海螺 2.3', 'type' => 'video'],
];

$templates = $creationType === 'image' ? $imageTemplates : $videoTemplates;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI创作平台</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- 自定义样式 -->
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Lucide Icons CDN -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-[#F5F5F5]">
    <div class="flex h-screen">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="flex-1 flex flex-col ml-16">
            <?php include 'includes/header.php'; ?>
            
            <main class="flex-1 mt-14 overflow-auto">
                <?php if ($activeTab === 'inspiration'): ?>
                    <?php include 'includes/inspiration_library.php'; ?>
                <?php elseif ($activeTab === 'create'): ?>
                    <?php include 'includes/creation_area.php'; ?>
                    
                    <div class="max-w-[900px] mx-auto px-6 pb-8">
                        <?php include 'includes/template_cards.php'; ?>
                    </div>
                <?php elseif ($activeTab === 'assets'): ?>
                    <?php include 'includes/assets.php'; ?>
                <?php elseif ($activeTab === 'publish'): ?>
                    <?php include 'includes/publish.php'; ?>
                <?php elseif ($activeTab === 'tutorial'): ?>
                    <?php include 'includes/tutorial.php'; ?>
                <?php endif; ?>
            </main>
        </div>
    </div>
    
    <?php include 'includes/dialogs.php'; ?>
    <?php include 'includes/video_params_dialog.php'; ?>
    <?php include 'includes/template_sheet.php'; ?>
    
    <script src="assets/js/main.js"></script>
    <script>
        // 确保页面加载完成后初始化
        window.addEventListener('load', function() {
            // 初始化Lucide图标
            if (typeof lucide !== 'undefined') {
                setTimeout(function() {
                    lucide.createIcons();
                }, 200);
            }
        });
        
        // 立即初始化一次
        if (typeof lucide !== 'undefined') {
            setTimeout(function() {
                lucide.createIcons();
            }, 100);
        }
    </script>
</body>
</html>
