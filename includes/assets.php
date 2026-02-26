<?php
// 从共享数据层加载资产（与 api/assets 同源）
$activeFilter = $_GET['filter'] ?? 'all';
$currentUserId = !empty($currentUser['id']) ? (int)$currentUser['id'] : 0;
$historyItems = get_assets($activeFilter, 1, 100, $currentUserId);
$filteredHistory = $historyItems; // get_assets 已按 filter 过滤
?>
<div class="max-w-[1200px] mx-auto p-6">
    <h1 class="text-2xl font-semibold text-[#1A1A1A] mb-6">资产</h1>

    <!-- Filter Tabs -->
    <div class="flex gap-4 border-b border-[#E5E5E5] mb-6">
        <a 
            href="?tab=assets&filter=all"
            class="pb-3 px-1 text-sm font-medium transition-all duration-200 relative <?= $activeFilter === 'all' ? 'text-[#3B82F6]' : 'text-[#666666] hover:text-[#1A1A1A]' ?>"
        >
            全部
            <?php if ($activeFilter === 'all'): ?>
                <span class="absolute bottom-0 left-0 right-0 h-0.5 bg-[#3B82F6] rounded-full"></span>
            <?php endif; ?>
        </a>
        <a 
            href="?tab=assets&filter=image"
            class="pb-3 px-1 text-sm font-medium transition-all duration-200 relative flex items-center gap-2 <?= $activeFilter === 'image' ? 'text-[#3B82F6]' : 'text-[#666666] hover:text-[#1A1A1A]' ?>"
        >
            <i data-lucide="image" class="w-4 h-4"></i>
            图片
            <?php if ($activeFilter === 'image'): ?>
                <span class="absolute bottom-0 left-0 right-0 h-0.5 bg-[#3B82F6] rounded-full"></span>
            <?php endif; ?>
        </a>
        <a 
            href="?tab=assets&filter=video"
            class="pb-3 px-1 text-sm font-medium transition-all duration-200 relative flex items-center gap-2 <?= $activeFilter === 'video' ? 'text-[#3B82F6]' : 'text-[#666666] hover:text-[#1A1A1A]' ?>"
        >
            <i data-lucide="video" class="w-4 h-4"></i>
            视频
            <?php if ($activeFilter === 'video'): ?>
                <span class="absolute bottom-0 left-0 right-0 h-0.5 bg-[#3B82F6] rounded-full"></span>
            <?php endif; ?>
        </a>
    </div>

    <!-- History Grid -->
    <?php if (empty($currentUser)): ?>
        <div class="text-center py-20 text-[#666666]">
            <p class="mb-3">登录后可查看你的资产</p>
            <button type="button" onclick="openAuthDialog('login')" class="h-9 px-4 text-sm font-medium bg-[#3B82F6] hover:bg-[#2563EB] text-white rounded-lg transition-colors">
                去登录
            </button>
        </div>
    <?php elseif (count($filteredHistory) > 0): ?>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
            <?php foreach ($filteredHistory as $item): 
                $mediaUrl = htmlspecialchars((string)($item['image'] ?? ''));
                $isVideo = ($item['type'] ?? '') === 'video';
                $ext = $isVideo ? 'mp4' : 'jpg';
                $downloadName = ($item['type'] ?? 'file') . '-' . ($item['id'] ?? '') . '.' . $ext;
            ?>
                <div class="group bg-white rounded-xl overflow-hidden shadow-md hover:shadow-lg transition-all duration-300">
                    <div class="relative aspect-[3/4] bg-[#F7F7F7] asset-media-wrap cursor-pointer" data-asset-url="<?= $mediaUrl ?>" data-asset-video="<?= $isVideo ? '1' : '0' ?>" onclick="var u=this.getAttribute('data-asset-url');var v=this.getAttribute('data-asset-video')==='1';if(u)openAssetPreview(u,v)">
                        <?php if ($isVideo): ?>
                            <video
                                src="<?= $mediaUrl ?>"
                                class="w-full h-full object-cover pointer-events-none"
                                muted
                                playsinline
                                preload="metadata"
                                onerror="this.style.display='none';this.insertAdjacentHTML('afterend','<div class=&quot;w-full h-full flex items-center justify-center text-xs text-[#999999]&quot;>视频资源已失效</div>');"
                            ></video>
                        <?php else: ?>
                            <img
                                src="<?= $mediaUrl ?>"
                                alt="<?= htmlspecialchars($item['title']) ?>"
                                class="w-full h-full object-cover pointer-events-none"
                                loading="lazy"
                                onerror="this.style.display='none';this.insertAdjacentHTML('afterend','<div class=&quot;w-full h-full flex items-center justify-center text-xs text-[#999999]&quot;>图片资源已失效</div>');"
                            />
                        <?php endif; ?>
                        <div class="absolute top-2 left-2">
                            <span class="px-2 py-0.5 text-[10px] rounded backdrop-blur-sm text-white <?= $item['type'] === 'image' ? 'bg-blue-500/80' : 'bg-purple-500/80' ?>">
                                <?= $item['type'] === 'image' ? '图片' : '视频' ?>
                            </span>
                        </div>
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition-all duration-200 flex items-center justify-center gap-2 opacity-0 group-hover:opacity-100 pointer-events-none group-hover:pointer-events-auto">
                            <button type="button" data-asset-url="<?= $mediaUrl ?>" data-asset-video="<?= $isVideo ? '1' : '0' ?>" class="h-9 px-3 rounded-lg bg-white/90 hover:bg-white text-sm font-medium text-[#1A1A1A] flex items-center gap-1.5" onclick="event.stopPropagation();openAssetPreview(this.getAttribute('data-asset-url'),this.getAttribute('data-asset-video')==='1')">
                                <i data-lucide="eye" class="w-4 h-4"></i>查看
                            </button>
                            <a href="<?= $mediaUrl ?>" download="<?= htmlspecialchars($downloadName) ?>" class="h-9 px-3 rounded-lg bg-white/90 hover:bg-white text-sm font-medium text-[#1A1A1A] flex items-center gap-1.5" onclick="event.stopPropagation()">
                                <i data-lucide="download" class="w-4 h-4"></i>下载
                            </a>
                        </div>
                    </div>
                    <div class="p-3">
                        <p class="text-sm font-medium text-[#1A1A1A] mb-1 line-clamp-1">
                            <?= htmlspecialchars($item['title']) ?>
                        </p>
                        <p class="text-xs text-[#666666] mb-1 line-clamp-1">
                            <?= htmlspecialchars($item['model']) ?>
                        </p>
                        <p class="text-xs text-[#999999]">
                            <?= htmlspecialchars($item['createdAt']) ?>
                        </p>
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
</script>
