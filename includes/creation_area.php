<?php
// $imageModels, $videoModels 由 index.php 从 api/data 加载
$models = $creationType === 'image' ? $imageModels : $videoModels;
$currentModel = $models[0]; // 默认第一个模型
$defaultAspectRatio = $creationType === 'image' ? '3:4' : '16:9';
?>
<div id="creation-wrapper" class="flex-1 flex flex-col h-full">
    <!-- ========== 生成结果/进度区域（初始隐藏，点击生成后显示） ========== -->
    <div id="generation-area" class="hidden flex-1 overflow-y-auto px-6 pt-6 pb-2">
        <div class="max-w-[900px] mx-auto">
            <div id="generation-messages" class="space-y-6"></div>
        </div>
    </div>

    <!-- ========== 标题区域（生成中隐藏） ========== -->
    <div id="creation-title" class="text-center pt-6 pb-2 px-6">
        <h1 class="text-2xl font-semibold text-[#1A1A1A] mb-3">
            <?= $creationType === 'image' ? '图片创作' : '视频创作' ?>
        </h1>
    </div>

    <!-- ========== 输入卡片区域（始终显示，生成中贴底） ========== -->
    <div id="creation-input-wrapper" class="px-6 pb-6 pt-2">
        <div id="creation-card" class="max-w-[900px] mx-auto bg-white rounded-2xl shadow-md p-6">
            <!-- Tabs -->
            <div class="flex gap-6 mb-5 border-b border-[#E5E5E5]">
                <button onclick="changeType('image')" class="flex items-center gap-2 pb-3 text-sm font-medium transition-all duration-200 relative <?= $creationType === 'image' ? 'text-[#3B82F6]' : 'text-[#666666] hover:text-[#1A1A1A]' ?>">
                    <i data-lucide="image" class="w-4 h-4"></i>
                    图片生成
                    <?php if ($creationType === 'image'): ?>
                        <span class="absolute bottom-0 left-0 right-0 h-0.5 bg-[#3B82F6] rounded-full"></span>
                    <?php endif; ?>
                </button>
                <button onclick="changeType('video')" class="flex items-center gap-2 pb-3 text-sm font-medium transition-all duration-200 relative <?= $creationType === 'video' ? 'text-[#3B82F6]' : 'text-[#666666] hover:text-[#1A1A1A]' ?>">
                    <i data-lucide="video" class="w-4 h-4"></i>
                    视频生成
                    <?php if ($creationType === 'video'): ?>
                        <span class="absolute bottom-0 left-0 right-0 h-0.5 bg-[#3B82F6] rounded-full"></span>
                    <?php endif; ?>
                </button>
            </div>

            <!-- Input Area -->
            <?php if ($creationType === 'video'): ?>
                <div class="mb-4">
                    <div class="flex flex-col sm:flex-row sm:flex-nowrap items-stretch gap-3 min-w-0">
                        <div class="flex gap-3 flex-shrink-0 justify-center sm:justify-start">
                            <!-- First Frame -->
                            <div id="first-frame-drop" class="w-[80px] h-[80px] sm:w-[84px] sm:h-[84px] overflow-hidden border-2 border-dashed border-[#E5E5E5] rounded-lg flex flex-col items-center justify-center cursor-pointer hover:border-[#3B82F6] hover:bg-[#F0F7FF] transition-all duration-200 relative flex-shrink-0">
                                <input
                                    type="file"
                                    accept="image/*"
                                    id="first-frame-input"
                                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                    onchange="handleFrameUpload(this, 'first-frame-preview', 'first-frame')"
                                />
                                <div id="first-frame-preview" class="w-full h-full flex flex-col items-center justify-center min-w-0 min-h-0 p-1">
                                    <i data-lucide="plus" class="w-5 h-5 text-[#999999] mb-0.5 flex-shrink-0"></i>
                                    <span class="text-[10px] text-[#999999] leading-tight text-center">首帧</span>
                                    <span class="text-[9px] text-[#BBBBBB] leading-tight text-center mt-0.5">点击或拖拽上传</span>
                                </div>
                            </div>

                            <!-- Last Frame -->
                            <div id="last-frame-drop" class="w-[80px] h-[80px] sm:w-[84px] sm:h-[84px] overflow-hidden border-2 border-dashed border-[#E5E5E5] rounded-lg flex flex-col items-center justify-center cursor-pointer hover:border-[#3B82F6] hover:bg-[#F0F7FF] transition-all duration-200 relative flex-shrink-0">
                                <input
                                    type="file"
                                    accept="image/*"
                                    id="last-frame-input"
                                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                    onchange="handleFrameUpload(this, 'last-frame-preview', 'last-frame')"
                                />
                                <div id="last-frame-preview" class="w-full h-full flex flex-col items-center justify-center min-w-0 min-h-0 p-1">
                                    <i data-lucide="plus" class="w-5 h-5 text-[#999999] mb-0.5 flex-shrink-0"></i>
                                    <span class="text-[10px] text-[#999999] leading-tight text-center">尾帧</span>
                                    <span class="text-[9px] text-[#BBBBBB] leading-tight text-center mt-0.5">点击或拖拽上传</span>
                                </div>
                            </div>
                        </div>

                        <!-- Text Input (story description) -->
                        <div class="flex-1 min-w-0 min-h-[80px] sm:min-h-[84px]">
                            <textarea
                                id="prompt-input"
                                placeholder="试试描述一段简短的故事情节，最关键的是主体、环境、时间、风格"
                                class="w-full h-[80px] sm:h-[84px] p-3 text-sm text-[#1A1A1A] placeholder:text-[#999999] border border-[#E5E5E5] rounded-lg resize-none focus:outline-none focus:border-[#3B82F6] transition-colors"
                            ></textarea>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="mb-4">
                    <div class="flex flex-col sm:flex-row sm:flex-nowrap items-stretch gap-3 min-w-0">
                        <!-- 多张参考图上传（上传到 OSS） -->
                        <div class="flex gap-2 items-start flex-shrink-0 justify-center sm:justify-start">
                            <div 
                                id="ref-images-upload"
                                class="w-[80px] h-[80px] sm:w-[84px] sm:h-[84px] min-w-[80px] min-h-[80px] sm:min-w-[84px] sm:min-h-[84px] max-w-[80px] max-h-[80px] sm:max-w-[84px] sm:max-h-[84px] overflow-hidden border-2 border-dashed border-[#E5E5E5] rounded-lg flex flex-col items-center justify-center cursor-pointer hover:border-[#3B82F6] hover:bg-[#F0F7FF] transition-all duration-200 flex-shrink-0"
                                onclick="document.getElementById('ref-images-input').click()"
                            >
                                <input type="file" id="ref-images-input" accept="image/*" multiple class="hidden" onchange="handleRefImagesUpload(this)">
                                <i data-lucide="plus" class="w-5 h-5 text-[#999999] mb-0.5 flex-shrink-0"></i>
                                <span class="text-[10px] text-[#999999] leading-tight text-center">添加</span>
                                <span class="text-[9px] text-[#BBBBBB] leading-tight text-center mt-0.5">点击或拖拽</span>
                            </div>
                            <div id="ref-images-preview" class="flex flex-wrap gap-2 max-w-[240px] max-h-[84px] overflow-y-auto content-start"></div>
                        </div>

                        <!-- Text Input -->
                        <div class="flex-1 min-w-0 min-h-[80px] sm:min-h-[84px]">
                            <textarea
                                id="prompt-input"
                                placeholder="输入图片生成的提示词，例如：浩瀚的银河中一艘宇宙飞船驶过"
                                class="w-full h-[80px] sm:h-[84px] p-3 text-sm text-[#1A1A1A] placeholder:text-[#999999] border border-[#E5E5E5] rounded-lg resize-none focus:outline-none focus:border-[#3B82F6] transition-colors"
                            ></textarea>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Parameters Bar -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <!-- Model Selector -->
                    <button
                        onclick="openModelDialog()"
                        class="flex items-center gap-2 px-3 py-1.5 text-sm text-[#1A1A1A] bg-[#F5F5F5] rounded-lg hover:bg-[#E5E5E5] transition-colors"
                    >
                        <div class="w-4 h-4 rounded bg-gradient-to-br from-blue-400 to-blue-600"></div>
                        <span id="selected-model"><?= htmlspecialchars($currentModel['name']) ?></span>
                        <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-[#666666]"></i>
                    </button>

                    <!-- Aspect Ratio & Count / Video Ratio & Duration -->
                    <?php if ($creationType === 'image'): ?>
                        <button
                            onclick="openParamsDialog()"
                            class="flex items-center gap-2 px-3 py-1.5 text-sm text-[#1A1A1A] bg-[#F5F5F5] rounded-lg hover:bg-[#E5E5E5] transition-colors"
                        >
                            <span id="aspect-ratio"><?= $defaultAspectRatio ?></span>
                            <span class="text-[#999999]">·</span>
                            <span id="image-count">1张</span>
                            <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-[#666666]"></i>
                        </button>
                    <?php else: ?>
                        <button
                            onclick="openVideoParamsDialog()"
                            class="flex items-center gap-2 px-3 py-1.5 text-sm text-[#1A1A1A] bg-[#F5F5F5] rounded-lg hover:bg-[#E5E5E5] transition-colors"
                        >
                            <span id="video-aspect-ratio"><?= $defaultAspectRatio ?></span>
                            <span class="text-[#999999]">·</span>
                            <span id="video-duration">5s</span>
                            <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-[#666666]"></i>
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Generate Button + Points Badge -->
                <div class="flex items-center gap-2">
                    <button 
                        id="generate-btn"
                        onclick="handleGenerate()"
                        class="h-9 px-8 text-sm font-medium bg-[#3B82F6] hover:bg-[#2563EB] text-white rounded-lg transition-all duration-200 hover:scale-[1.02] flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                        </svg>
                        生成
                    </button>
                    <span id="generate-points-badge" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-[#64748B] bg-[#F1F5F9] rounded-lg border border-[#E2E8F0]">
                        <svg class="w-3.5 h-3.5 text-amber-500" viewBox="0 0 24 24" fill="currentColor"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                        <span id="generate-points-value">--</span>
                    </span>
                </div>
            </div>
        </div>

        <!-- 生成状态指示器（有任务生成中时显示） -->
        <div id="gen-status-bar" class="hidden max-w-[900px] mx-auto mt-2 flex items-center justify-center gap-2 text-xs text-[#999]">
            <div class="w-4 h-4 border-2 border-[#3B82F6] border-t-transparent rounded-full animate-spin"></div>
            <span id="gen-status-text">0/1 生成中...</span>
            <button onclick="scrollToLatestGeneration()" class="text-[#3B82F6] hover:underline ml-2">回到底部 ↓</button>
        </div>
    </div>
</div>

<script>
// 存储模型数据供JS使用
window.modelsData = <?= json_encode($models, JSON_UNESCAPED_UNICODE) ?>;
window.currentCreationType = '<?= $creationType ?>';
document.addEventListener('DOMContentLoaded', function() { if (typeof updateGeneratePointsDisplay === 'function') updateGeneratePointsDisplay(); });
</script>
