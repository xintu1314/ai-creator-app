<?php
// 模拟历史记录数据
$historyItems = [
    [
        'id' => 'hist-1',
        'title' => '生成的图片1',
        'image' => 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=400&h=500&fit=crop',
        'type' => 'image',
        'model' => 'banana pro',
        'prompt' => '一个美丽的风景画',
        'createdAt' => '2026-02-05 10:30',
    ],
    [
        'id' => 'hist-2',
        'title' => '生成的视频1',
        'image' => 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=400&h=500&fit=crop',
        'type' => 'video',
        'model' => '可灵',
        'prompt' => '一只蝴蝶在花丛中飞舞',
        'createdAt' => '2026-02-05 09:15',
    ],
    [
        'id' => 'hist-3',
        'title' => '生成的图片2',
        'image' => 'https://images.unsplash.com/photo-1576919228236-a097c32a5cd4?w=400&h=500&fit=crop',
        'type' => 'image',
        'model' => 'banana pro',
        'prompt' => '圣诞主题的海报设计',
        'createdAt' => '2026-02-04 16:20',
    ],
];

$activeFilter = $_GET['filter'] ?? 'all';
$filteredHistory = $activeFilter === 'all' 
    ? $historyItems 
    : array_filter($historyItems, function($item) use ($activeFilter) {
        return $item['type'] === $activeFilter;
    });
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
    <?php if (count($filteredHistory) > 0): ?>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
            <?php foreach ($filteredHistory as $item): ?>
                <div class="group cursor-pointer bg-white rounded-xl overflow-hidden shadow-md hover:shadow-lg transition-all duration-300">
                    <div class="relative aspect-[3/4]">
                        <img
                            src="<?= htmlspecialchars($item['image']) ?>"
                            alt="<?= htmlspecialchars($item['title']) ?>"
                            class="w-full h-full object-cover"
                        />
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
