<div class="mt-8">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-base font-medium text-[#1A1A1A]">来试试一键做同款</h3>
        <button 
            onclick="openTemplateSheet()"
            class="flex items-center text-sm text-[#666666] hover:text-[#3B82F6] transition-colors"
        >
            查看更多
            <i data-lucide="chevron-right" class="w-4 h-4 ml-0.5"></i>
        </button>
    </div>

    <!-- Cards Grid - Horizontal Scroll -->
    <div class="flex gap-4 overflow-x-auto pb-2 scrollbar-hide" id="template-cards-container">
        <?php foreach ($templates as $template): ?>
            <?php
            $mediaUrl = (string)($template['image'] ?? '');
            $isVideoType = (($template['type'] ?? 'image') === 'video');
            // Some seeds store thumbnail images for video templates. Only render <video> when it's a real video URL.
            $looksLikeVideo = $mediaUrl !== '' && preg_match('/\.(mp4|webm|mov|m3u8)(\?|$)/i', $mediaUrl);
            $renderAsVideo = $isVideoType && $looksLikeVideo;
            ?>
            <div class="flex-shrink-0 w-[160px] group template-card cursor-pointer" data-template-id="<?= $template['id'] ?>" onclick="useTemplate(<?= htmlspecialchars(json_encode($template, JSON_UNESCAPED_UNICODE)) ?>)">
                <div class="relative w-full h-[200px] rounded-xl overflow-hidden transition-all duration-300 shadow-md group-hover:shadow-lg group-hover:-translate-y-1">
                    <?php if ($renderAsVideo): ?>
                        <video
                            src="<?= htmlspecialchars($mediaUrl) ?>"
                            class="w-full h-full object-cover"
                            muted
                            playsinline
                            preload="metadata"
                        ></video>
                    <?php else: ?>
                        <img
                            src="<?= htmlspecialchars($mediaUrl) ?>"
                            alt="<?= htmlspecialchars($template['title']) ?>"
                            class="w-full h-full object-cover"
                        />
                    <?php endif; ?>
                    
                    <!-- Model Tag -->
                    <div class="absolute top-2 left-2">
                        <span class="px-2 py-0.5 text-[10px] bg-black/60 text-white rounded backdrop-blur-sm">
                            <?= htmlspecialchars($template['model']) ?>
                        </span>
                    </div>

                    <!-- Title Overlay -->
                    <div class="absolute bottom-0 left-0 right-0 p-2 bg-gradient-to-t from-black/70 to-transparent">
                        <p class="text-xs text-white line-clamp-2"><?= htmlspecialchars($template['title']) ?></p>
                    </div>

                    <!-- Hover Overlay with Action Button - pointer-events-none 未悬停时让点击穿透 -->
                    <div class="absolute inset-0 bg-black/40 flex items-end justify-center p-3 opacity-0 group-hover:opacity-100 transition-all duration-250 pointer-events-none group-hover:pointer-events-auto">
                        <button
                            onclick="event.stopPropagation(); useTemplate(<?= htmlspecialchars(json_encode($template, JSON_UNESCAPED_UNICODE)) ?>)"
                            class="w-full py-2 text-sm font-medium text-[#1A1A1A] bg-white rounded-lg transform translate-y-full group-hover:translate-y-0 transition-all duration-250"
                        >
                            做同款
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
