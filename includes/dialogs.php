<?php
// 模型选择对话框
$models = $creationType === 'image' ? $imageModels : $videoModels;
?>
<!-- Model Select Dialog -->
<div id="model-dialog" class="hidden fixed inset-0 z-50 dialog-overlay" onclick="closeModelDialog()" style="display: none;">
    <div class="dialog-content max-w-[680px] p-0 gap-0 overflow-hidden" onclick="event.stopPropagation()">
        <div class="px-6 py-4 border-b border-[#E5E5E5]">
            <h2 class="text-base font-medium text-[#1A1A1A]">选择模型</h2>
        </div>
        
        <div class="p-4 max-h-[500px] overflow-y-auto">
            <div class="grid grid-cols-2 gap-3">
                <?php foreach ($models as $model): ?>
                    <button
                        onclick="selectModel('<?= htmlspecialchars($model['id']) ?>', '<?= htmlspecialchars($model['name']) ?>')"
                        class="text-left p-4 rounded-xl border border-[#E5E5E5] bg-[#F9FAFB] transition-all duration-200 hover:border-[#3B82F6] hover:bg-[#F0F7FF] model-option"
                        data-model-id="<?= htmlspecialchars($model['id']) ?>"
                    >
                        <div class="flex items-start gap-3">
                            <!-- Model Icon -->
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 bg-gradient-to-br <?= $model['icon'] === 'banana' ? 'from-yellow-400 to-yellow-600' : ($model['icon'] === 'doubao' ? 'from-emerald-500 to-teal-600' : ($model['icon'] === 'sora' ? 'from-purple-500 to-pink-500' : 'from-blue-400 to-blue-600')) ?>">
                                <?php
                                $iconHtml = '';
                                switch($model['icon']) {
                                    case 'seedream':
                                        $iconHtml = '<div class="w-5 h-5 bg-white/90 rounded flex items-center justify-center"><div class="w-3 h-3 bg-gradient-to-r from-blue-400 to-blue-600 rounded-sm"></div></div>';
                                        break;
                                    case 'universal':
                                        $iconHtml = '<div class="w-5 h-5 bg-amber-400 rounded-full"></div>';
                                        break;
                                    case 'qwen':
                                        $iconHtml = '<div class="w-5 h-5 bg-purple-500 rounded-lg"></div>';
                                        break;
                                    case 'ai':
                                        $iconHtml = '<div class="w-5 h-5 bg-pink-500 rounded-full"></div>';
                                        break;
                                    case 'base':
                                        $iconHtml = '<div class="w-5 h-5 bg-orange-500 rounded-lg"></div>';
                                        break;
                                    case 'zimage':
                                        $iconHtml = '<div class="w-5 h-5 bg-indigo-500 rounded-lg"></div>';
                                        break;
                                    case 'pixverse':
                                        $iconHtml = '<div class="w-5 h-5 bg-cyan-400 rounded-full"></div>';
                                        break;
                                    case 'kling':
                                        $iconHtml = '<div class="w-5 h-5 bg-blue-500 rounded-full"></div>';
                                        break;
                                    case 'tongyi':
                                        $iconHtml = '<div class="w-5 h-5 bg-purple-600 rounded-lg"></div>';
                                        break;
                                    case 'vidu':
                                        $iconHtml = '<div class="w-5 h-5 bg-orange-400 rounded-lg"></div>';
                                        break;
                                    case 'hailuo':
                                        $iconHtml = '<div class="w-5 h-5 bg-red-500 rounded-full"></div>';
                                        break;
                                    case 'banana':
                                        $iconHtml = '<div class="w-5 h-5 bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-lg"></div>';
                                        break;
                                    case 'doubao':
                                        $iconHtml = '<div class="w-5 h-5 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-lg"></div>';
                                        break;
                                    case 'sora':
                                        $iconHtml = '<div class="w-5 h-5 bg-gradient-to-br from-purple-500 to-pink-500 rounded-lg"></div>';
                                        break;
                                    default:
                                        $iconHtml = '<div class="w-5 h-5 bg-blue-500 rounded-lg"></div>';
                                }
                                echo $iconHtml;
                                ?>
                            </div>
                            
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-medium text-[#1A1A1A] text-sm"><?= htmlspecialchars($model['name']) ?></span>
                                    <?php if (!empty($model['isNew'])): ?>
                                        <span class="h-4 px-1.5 text-[10px] bg-amber-100 text-amber-600 rounded border-0">NEW</span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-xs text-[#666666] line-clamp-2 mb-2"><?= htmlspecialchars($model['description']) ?></p>
                                
                                <!-- Tags -->
                                <div class="flex flex-wrap gap-1.5">
                                    <?php foreach ($model['tags'] as $tag): ?>
                                        <span class="px-2 py-0.5 text-[10px] bg-white border border-[#E5E5E5] rounded text-[#666666]">
                                            <?= htmlspecialchars($tag) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Params Dialog -->
<div id="params-dialog" class="hidden fixed inset-0 z-50 dialog-overlay" onclick="closeParamsDialog()" style="display: none;">
    <div class="dialog-content max-w-[400px] p-0 gap-0" onclick="event.stopPropagation()">
        <div class="px-5 py-4 border-b border-[#E5E5E5]">
            <h2 class="text-base font-medium text-[#1A1A1A]">图片设置</h2>
        </div>
        
        <div class="p-5 space-y-6">
            <!-- Quality Selection -->
            <div>
                <label class="text-sm text-[#666666] mb-2 block">图像质量</label>
                <div class="flex gap-2">
                    <button
                        id="quality-2k"
                        onclick="setQuality('2k')"
                        class="flex-1 py-2 text-sm rounded-lg border transition-all duration-200 quality-btn border-[#3B82F6] bg-[#F0F7FF] text-[#3B82F6]"
                        data-quality="2k"
                    >
                        高清 2K
                    </button>
                    <button
                        id="quality-4k"
                        onclick="setQuality('4k')"
                        class="flex-1 py-2 text-sm rounded-lg border transition-all duration-200 quality-btn border-[#E5E5E5] text-[#666666] hover:border-[#3B82F6]"
                        data-quality="4k"
                    >
                        超清 4K
                    </button>
                </div>
            </div>

            <!-- Aspect Ratio -->
            <div>
                <label class="text-sm text-[#666666] mb-3 block">图片尺寸</label>
                <div class="grid grid-cols-5 gap-2">
                    <?php
                    $aspectRatios = [
                        ['value' => '1:1', 'label' => '1:1', 'w' => 1024, 'h' => 1024],
                        ['value' => '2:3', 'label' => '2:3', 'w' => 768, 'h' => 1152],
                        ['value' => '3:2', 'label' => '3:2', 'w' => 1152, 'h' => 768],
                        ['value' => '3:4', 'label' => '3:4', 'w' => 768, 'h' => 1024],
                        ['value' => '4:3', 'label' => '4:3', 'w' => 1024, 'h' => 768],
                        ['value' => '9:16', 'label' => '9:16', 'w' => 576, 'h' => 1024],
                        ['value' => '16:9', 'label' => '16:9', 'w' => 1024, 'h' => 576],
                        ['value' => '9:21', 'label' => '9:21', 'w' => 448, 'h' => 1024],
                        ['value' => '21:9', 'label' => '21:9', 'w' => 1024, 'h' => 448],
                    ];
                    foreach ($aspectRatios as $ratio):
                        $isSelected = $ratio['value'] === $defaultAspectRatio;
                    ?>
                        <button
                            onclick="setAspectRatio('<?= $ratio['value'] ?>', <?= $ratio['w'] ?>, <?= $ratio['h'] ?>)"
                            class="flex flex-col items-center gap-1.5 p-2 rounded-lg border transition-all duration-200 <?= $isSelected ? 'border-[#3B82F6] bg-[#F0F7FF]' : 'border-[#E5E5E5] hover:border-[#3B82F6]' ?> aspect-ratio-btn"
                            data-ratio="<?= $ratio['value'] ?>"
                        >
                            <div 
                                class="border-2 rounded-sm <?= $isSelected ? 'border-[#3B82F6]' : 'border-[#999999]' ?>"
                                style="width: <?= $ratio['value'] === '1:1' ? 16 : ($ratio['w'] > $ratio['h'] ? 18 : 12) ?>px; height: <?= $ratio['value'] === '1:1' ? 16 : ($ratio['w'] > $ratio['h'] ? 12 : 18) ?>px;"
                            ></div>
                            <span class="text-[10px] <?= $isSelected ? 'text-[#3B82F6]' : 'text-[#666666]' ?>"><?= $ratio['label'] ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
                
                <!-- Custom Size Input -->
                <div class="flex items-center gap-2 mt-3">
                    <div class="flex items-center gap-2 flex-1">
                        <span class="text-xs text-[#999999]">W</span>
                        <input 
                            type="text" 
                            id="width-input"
                            value="<?= $aspectRatios[3]['w'] ?>"
                            readonly
                            class="flex-1 h-8 px-3 text-sm bg-[#F5F5F5] rounded border border-[#E5E5E5] text-[#666666]"
                        />
                    </div>
                    <span class="text-[#999999]">×</span>
                    <div class="flex items-center gap-2 flex-1">
                        <span class="text-xs text-[#999999]">H</span>
                        <input 
                            type="text" 
                            id="height-input"
                            value="<?= $aspectRatios[3]['h'] ?>"
                            readonly
                            class="flex-1 h-8 px-3 text-sm bg-[#F5F5F5] rounded border border-[#E5E5E5] text-[#666666]"
                        />
                    </div>
                </div>
            </div>

            <!-- Image Count -->
            <div>
                <label class="text-sm text-[#666666] mb-2 block">图片张数</label>
                <div class="grid grid-cols-4 gap-2">
                    <?php for ($i = 1; $i <= 4; $i++): ?>
                        <button
                            onclick="setCount(<?= $i ?>)"
                            id="count-<?= $i ?>"
                            class="py-2 text-sm rounded-lg border transition-all duration-200 count-btn <?= $i === 1 ? 'border-[#3B82F6] bg-[#F0F7FF] text-[#3B82F6]' : 'border-[#E5E5E5] text-[#666666] hover:border-[#3B82F6]' ?>"
                            data-count="<?= $i ?>"
                        >
                            <?= $i ?>张
                        </button>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// 存储当前设置
window.currentSettings = {
    mode: 'single',
    quality: '2k',
    aspectRatio: '<?= $defaultAspectRatio ?>',
    selectedModel: '<?= $currentModel['id'] ?>',
    count: 1
};
</script>
