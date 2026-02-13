<?php
// 视频参数设置对话框
$defaultAspectRatio = $defaultAspectRatio ?? '16:9';
$videoAspectRatios = [
    ['value' => '16:9', 'label' => '16:9'],
    ['value' => '1:1', 'label' => '1:1'],
    ['value' => '9:16', 'label' => '9:16'],
];
$defaultVideoDuration = 5;
$defaultVideoQuality = 'standard';
?>
<!-- Video Params Dialog -->
<div id="video-params-dialog" class="hidden fixed inset-0 z-50 dialog-overlay" onclick="closeVideoParamsDialog()" style="display: none;">
    <div class="dialog-content w-[92vw] max-w-[480px] p-0 gap-0" onclick="event.stopPropagation()">
        <div class="px-5 py-4 border-b border-[#E5E5E5]">
            <h2 class="text-base font-medium text-[#1A1A1A]">视频设置</h2>
        </div>
        
        <div class="p-5 space-y-6">
            <!-- Quality Selection -->
            <div>
                <label class="text-sm text-[#666666] mb-2 block">生成品质</label>
                <div class="flex p-1 bg-[#F5F5F5] rounded-lg">
                    <button
                        id="quality-standard"
                        onclick="setVideoQuality('standard')"
                        class="flex-1 py-2 text-sm rounded-md transition-all duration-200 bg-white text-[#1A1A1A] shadow-sm whitespace-nowrap"
                    >
                        标准模式
                    </button>
                    <button
                        id="quality-high"
                        onclick="setVideoQuality('high')"
                        class="flex-1 py-2 text-sm rounded-md transition-all duration-200 text-[#666666] hover:text-[#1A1A1A] whitespace-nowrap"
                    >
                        高品质模式
                    </button>
                </div>
            </div>

            <!-- Aspect Ratio -->
            <div>
                <label class="text-sm text-[#666666] mb-3 block">视频比例</label>
                <div class="grid grid-cols-3 gap-2">
                    <?php foreach ($videoAspectRatios as $ratio): ?>
                        <?php $isSelected = $ratio['value'] === $defaultAspectRatio; ?>
                        <button
                            onclick="setVideoAspectRatio('<?= $ratio['value'] ?>')"
                            class="flex flex-col items-center gap-1.5 p-2 rounded-lg border transition-all duration-200 <?= $isSelected ? 'border-[#3B82F6] bg-[#F0F7FF]' : 'border-[#E5E5E5] hover:border-[#3B82F6]' ?> video-aspect-ratio-btn"
                            data-ratio="<?= $ratio['value'] ?>"
                        >
                            <div 
                                class="border-2 rounded-sm <?= $isSelected ? 'border-[#3B82F6]' : 'border-[#999999]' ?>"
                                style="width: <?= $ratio['value'] === '1:1' ? 16 : ($ratio['value'] === '16:9' ? 18 : 12) ?>px; height: <?= $ratio['value'] === '1:1' ? 16 : ($ratio['value'] === '16:9' ? 12 : 18) ?>px;"
                            ></div>
                            <span class="text-[10px] <?= $isSelected ? 'text-[#3B82F6]' : 'text-[#666666]' ?>"><?= $ratio['label'] ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Duration -->
            <div>
                <label class="text-sm text-[#666666] mb-2 block">视频时长</label>
                <div class="flex p-1 bg-[#F5F5F5] rounded-lg">
                    <button
                        id="duration-5"
                        onclick="setVideoDuration(5)"
                        class="flex-1 py-2 text-sm rounded-md transition-all duration-200 bg-white text-[#1A1A1A] shadow-sm"
                    >
                        5s
                    </button>
                    <button
                        id="duration-10"
                        onclick="setVideoDuration(10)"
                        class="flex-1 py-2 text-sm rounded-md transition-all duration-200 text-[#666666] hover:text-[#1A1A1A]"
                    >
                        10s
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// 视频参数设置函数
function openVideoParamsDialog() {
    const dialog = document.getElementById('video-params-dialog');
    if (dialog) {
        dialog.classList.remove('hidden');
        dialog.style.display = 'flex';
        updateVideoParamsDialogUI();
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
}

function closeVideoParamsDialog() {
    const dialog = document.getElementById('video-params-dialog');
    if (dialog) {
        dialog.classList.add('hidden');
        dialog.style.display = 'none';
    }
}

function setVideoAspectRatio(ratio) {
    if (!window.currentSettings) {
        window.currentSettings = {};
    }
    window.currentSettings.videoAspectRatio = ratio;
    const element = document.getElementById('video-aspect-ratio');
    if (element) {
        element.textContent = ratio;
    }
    updateVideoParamsDialogUI();
    if (typeof updateGeneratePointsDisplay === 'function') updateGeneratePointsDisplay();
}

function setVideoDuration(duration) {
    if (!window.currentSettings) {
        window.currentSettings = {};
    }
    window.currentSettings.videoDuration = duration;
    const element = document.getElementById('video-duration');
    if (element) {
        element.textContent = duration + 's';
    }
    updateVideoParamsDialogUI();
    if (typeof updateGeneratePointsDisplay === 'function') updateGeneratePointsDisplay();
}

function setVideoQuality(quality) {
    if (!window.currentSettings) {
        window.currentSettings = {};
    }
    window.currentSettings.videoQuality = quality;
    updateVideoParamsDialogUI();
    if (typeof updateGeneratePointsDisplay === 'function') updateGeneratePointsDisplay();
}

function updateVideoParamsDialogUI() {
    if (!window.currentSettings) return;
    
    // 更新比例按钮
    document.querySelectorAll('.video-aspect-ratio-btn').forEach(btn => {
        const ratio = btn.getAttribute('data-ratio');
        const currentRatio = window.currentSettings.videoAspectRatio || '<?= $defaultAspectRatio ?>';
        if (ratio === currentRatio) {
            btn.classList.add('border-[#3B82F6]', 'bg-[#F0F7FF]');
            btn.classList.remove('border-[#E5E5E5]');
            const span = btn.querySelector('span');
            if (span) span.classList.add('text-[#3B82F6]');
            const div = btn.querySelector('div');
            if (div) div.classList.add('border-[#3B82F6]');
        } else {
            btn.classList.remove('border-[#3B82F6]', 'bg-[#F0F7FF]');
            btn.classList.add('border-[#E5E5E5]');
            const span = btn.querySelector('span');
            if (span) span.classList.remove('text-[#3B82F6]');
            const div = btn.querySelector('div');
            if (div) div.classList.remove('border-[#3B82F6]');
        }
    });
    
    // 更新时长按钮
    const duration5 = document.getElementById('duration-5');
    const duration10 = document.getElementById('duration-10');
    const currentDuration = window.currentSettings.videoDuration || <?= $defaultVideoDuration ?>;
    if (duration5 && duration10) {
        if (currentDuration === 5) {
            duration5.className = 'flex-1 py-2 text-sm rounded-md transition-all duration-200 bg-white text-[#1A1A1A] shadow-sm';
            duration10.className = 'flex-1 py-2 text-sm rounded-md transition-all duration-200 text-[#666666] hover:text-[#1A1A1A]';
        } else {
            duration5.className = 'flex-1 py-2 text-sm rounded-md transition-all duration-200 text-[#666666] hover:text-[#1A1A1A]';
            duration10.className = 'flex-1 py-2 text-sm rounded-md transition-all duration-200 bg-white text-[#1A1A1A] shadow-sm';
        }
    }
    
    // 更新品质按钮
    const qualityStandard = document.getElementById('quality-standard');
    const qualityHigh = document.getElementById('quality-high');
    const currentQuality = window.currentSettings.videoQuality || '<?= $defaultVideoQuality ?>';
    if (qualityStandard && qualityHigh) {
        if (currentQuality === 'standard') {
            qualityStandard.className = 'flex-1 py-2 text-sm rounded-md transition-all duration-200 bg-white text-[#1A1A1A] shadow-sm';
            qualityHigh.className = 'flex-1 py-2 text-sm rounded-md transition-all duration-200 text-[#666666] hover:text-[#1A1A1A]';
        } else {
            qualityStandard.className = 'flex-1 py-2 text-sm rounded-md transition-all duration-200 text-[#666666] hover:text-[#1A1A1A]';
            qualityHigh.className = 'flex-1 py-2 text-sm rounded-md transition-all duration-200 bg-white text-[#1A1A1A] shadow-sm';
        }
    }
}

// 初始化视频设置
if (!window.currentSettings) {
    window.currentSettings = {};
}
window.currentSettings.videoAspectRatio = window.currentSettings.videoAspectRatio || '<?= $defaultAspectRatio ?>';
window.currentSettings.videoDuration = window.currentSettings.videoDuration || <?= $defaultVideoDuration ?>;
window.currentSettings.videoQuality = window.currentSettings.videoQuality || '<?= $defaultVideoQuality ?>';
</script>
