<?php
$activeTab = $_GET['inspiration_tab'] ?? 'all';
$selectedCategory = $_GET['category'] ?? '全部';

$categories = ['全部', '室内', '景观', '建筑', '电商', '人物', '动物', '自然'];

$allTemplates = array_merge($imageTemplates, $videoTemplates);

$displayTemplates = $activeTab === 'all' 
    ? $allTemplates 
    : ($activeTab === 'image' 
        ? $imageTemplates 
        : $videoTemplates);

$filteredTemplates = $selectedCategory === '全部' 
    ? $displayTemplates 
    : array_filter($displayTemplates, function($t) use ($selectedCategory) {
        return ($t['category'] ?? '') === $selectedCategory;
    });
?>
<div class="flex-1 p-6 overflow-auto">
    <!-- Header -->
    <div class="max-w-[1200px] mx-auto mb-6">
        <h1 class="text-2xl font-semibold text-[#1A1A1A] mb-4">灵感库</h1>
        
        <!-- Tabs -->
        <div class="flex gap-4 border-b border-[#E5E5E5] mb-4">
            <a 
                href="?tab=inspiration&inspiration_tab=all&category=<?= urlencode($selectedCategory) ?>"
                class="pb-3 px-1 text-sm font-medium transition-all duration-200 relative <?= $activeTab === 'all' ? 'text-[#3B82F6]' : 'text-[#666666] hover:text-[#1A1A1A]' ?>"
            >
                全部
                <?php if ($activeTab === 'all'): ?>
                    <span class="absolute bottom-0 left-0 right-0 h-0.5 bg-[#3B82F6] rounded-full"></span>
                <?php endif; ?>
            </a>
            <a 
                href="?tab=inspiration&inspiration_tab=image&category=<?= urlencode($selectedCategory) ?>"
                class="pb-3 px-1 text-sm font-medium transition-all duration-200 relative flex items-center gap-2 <?= $activeTab === 'image' ? 'text-[#3B82F6]' : 'text-[#666666] hover:text-[#1A1A1A]' ?>"
            >
                <i data-lucide="image" class="w-4 h-4"></i>
                图片模板
                <?php if ($activeTab === 'image'): ?>
                    <span class="absolute bottom-0 left-0 right-0 h-0.5 bg-[#3B82F6] rounded-full"></span>
                <?php endif; ?>
            </a>
            <a 
                href="?tab=inspiration&inspiration_tab=video&category=<?= urlencode($selectedCategory) ?>"
                class="pb-3 px-1 text-sm font-medium transition-all duration-200 relative flex items-center gap-2 <?= $activeTab === 'video' ? 'text-[#3B82F6]' : 'text-[#666666] hover:text-[#1A1A1A]' ?>"
            >
                <i data-lucide="video" class="w-4 h-4"></i>
                视频模板
                <?php if ($activeTab === 'video'): ?>
                    <span class="absolute bottom-0 left-0 right-0 h-0.5 bg-[#3B82F6] rounded-full"></span>
                <?php endif; ?>
            </a>
        </div>

        <!-- Category Filter -->
        <div class="flex items-center gap-2 overflow-x-auto">
            <?php foreach ($categories as $category): ?>
                <a
                    href="?tab=inspiration&inspiration_tab=<?= urlencode($activeTab) ?>&category=<?= urlencode($category) ?>"
                    class="px-4 py-1.5 text-sm font-medium rounded-lg whitespace-nowrap transition-colors <?= $selectedCategory === $category ? 'bg-[#3B82F6] text-white' : 'bg-[#F5F5F5] text-[#666666] hover:bg-[#E5E5E5]' ?>"
                >
                    <?= htmlspecialchars($category) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Masonry Grid -->
    <div class="max-w-[1200px] mx-auto">
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">
            <?php foreach ($filteredTemplates as $template): ?>
                <div
                    class="group cursor-pointer"
                    onclick="useTemplate(<?= htmlspecialchars(json_encode($template, JSON_UNESCAPED_UNICODE)) ?>)"
                >
                    <div class="relative rounded-xl overflow-hidden shadow-md group-hover:shadow-lg transition-all duration-300 aspect-[3/4]">
                        <?php if (($template['type'] ?? 'image') === 'video'): ?>
                            <video
                                src="<?= htmlspecialchars($template['image']) ?>"
                                class="w-full h-full object-cover"
                                muted
                                playsinline
                                preload="metadata"
                            ></video>
                        <?php else: ?>
                            <img
                                src="<?= htmlspecialchars($template['image']) ?>"
                                alt="<?= htmlspecialchars($template['title']) ?>"
                                class="w-full h-full object-cover"
                            />
                        <?php endif; ?>
                        
                        <!-- Model Tag -->
                        <div class="absolute top-1.5 left-1.5">
                            <span class="px-1.5 py-0.5 text-[9px] bg-black/60 text-white rounded backdrop-blur-sm">
                                <?= htmlspecialchars($template['model']) ?>
                            </span>
                        </div>

                        <!-- Type Badge -->
                        <div class="absolute top-1.5 right-1.5">
                            <span class="px-1.5 py-0.5 text-[9px] rounded backdrop-blur-sm <?= $template['type'] === 'image' ? 'bg-blue-500/80 text-white' : 'bg-purple-500/80 text-white' ?>">
                                <?= $template['type'] === 'image' ? '图片' : '视频' ?>
                            </span>
                        </div>

                        <!-- Title Overlay -->
                        <div class="absolute bottom-0 left-0 right-0 p-1.5 bg-gradient-to-t from-black/80 to-transparent">
                            <p class="text-[10px] text-white font-medium line-clamp-2 leading-tight">
                                <?= htmlspecialchars($template['title']) ?>
                            </p>
                        </div>

                        <!-- Hover Overlay -->
                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity duration-250 flex items-center justify-center">
                            <button class="px-4 py-1.5 text-xs font-medium text-white bg-white/20 backdrop-blur-sm rounded-lg hover:bg-white/30 transition-colors">
                                做同款
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
function useTemplate(template) {
    // 切换到创作页面并使用模板
    if (template.type === 'image' || template.type === 'video') {
        window.location.href = '?tab=create&type=' + template.type;
    }
}
</script>
