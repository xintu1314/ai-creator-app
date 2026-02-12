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
                    <div class="relative aspect-video bg-[#F5F5F5]" <?= !empty($tutorial['videoUrl']) ? 'onclick="openTutorialVideoPlayer(\'' . htmlspecialchars($tutorial['videoUrl'], ENT_QUOTES) . '\', \'' . htmlspecialchars($tutorial['title'], ENT_QUOTES) . '\')"' : '' ?>>
                        <?php if (!empty($tutorial['coverUrl'])): ?>
                            <img src="<?= htmlspecialchars($tutorial['coverUrl']) ?>" alt="<?= htmlspecialchars($tutorial['title']) ?>" class="w-full h-full object-cover" />
                        <?php endif; ?>
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
                        <?php if (!empty($tutorial['videoUrl'])): ?>
                            <button type="button" onclick="openTutorialVideoPlayer('<?= htmlspecialchars($tutorial['videoUrl'], ENT_QUOTES) ?>', '<?= htmlspecialchars($tutorial['title'], ENT_QUOTES) ?>')" class="inline-flex mt-3 text-xs text-[#3B82F6] hover:text-[#2563EB]">
                                查看视频
                            </button>
                        <?php endif; ?>
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

<!-- 教程视频播放弹层 -->
<div id="tutorial-video-dialog" class="hidden fixed inset-0 z-50 bg-black/70 items-center justify-center p-4" onclick="closeTutorialVideoPlayer()" style="display:none;">
    <div class="w-full max-w-[960px] bg-black rounded-xl overflow-hidden" onclick="event.stopPropagation()">
        <div class="h-11 px-4 flex items-center justify-between bg-black/70 border-b border-white/10">
            <div id="tutorial-video-title" class="text-sm text-white font-medium truncate">教程视频</div>
            <button type="button" onclick="closeTutorialVideoPlayer()" class="h-8 px-3 text-xs text-white/90 hover:text-white hover:bg-white/10 rounded">关闭</button>
        </div>
        <div class="aspect-video bg-black">
            <video id="tutorial-video-player" controls playsinline class="w-full h-full bg-black"></video>
        </div>
    </div>
</div>

<script>
function openTutorialVideoPlayer(url, title) {
    const dialog = document.getElementById('tutorial-video-dialog');
    const player = document.getElementById('tutorial-video-player');
    const titleEl = document.getElementById('tutorial-video-title');
    if (!dialog || !player) return;
    if (titleEl) titleEl.textContent = title || '教程视频';
    player.src = url || '';
    dialog.classList.remove('hidden');
    dialog.style.display = 'flex';
    const p = player.play();
    if (p && typeof p.catch === 'function') p.catch(() => {});
}

function closeTutorialVideoPlayer() {
    const dialog = document.getElementById('tutorial-video-dialog');
    const player = document.getElementById('tutorial-video-player');
    if (!dialog || !player) return;
    player.pause();
    player.removeAttribute('src');
    player.load();
    dialog.classList.add('hidden');
    dialog.style.display = 'none';
}
</script>
