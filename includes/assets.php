<?php
$activeFilter = $_GET['filter'] ?? 'all';
$currentUserId = !empty($currentUser['id']) ? (int)$currentUser['id'] : 0;
$historyItems = get_assets($activeFilter, 1, 100, $currentUserId);
$filteredHistory = $historyItems;
$creationType = $_GET['type'] ?? 'image';
$models = $creationType === 'image' ? $imageModels : $videoModels;
$currentModel = $currentModel ?? ($models[0] ?? null);
$defaultAspectRatio = $creationType === 'image' ? '3:4' : '16:9';
?>
<div id="assets-wrapper" class="flex-1 flex flex-col min-h-0">
    <!-- 统一滚动区域：生成进度 + 资产列表，生成时两者都可见 -->
    <div id="assets-scroll-container" class="flex-1 overflow-y-auto">
        <!-- 资产列表（垂直卡片布局，最早在上、最新在下） -->
        <div id="assets-list-section">
        <div class="max-w-[900px] mx-auto p-6">
            <h1 class="text-2xl font-semibold text-[#1A1A1A] mb-6">资产</h1>

            <div class="flex gap-4 border-b border-[#E5E5E5] mb-6">
                <a href="?tab=assets&filter=all" class="pb-3 px-1 text-sm font-medium transition-all duration-200 relative <?= $activeFilter === 'all' ? 'text-[#3B82F6]' : 'text-[#666666] hover:text-[#1A1A1A]' ?>">
                    全部
                    <?php if ($activeFilter === 'all'): ?>
                        <span class="absolute bottom-0 left-0 right-0 h-0.5 bg-[#3B82F6] rounded-full"></span>
                    <?php endif; ?>
                </a>
                <a href="?tab=assets&filter=image" class="pb-3 px-1 text-sm font-medium transition-all duration-200 relative flex items-center gap-2 <?= $activeFilter === 'image' ? 'text-[#3B82F6]' : 'text-[#666666] hover:text-[#1A1A1A]' ?>">
                    <i data-lucide="image" class="w-4 h-4"></i>
                    图片
                    <?php if ($activeFilter === 'image'): ?>
                        <span class="absolute bottom-0 left-0 right-0 h-0.5 bg-[#3B82F6] rounded-full"></span>
                    <?php endif; ?>
                </a>
                <a href="?tab=assets&filter=video" class="pb-3 px-1 text-sm font-medium transition-all duration-200 relative flex items-center gap-2 <?= $activeFilter === 'video' ? 'text-[#3B82F6]' : 'text-[#666666] hover:text-[#1A1A1A]' ?>">
                    <i data-lucide="video" class="w-4 h-4"></i>
                    视频
                    <?php if ($activeFilter === 'video'): ?>
                        <span class="absolute bottom-0 left-0 right-0 h-0.5 bg-[#3B82F6] rounded-full"></span>
                    <?php endif; ?>
                </a>
            </div>

            <?php if (empty($currentUser)): ?>
                <div class="text-center py-20 text-[#666666]">
                    <p class="mb-3">登录后可查看你的资产</p>
                    <button type="button" onclick="openAuthDialog('login')" class="h-9 px-4 text-sm font-medium bg-[#3B82F6] hover:bg-[#2563EB] text-white rounded-lg transition-colors">
                        去登录
                    </button>
                </div>
            <?php elseif (count($filteredHistory) > 0): ?>
                <div class="space-y-6">
                    <?php foreach ($filteredHistory as $item):
                        $mediaUrl = htmlspecialchars((string)($item['image'] ?? ''));
                        $isVideo = ($item['type'] ?? '') === 'video';
                        $ext = $isVideo ? 'mp4' : 'jpg';
                        $downloadName = ($item['type'] ?? 'file') . '-' . ($item['id'] ?? '') . '.' . $ext;
                        $title = htmlspecialchars($item['title'] ?? $item['prompt'] ?? '');
                        $model = htmlspecialchars($item['model'] ?? '');
                        $createdAt = htmlspecialchars($item['createdAt'] ?? '');
                    ?>
                        <div class="gen-result-card gen-fade-in" data-prompt="<?= htmlspecialchars($item['prompt'] ?? '') ?>" data-meta="<?= htmlspecialchars(json_encode(['type' => $item['type'] ?? 'image', 'model' => $model], JSON_UNESCAPED_UNICODE)) ?>">
                            <div class="px-5 pt-4 pb-2">
                                <div class="text-sm font-medium text-[#1A1A1A] mb-2"><?= $title ?></div>
                                <div class="flex flex-wrap gap-2 mb-3">
                                    <span class="px-2.5 py-0.5 text-xs rounded-full bg-[#EEF2FF] text-[#4F46E5]"><?= $item['type'] === 'image' ? '图片' : '视频' ?></span>
                                    <span class="px-2.5 py-0.5 text-xs rounded-full bg-[#F5F5F5] text-[#666]"><?= $model ?></span>
                                    <span class="px-2.5 py-0.5 text-xs rounded-full bg-[#F5F5F5] text-[#666]"><?= $createdAt ?></span>
                                </div>
                            </div>
                            <div class="px-5 pb-4">
                                <div class="relative">
                                    <?php if ($isVideo): ?>
                                        <video src="<?= $mediaUrl ?>" controls class="w-full rounded-2xl shadow-sm block max-h-[500px] object-contain bg-[#FAFAFA] gen-asset-draggable cursor-grab active:cursor-grabbing" draggable="true" data-url="<?= $mediaUrl ?>" data-type="video"></video>
                                    <?php else: ?>
                                        <img src="<?= $mediaUrl ?>" alt="<?= $title ?>" class="w-full rounded-2xl shadow-sm block max-h-[500px] object-contain bg-[#FAFAFA] gen-asset-draggable cursor-grab active:cursor-grabbing" draggable="true" data-url="<?= $mediaUrl ?>" data-type="image" loading="lazy" onerror="this.style.display='none';this.nextElementSibling?.classList.remove('hidden');" />
                                        <div class="hidden w-full h-[200px] flex items-center justify-center text-[#999] rounded-2xl bg-[#F5F5F5]">图片资源已失效</div>
                                    <?php endif; ?>
                                    <span class="absolute top-2 left-2 px-2 py-0.5 text-xs bg-black/50 text-white rounded-full backdrop-blur-sm">AI 生成</span>
                                </div>
                                <div class="flex items-center gap-5 mt-3 pt-3 border-t border-[#F0F0F0] text-sm text-[#888]">
                                    <button type="button" class="flex items-center gap-1.5 hover:text-[#3B82F6] transition-colors" onclick="reEditFromMessage(this)">
                                        <i data-lucide="pencil" class="w-3.5 h-3.5"></i> 重新编辑
                                    </button>
                                    <button type="button" class="flex items-center gap-1.5 hover:text-[#3B82F6] transition-colors" onclick="regenerateFromMessage(this)">
                                        <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i> 再次生成
                                    </button>
                                    <a href="<?= $mediaUrl ?>" download="<?= $downloadName ?>" class="flex items-center gap-1.5 hover:text-[#3B82F6] transition-colors">
                                        <i data-lucide="download" class="w-3.5 h-3.5"></i> 下载
                                    </a>
                                    <a href="<?= $mediaUrl ?>" target="_blank" class="flex items-center gap-1.5 hover:text-[#3B82F6] transition-colors">
                                        <i data-lucide="external-link" class="w-3.5 h-3.5"></i> 新窗口打开
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-20 text-[#666666]">
                    <p>暂无历史记录</p>
                </div>
            <?php endif; ?>
        </div>
        </div>

        <!-- 生成区域（在底部，生成时显示进度动画） -->
        <div id="generation-area" class="hidden px-6 pt-6 pb-2">
            <div class="max-w-[900px] mx-auto">
                <div id="generation-messages" class="space-y-6"></div>
            </div>
        </div>
    </div>

    <!-- 底部生成界面 -->
    <div id="creation-input-wrapper" class="flex-shrink-0 px-6 pb-6 pt-2 bg-[#F5F5F5]">
        <?php include __DIR__ . '/creation_input.php'; ?>
    </div>
</div>

<!-- 资产预览弹窗 -->
<div id="asset-preview-dialog" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4" onclick="closeAssetPreview()">
    <div class="relative max-w-[90vw] max-h-[90vh] bg-black rounded-lg overflow-hidden" onclick="event.stopPropagation()">
        <button type="button" onclick="closeAssetPreview()" class="absolute top-2 right-2 z-10 w-10 h-10 rounded-full bg-black/50 hover:bg-black/70 text-white flex items-center justify-center">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>
        <img id="asset-preview-img" src="" alt="预览" class="max-w-full max-h-[90vh] object-contain hidden">
        <video id="asset-preview-video" src="" controls class="max-w-full max-h-[90vh] hidden"></video>
    </div>
</div>
<script>
function openAssetPreview(url, isVideo) {
    var d = document.getElementById('asset-preview-dialog');
    var img = document.getElementById('asset-preview-img');
    var vid = document.getElementById('asset-preview-video');
    img.classList.add('hidden');
    vid.classList.add('hidden');
    if (isVideo) {
        vid.src = url;
        vid.classList.remove('hidden');
    } else {
        img.src = url;
        img.classList.remove('hidden');
    }
    d.classList.remove('hidden');
    if (typeof lucide !== 'undefined') lucide.createIcons();
}
function closeAssetPreview() {
    var d = document.getElementById('asset-preview-dialog');
    var img = document.getElementById('asset-preview-img');
    var vid = document.getElementById('asset-preview-video');
    d.classList.add('hidden');
    img.src = '';
    vid.src = '';
    vid.pause();
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && !document.getElementById('asset-preview-dialog').classList.contains('hidden')) closeAssetPreview();
});
// 进入资产页后默认滚动到底部，显示生成界面
(function scrollAssetsToBottom() {
    var c = document.getElementById('assets-scroll-container');
    if (!c) return;
    function done() { c.scrollTop = c.scrollHeight; }
    requestAnimationFrame(function() { done(); });
    setTimeout(done, 200);
    setTimeout(done, 600);
    window.addEventListener('load', function() { setTimeout(done, 50); });
})();
</script>
