<?php
require_once __DIR__ . '/api/common/auth.php';
// 统一 Session 启动逻辑（含 save_path fallback），避免页面与 /api 登录态不一致
auth_boot_session();

require_once __DIR__ . '/api/common/points.php';
require_once __DIR__ . '/api/data/models.php';
require_once __DIR__ . '/api/data/templates.php';
require_once __DIR__ . '/api/data/categories.php';
require_once __DIR__ . '/api/data/assets.php';
require_once __DIR__ . '/api/data/tutorials.php';

// 获取参数
$activeTab = $_GET['tab'] ?? 'create';
$creationType = $_GET['type'] ?? 'image';

// 从共享数据层加载（与 API 同源）
$imageTemplates = get_templates('image');
$videoTemplates = get_templates('video');
$templates = $creationType === 'image' ? $imageTemplates : $videoTemplates;

$imageModels = get_models('image');
$videoModels = get_models('video');
$categories = get_categories();
$currentModel = ($creationType === 'video' ? $videoModels : $imageModels)[0] ?? null;
$currentUser = auth_get_current_user();
$pointsSummary = !empty($currentUser['id']) ? points_get_wallet_summary((int)$currentUser['id']) : null;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI创作平台</title>
    <!-- Tailwind CSS build output -->
    <link rel="stylesheet" href="assets/css/tailwind.css">
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
            
            <main class="flex-1 mt-14 overflow-hidden flex flex-col">
                <?php if ($activeTab === 'inspiration'): ?>
                    <div class="flex-1 overflow-auto">
                        <?php include 'includes/inspiration_library.php'; ?>
                    </div>
                <?php elseif ($activeTab === 'create'): ?>
                    <?php include 'includes/creation_area.php'; ?>
                    
                    <div id="template-cards-section" class="max-w-[900px] mx-auto px-6 pb-8 overflow-auto flex-shrink-0">
                        <?php include 'includes/template_cards.php'; ?>
                    </div>
                <?php elseif ($activeTab === 'assets'): ?>
                    <div class="flex-1 overflow-auto">
                        <?php include 'includes/assets.php'; ?>
                    </div>
                <?php elseif ($activeTab === 'profile'): ?>
                    <div class="flex-1 overflow-auto">
                        <?php include 'includes/user_center.php'; ?>
                    </div>
                <?php elseif ($activeTab === 'publish'): ?>
                    <div class="flex-1 overflow-auto">
                        <?php include 'includes/publish.php'; ?>
                    </div>
                <?php elseif ($activeTab === 'admin'): ?>
                    <div class="flex-1 overflow-auto">
                        <?php include 'includes/admin.php'; ?>
                    </div>
                <?php elseif ($activeTab === 'tutorial'): ?>
                    <div class="flex-1 overflow-auto">
                        <?php include 'includes/tutorial.php'; ?>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
    
    <?php include 'includes/dialogs.php'; ?>
    <?php include 'includes/video_params_dialog.php'; ?>
    <?php include 'includes/template_sheet.php'; ?>
    
    <script src="assets/js/main.js?v=20250226b"></script>
    <script>
        window.currentUser = <?= json_encode($currentUser, JSON_UNESCAPED_UNICODE) ?>;
        window.pointsSummary = <?= json_encode($pointsSummary, JSON_UNESCAPED_UNICODE) ?>;
        window.pointsPricingImage = <?= json_encode((points_get_pricing_config()['image_cost_points'] ?? []), JSON_UNESCAPED_UNICODE) ?>;
        window.pointsPricingVideo = <?= json_encode((points_get_pricing_config()['video_cost_points'] ?? []), JSON_UNESCAPED_UNICODE) ?>;

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
