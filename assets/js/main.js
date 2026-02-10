// 切换标签页
function changeTab(tab) {
    window.location.href = `index.php?tab=${tab}`;
}

// 切换创作类型
function changeType(type) {
    if (type === 'image' || type === 'video') {
        window.location.href = `index.php?tab=create&type=${type}`;
    }
}

// 打开模型选择对话框
function openModelDialog() {
    const dialog = document.getElementById('model-dialog');
    if (dialog) {
        dialog.classList.remove('hidden');
        dialog.style.display = 'flex';
        // 重新初始化图标
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
}

// 关闭模型选择对话框
function closeModelDialog() {
    const dialog = document.getElementById('model-dialog');
    if (dialog) {
        dialog.classList.add('hidden');
        dialog.style.display = 'none';
    }
}

// 选择模型
function selectModel(modelId, modelName) {
    document.getElementById('selected-model').textContent = modelName;
    if (window.currentSettings) {
        window.currentSettings.selectedModel = modelId;
    }
    closeModelDialog();
}

// 打开参数设置对话框
function openParamsDialog() {
    const dialog = document.getElementById('params-dialog');
    if (dialog) {
        dialog.classList.remove('hidden');
        dialog.style.display = 'flex';
        // 更新UI状态
        updateParamsDialogUI();
        // 重新初始化图标
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
}

// 关闭参数设置对话框
function closeParamsDialog() {
    const dialog = document.getElementById('params-dialog');
    if (dialog) {
        dialog.classList.add('hidden');
        dialog.style.display = 'none';
    }
}

// 处理首帧和尾帧上传
function handleFrameUpload(input, previewId, frameType) {
    const file = input.files[0];
    if (file && file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById(previewId);
            if (preview) {
                preview.innerHTML = `<img src="${e.target.result}" alt="${frameType === 'first-frame' ? '首帧' : '尾帧'}" class="w-full h-full object-cover rounded-lg" />`;
            }
            // 存储文件信息
            if (!window.frameFiles) {
                window.frameFiles = {};
            }
            window.frameFiles[frameType] = file;
        };
        reader.readAsDataURL(file);
    }
}

// 设置图片张数
function setCount(count) {
    if (!window.currentSettings) {
        window.currentSettings = {};
    }
    window.currentSettings.count = count;
    
    // 更新UI
    const countElement = document.getElementById('image-count');
    if (countElement) {
        countElement.textContent = count + '张';
    }
    
    // 更新按钮状态
    document.querySelectorAll('.count-btn').forEach(btn => {
        const btnCount = parseInt(btn.getAttribute('data-count'));
        if (btnCount === count) {
            btn.className = 'py-2 text-sm rounded-lg border transition-all duration-200 border-[#3B82F6] bg-[#F0F7FF] text-[#3B82F6]';
        } else {
            btn.className = 'py-2 text-sm rounded-lg border transition-all duration-200 border-[#E5E5E5] hover:border-[#3B82F6] text-[#666666]';
        }
    });
}

// 更新参数对话框UI
function updateParamsDialogUI() {
    if (!window.currentSettings) return;
    
    // 更新图片张数按钮
    if (window.currentSettings.count) {
        setCount(window.currentSettings.count);
    }
    
    // 更新质量按钮
    const quality2k = document.getElementById('quality-2k');
    const quality4k = document.getElementById('quality-4k');
    if (quality2k && quality4k) {
        if (window.currentSettings.quality === '2k') {
            quality2k.className = 'flex-1 py-2 text-sm rounded-md transition-all duration-200 bg-white text-[#1A1A1A] shadow-sm';
            quality4k.className = 'flex-1 py-2 text-sm rounded-md transition-all duration-200 text-[#666666] hover:text-[#1A1A1A]';
        } else {
            quality2k.className = 'flex-1 py-2 text-sm rounded-md transition-all duration-200 text-[#666666] hover:text-[#1A1A1A]';
            quality4k.className = 'flex-1 py-2 text-sm rounded-md transition-all duration-200 bg-white text-[#1A1A1A] shadow-sm';
        }
    }
    
    // 更新比例按钮
    document.querySelectorAll('.aspect-ratio-btn').forEach(btn => {
        const ratio = btn.getAttribute('data-ratio');
        if (ratio === window.currentSettings.aspectRatio) {
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
}

// 设置模式
function setMode(mode) {
    if (window.currentSettings) {
        window.currentSettings.mode = mode;
    }
    updateParamsDialogUI();
}

// 设置质量
function setQuality(quality) {
    if (window.currentSettings) {
        window.currentSettings.quality = quality;
    }
    updateParamsDialogUI();
}

// 设置比例
function setAspectRatio(ratio, width, height) {
    if (window.currentSettings) {
        window.currentSettings.aspectRatio = ratio;
    }
    document.getElementById('aspect-ratio').textContent = ratio;
    document.getElementById('width-input').value = width;
    document.getElementById('height-input').value = height;
    updateParamsDialogUI();
}

// 使用模板
function useTemplate(template) {
    console.log('Using template:', template);
    // 可以在这里填充提示词或跳转到生成页面
    alert('模板功能：' + template.title);
}

// 生成处理 - 调用后端 API
async function handleGenerate() {
    const promptInput = document.getElementById('prompt-input');
    if (!promptInput) return;
    
    const prompt = promptInput.value.trim();
    if (!prompt) {
        alert('请输入提示词');
        return;
    }

    const type = window.currentCreationType || 'image';
    const selectedModel = document.getElementById('selected-model')?.textContent || '';
    const settings = window.currentSettings || {};
    
    const aspectRatio = type === 'video' 
        ? (document.getElementById('video-aspect-ratio')?.textContent || '16:9')
        : (document.getElementById('aspect-ratio')?.textContent || '3:4');

    const payload = {
        prompt,
        model: selectedModel,
        type,
        aspectRatio,
        mode: settings.mode || 'single',
        quality: settings.quality || '2k',
    };
    
    if (type === 'video') {
        const durationEl = document.getElementById('video-duration');
        payload.duration = durationEl ? parseInt(durationEl.textContent) || 5 : 5;
        payload.quality = window.videoQuality || 'standard';
    }

    try {
        const response = await fetch('api/generation/create.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload),
        });

        const data = await response.json();
        if (data.success) {
            alert('生成任务已创建！任务ID：' + (data.data?.taskId || ''));
        } else {
            alert('生成失败：' + (data.message || '未知错误'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('请求失败，请稍后重试');
    }
}

// 打开模板半屏
function openTemplateSheet() {
    const sheet = document.getElementById('template-sheet');
    if (sheet) {
        sheet.classList.remove('hidden');
        sheet.style.display = 'flex';
        // 重新初始化图标
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
}

// 关闭模板半屏
function closeTemplateSheet() {
    const sheet = document.getElementById('template-sheet');
    if (sheet) {
        sheet.classList.add('hidden');
        sheet.style.display = 'none';
    }
}

// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', function() {
    // 初始化Lucide图标
    setTimeout(function() {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }, 100);
    
    // 监听图标更新（当动态内容加载后）
    const observer = new MutationObserver(function(mutations) {
        if (typeof lucide !== 'undefined') {
            setTimeout(function() {
                lucide.createIcons();
            }, 50);
        }
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    // 确保对话框初始状态是隐藏的
    const modelDialog = document.getElementById('model-dialog');
    const paramsDialog = document.getElementById('params-dialog');
    if (modelDialog) {
        modelDialog.style.display = 'none';
    }
    if (paramsDialog) {
        paramsDialog.style.display = 'none';
    }
});
