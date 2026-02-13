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
            <?php foreach ($filteredHistory as $item): ?>
                <div class="group cursor-pointer bg-white rounded-xl overflow-hidden shadow-md hover:shadow-lg transition-all duration-300">
                    <div class="relative aspect-[3/4] bg-[#F7F7F7] asset-media-wrap">
                        <?php if (($item['type'] ?? '') === 'video'): ?>
                            <video
                                src="<?= htmlspecialchars($item['image']) ?>"
                                class="w-full h-full object-cover"
                                muted
                                playsinline
                                preload="metadata"
                                onerror="this.style.display='none';this.insertAdjacentHTML('afterend','<div class=&quot;w-full h-full flex items-center justify-center text-xs text-[#999999]&quot;>视频资源已失效</div>');"
                            ></video>
                        <?php else: ?>
                            <img
                                src="<?= htmlspecialchars($item['image']) ?>"
                                alt="<?= htmlspecialchars($item['title']) ?>"
                                class="w-full h-full object-cover"
                                loading="lazy"
                                onerror="this.style.display='none';this.insertAdjacentHTML('afterend','<div class=&quot;w-full h-full flex items-center justify-center text-xs text-[#999999]&quot;>图片资源已失效</div>');"
                            />
                        <?php endif; ?>
                        <div class="absolute top-2 left-2">
                            <span class="px-2 py-0.5 text-[10px] rounded backdrop-blur-sm text-white <?= $item['type'] === 'image' ? 'bg-blue-500/80' : 'bg-purple-500/80' ?>">
                                <?= $item['type'] === 'image' ? '图片' : '视频' ?>
                            </span>
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
