<?php
$models = $creationType === 'image' ? $imageModels : $videoModels;
$currentModel = $models[0] ?? null;
$defaultAspectRatio = $creationType === 'image' ? '3:4' : '16:9';
$assetHistory = $assetHistory ?? [];
?>
<div id="creation-wrapper" class="flex-1 flex flex-col h-full">
    <div id="generation-area" class="hidden flex-1 overflow-y-auto px-6 pt-6 pb-2">
        <div class="max-w-[900px] mx-auto">
            <?php if (count($assetHistory) > 0): ?>
            <div id="generation-history" class="space-y-6 mb-6">
                <?php foreach ($assetHistory as $item):
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
            <?php endif; ?>
            <div id="generation-messages" class="space-y-6"></div>
        </div>
    </div>

    <div id="creation-title" class="text-center pt-6 pb-2 px-6">
        <h1 class="text-2xl font-semibold text-[#1A1A1A] mb-3">创作</h1>
    </div>

    <div id="creation-input-wrapper" class="px-6 pb-6 pt-2">
        <?php include __DIR__ . '/creation_input.php'; ?>
    </div>
</div>
