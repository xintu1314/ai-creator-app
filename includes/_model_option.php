<?php
// 单个模型选项按钮（被 dialogs.php 的 model-dialog 复用）
if (empty($model) || !is_array($model)) return;
?>
<button
    onclick="selectModel('<?= htmlspecialchars($model['id']) ?>', '<?= htmlspecialchars($model['name']) ?>')"
    class="text-left p-4 min-h-[60px] rounded-xl border border-[#E5E5E5] bg-[#F9FAFB] transition-all duration-200 hover:border-[#3B82F6] hover:bg-[#F0F7FF] model-option"
    data-model-id="<?= htmlspecialchars($model['id']) ?>"
>
    <div class="flex items-start gap-3">
        <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 bg-gradient-to-br <?= $model['icon'] === 'banana' ? 'from-yellow-400 to-yellow-600' : ($model['icon'] === 'doubao' ? 'from-emerald-500 to-teal-600' : ($model['icon'] === 'sora' ? 'from-purple-500 to-pink-500' : 'from-blue-400 to-blue-600')) ?>">
            <?php
            $iconHtml = '';
            switch($model['icon']) {
                case 'seedream': $iconHtml = '<div class="w-5 h-5 bg-white/90 rounded flex items-center justify-center"><div class="w-3 h-3 bg-gradient-to-r from-blue-400 to-blue-600 rounded-sm"></div></div>'; break;
                case 'universal': $iconHtml = '<div class="w-5 h-5 bg-amber-400 rounded-full"></div>'; break;
                case 'qwen': $iconHtml = '<div class="w-5 h-5 bg-purple-500 rounded-lg"></div>'; break;
                case 'ai': $iconHtml = '<div class="w-5 h-5 bg-pink-500 rounded-full"></div>'; break;
                case 'base': $iconHtml = '<div class="w-5 h-5 bg-orange-500 rounded-lg"></div>'; break;
                case 'zimage': $iconHtml = '<div class="w-5 h-5 bg-indigo-500 rounded-lg"></div>'; break;
                case 'pixverse': $iconHtml = '<div class="w-5 h-5 bg-cyan-400 rounded-full"></div>'; break;
                case 'kling': $iconHtml = '<div class="w-5 h-5 bg-blue-500 rounded-full"></div>'; break;
                case 'tongyi': $iconHtml = '<div class="w-5 h-5 bg-purple-600 rounded-lg"></div>'; break;
                case 'vidu': $iconHtml = '<div class="w-5 h-5 bg-orange-400 rounded-lg"></div>'; break;
                case 'hailuo': $iconHtml = '<div class="w-5 h-5 bg-red-500 rounded-full"></div>'; break;
                case 'banana': $iconHtml = '<div class="w-5 h-5 bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-lg"></div>'; break;
                case 'doubao': $iconHtml = '<div class="w-5 h-5 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-lg"></div>'; break;
                case 'sora': $iconHtml = '<div class="w-5 h-5 bg-gradient-to-br from-purple-500 to-pink-500 rounded-lg"></div>'; break;
                default: $iconHtml = '<div class="w-5 h-5 bg-blue-500 rounded-lg"></div>';
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
            <div class="flex flex-wrap gap-1.5">
                <?php foreach (($model['tags'] ?? []) as $tag): ?>
                    <span class="px-2 py-0.5 text-[10px] bg-white border border-[#E5E5E5] rounded text-[#666666]"><?= htmlspecialchars($tag) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</button>
