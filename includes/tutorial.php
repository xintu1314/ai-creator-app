<?php
// 从共享数据层加载教程（与 api/tutorials 同源）
$tutorials = get_tutorials();
?>
<div class="max-w-[1200px] mx-auto p-6">
    <h1 class="text-2xl font-semibold text-[#1A1A1A] mb-6">教程</h1>

    <!-- Tutorials List -->
    <?php if (count($tutorials) > 0): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($tutorials as $tutorial): ?>
                <div class="bg-white rounded-lg overflow-hidden shadow-md hover:shadow-lg transition-all duration-300 cursor-pointer">
                    <!-- Video Preview -->
                    <div class="relative aspect-video bg-[#F5F5F5]">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <i data-lucide="play" class="w-16 h-16 text-white bg-black/50 rounded-full p-4"></i>
                        </div>
                    </div>
                    
                    <!-- Content -->
                    <div class="p-4">
                        <h3 class="text-lg font-medium text-[#1A1A1A] mb-2 line-clamp-2">
                            <?= htmlspecialchars($tutorial['title']) ?>
                        </h3>
                        <p class="text-sm text-[#666666] line-clamp-3">
                            <?= htmlspecialchars($tutorial['description']) ?>
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-20 text-[#666666] bg-white rounded-lg border border-[#E5E5E5]">
            <p>暂无教程内容</p>
            <p class="text-sm mt-2">教程内容将通过后台管理上传</p>
        </div>
    <?php endif; ?>
</div>
