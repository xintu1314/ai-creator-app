// ============================
// 标签页 & 创作类型切换
// ============================
function changeTab(tab) {
    window.location.href = `index.php?tab=${tab}`;
}

function changeType(type) {
    if (type === 'image' || type === 'video') {
        window.location.href = `index.php?tab=create&type=${type}`;
    }
}

// ============================
// 对话框：模型选择
// ============================
function openModelDialog() {
    const dialog = document.getElementById('model-dialog');
    if (dialog) {
        dialog.classList.remove('hidden');
        dialog.style.display = 'flex';
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }
}

function closeModelDialog() {
    const dialog = document.getElementById('model-dialog');
    if (dialog) {
        dialog.classList.add('hidden');
        dialog.style.display = 'none';
    }
}

function selectModel(modelId, modelName) {
    document.getElementById('selected-model').textContent = modelName;
    if (window.currentSettings) {
        window.currentSettings.selectedModel = modelId;
    }
    closeModelDialog();
}

// ============================
// 对话框：参数设置
// ============================
function openParamsDialog() {
    const dialog = document.getElementById('params-dialog');
    if (dialog) {
        dialog.classList.remove('hidden');
        dialog.style.display = 'flex';
        updateParamsDialogUI();
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }
}

function closeParamsDialog() {
    const dialog = document.getElementById('params-dialog');
    if (dialog) {
        dialog.classList.add('hidden');
        dialog.style.display = 'none';
    }
}

// ============================
// 上传：首帧/尾帧（OSS）
// ============================
async function handleFrameUpload(input, previewId, frameType) {
    const file = input.files[0];
    if (!file || !file.type.startsWith('image/')) return;
    const preview = document.getElementById(previewId);
    if (preview) preview.innerHTML = '<span class="text-xs text-[#3B82F6]">上传中...</span>';
    const formData = new FormData();
    formData.append('file', file);
    formData.append('prefix', 'assets/images/frames');
    try {
        const res = await fetch('api/upload/image.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success && data.data?.url) {
            if (!window.frameUrls) window.frameUrls = {};
            window.frameUrls[frameType] = data.data.url;
            if (preview) preview.innerHTML = `<img src="${data.data.url}" alt="${frameType}" class="w-full h-full object-cover rounded-lg" />`;
        } else {
            if (preview) preview.innerHTML = '<span class="text-xs text-red-500">' + (data.message || '上传失败') + '</span>';
        }
    } catch (err) {
        if (preview) preview.innerHTML = '<span class="text-xs text-red-500">上传失败</span>';
    }
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

// ============================
// 上传：多张参考图（OSS）
// ============================
async function handleRefImagesUpload(input) {
    const files = Array.from(input.files || []);
    if (files.length === 0) return;
    if (!window.referenceImageUrls) window.referenceImageUrls = [];
    const preview = document.getElementById('ref-images-preview');
    for (const file of files) {
        if (!file.type.startsWith('image/')) continue;
        const formData = new FormData();
        formData.append('file', file);
        formData.append('prefix', 'assets/images/references');
        try {
            const res = await fetch('api/upload/image.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success && data.data?.url) {
                window.referenceImageUrls.push(data.data.url);
                const div = document.createElement('div');
                div.className = 'relative w-[60px] h-[60px] rounded-lg overflow-hidden flex-shrink-0 group';
                div.dataset.url = data.data.url;
                div.innerHTML = `<img src="${data.data.url}" alt="参考" class="w-full h-full object-cover" /><button type="button" onclick="removeRefImage(this)" class="absolute top-0 right-0 w-5 h-5 bg-black/60 text-white text-xs rounded-bl flex items-center justify-center opacity-0 group-hover:opacity-100">×</button>`;
                if (preview) preview.appendChild(div);
            }
        } catch (e) { /* skip */ }
    }
    input.value = '';
}

function removeRefImage(btn) {
    const div = btn.closest('.relative');
    if (!div || !div.dataset.url) return;
    const url = div.dataset.url;
    if (window.referenceImageUrls) window.referenceImageUrls = window.referenceImageUrls.filter(u => u !== url);
    div.remove();
}

// ============================
// 参数设置 UI
// ============================
function setCount(count) {
    if (!window.currentSettings) window.currentSettings = {};
    window.currentSettings.count = count;
    const countElement = document.getElementById('image-count');
    if (countElement) countElement.textContent = count + '张';
    document.querySelectorAll('.count-btn').forEach(btn => {
        const btnCount = parseInt(btn.getAttribute('data-count'));
        const baseClass = 'py-2 text-sm rounded-lg border transition-all duration-200 count-btn';
        if (btnCount === count) {
            btn.className = baseClass + ' border-[#3B82F6] bg-[#F0F7FF] text-[#3B82F6]';
        } else {
            btn.className = baseClass + ' border-[#E5E5E5] text-[#666666] hover:border-[#3B82F6]';
        }
    });
}

function updateParamsDialogUI() {
    if (!window.currentSettings) return;
    if (window.currentSettings.count) setCount(window.currentSettings.count);
    const quality2k = document.getElementById('quality-2k');
    const quality4k = document.getElementById('quality-4k');
    const selectedQuality = (window.currentSettings.quality || '2k').toLowerCase();
    const baseClass = 'flex-1 py-2 text-sm rounded-lg border transition-all duration-200 quality-btn';
    const selectedClass = baseClass + ' border-[#3B82F6] bg-[#F0F7FF] text-[#3B82F6]';
    const unselectedClass = baseClass + ' border-[#E5E5E5] text-[#666666] hover:border-[#3B82F6]';
    if (quality2k && quality4k) {
        quality2k.className = selectedQuality === '2k' ? selectedClass : unselectedClass;
        quality4k.className = selectedQuality === '4k' ? selectedClass : unselectedClass;
    }
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

function setMode(mode) {
    if (window.currentSettings) window.currentSettings.mode = mode;
    updateParamsDialogUI();
}

function setQuality(quality) {
    if (window.currentSettings) window.currentSettings.quality = quality;
    updateParamsDialogUI();
}

function setAspectRatio(ratio, width, height) {
    if (window.currentSettings) window.currentSettings.aspectRatio = ratio;
    document.getElementById('aspect-ratio').textContent = ratio;
    document.getElementById('width-input').value = width;
    document.getElementById('height-input').value = height;
    updateParamsDialogUI();
}

function useTemplate(template) {
    console.log('Using template:', template);
    alert('模板功能：' + template.title);
}

// ============================
// 工具函数
// ============================
function escapeHtml(s) {
    if (!s) return '';
    const div = document.createElement('div');
    div.textContent = s;
    return div.innerHTML;
}

// ============================
// 布局状态切换：普通模式 ↔ 生成模式
// ============================
function enterCreatingMode() {
    const main = document.querySelector('main');
    if (main) main.classList.add('creating-mode');
    const title = document.getElementById('creation-title');
    if (title) title.classList.add('hidden');
    const genArea = document.getElementById('generation-area');
    if (genArea) genArea.classList.remove('hidden');
    const tplSection = document.getElementById('template-cards-section');
    if (tplSection) tplSection.classList.add('hidden');
    const statusBar = document.getElementById('gen-status-bar');
    if (statusBar) statusBar.classList.remove('hidden');
}

function scrollToLatestGeneration() {
    const genArea = document.getElementById('generation-area');
    if (genArea) genArea.scrollTop = genArea.scrollHeight;
}

// ============================
// 生成按钮状态
// ============================
function setGenerateBtnLoading(loading) {
    const genBtn = document.getElementById('generate-btn');
    if (!genBtn) return;
    if (loading) {
        genBtn.disabled = true;
        genBtn.classList.add('opacity-70', 'cursor-not-allowed');
        genBtn.innerHTML = '<div class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div> 生成中...';
    } else {
        genBtn.disabled = false;
        genBtn.classList.remove('opacity-70', 'cursor-not-allowed');
        genBtn.innerHTML = '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg> 生成';
    }
}

function isGeneratingNow() {
    return Boolean(window.__genInFlight);
}

function setGeneratingNow(flag) {
    window.__genInFlight = Boolean(flag);
}

function initGenerationBatch(total) {
    window.__activePollGroupId = 'grp_' + Date.now() + '_' + Math.random().toString(36).slice(2, 8);
    window.__batchState = {
        total: Math.max(1, Number(total || 1)),
        done: 0,
    };
}

function completeOneGeneration() {
    if (!window.__batchState) {
        setGeneratingNow(false);
        window.__activePollId = null;
        window.__activePollGroupId = null;
        setGenerateBtnLoading(false);
        updateStatusBar(null);
        return;
    }
    window.__batchState.done += 1;
    const done = window.__batchState.done;
    const total = window.__batchState.total;
    if (done >= total) {
        setGeneratingNow(false);
        window.__activePollId = null;
        window.__activePollGroupId = null;
        setGenerateBtnLoading(false);
        updateStatusBar(null);
    } else {
        updateStatusBar(`${done}/${total} 已完成，剩余生成中...`);
    }
}

// ============================
// 创建生成中消息卡片（渐变动画风格）
// ============================
function createProcessingMessage(prompt, meta, slotIndex = 0, totalSlots = 1) {
    const id = 'msg-' + Date.now() + '-' + Math.random().toString(36).slice(2, 8) + '-' + slotIndex;
    const msg = document.createElement('div');
    msg.id = id;
    msg.className = 'gen-result-card gen-fade-in';
    msg.dataset.prompt = prompt || '';
    msg.dataset.meta = JSON.stringify(meta || {});

    msg.innerHTML = `
        <div class="px-5 pt-4 pb-3">
            <div class="text-sm font-medium text-[#1A1A1A] mb-2 truncate">${escapeHtml(prompt || '生成中...')}</div>
            <div class="flex flex-wrap gap-2 mb-4">
                <span class="px-2.5 py-0.5 text-xs rounded-full bg-[#EEF2FF] text-[#4F46E5]">第${slotIndex + 1}/${totalSlots}张</span>
                <span class="px-2.5 py-0.5 text-xs rounded-full bg-[#F5F5F5] text-[#666]">${escapeHtml((meta && meta.model) || '')}</span>
                <span class="px-2.5 py-0.5 text-xs rounded-full bg-[#F5F5F5] text-[#666]">${escapeHtml((meta && meta.quality) || '')}</span>
                <span class="px-2.5 py-0.5 text-xs rounded-full bg-[#F5F5F5] text-[#666]">${escapeHtml((meta && meta.aspectRatio) || '')}</span>
                <span class="px-2.5 py-0.5 text-xs rounded-full bg-[#F5F5F5] text-[#666]">${escapeHtml(String((meta && meta.count) || 1) + '张')}</span>
            </div>
            <div class="relative w-[240px] h-[320px] rounded-2xl overflow-hidden">
                <!-- 渐变动画背景 -->
                <div class="absolute inset-0 gen-gradient-anim"></div>
                <!-- 进度徽章 -->
                <div class="absolute top-3 left-3 gen-progress-badge msg-progress-text">
                    0% 生成中...
                </div>
                <!-- 底部进度条 -->
                <div class="absolute bottom-0 left-0 right-0 h-1 bg-white/20">
                    <div class="msg-progress-fill h-full bg-white/60 rounded-r-full gen-progress-bar-fill" style="width:0%"></div>
                </div>
            </div>
        </div>
    `;
    return { el: msg, id };
}

// ============================
// 创建完成消息卡片
// ============================
function createResultMessage(prompt, meta, imageUrl, slotIndex = 0, totalSlots = 1) {
    const msg = document.createElement('div');
    msg.className = 'gen-result-card gen-fade-in';
    msg.dataset.prompt = prompt || '';
    msg.dataset.meta = JSON.stringify(meta || {});

    const imgHtml = imageUrl
        ? `<img src="${escapeHtml(imageUrl)}" alt="生成结果" class="w-full rounded-2xl shadow-sm block max-h-[500px] object-contain bg-[#FAFAFA]" />`
        : '<div class="h-[200px] flex items-center justify-center text-[#999] rounded-2xl bg-[#F5F5F5]">暂无预览</div>';

    msg.innerHTML = `
        <div class="px-5 pt-4 pb-2">
            <div class="text-sm font-medium text-[#1A1A1A] mb-2">${escapeHtml(prompt || '')}</div>
            <div class="flex flex-wrap gap-2 mb-3">
                <span class="px-2.5 py-0.5 text-xs rounded-full bg-[#EEF2FF] text-[#4F46E5]">第${slotIndex + 1}/${totalSlots}张</span>
                <span class="px-2.5 py-0.5 text-xs rounded-full bg-[#F5F5F5] text-[#666]">${escapeHtml((meta && meta.model) || '')}</span>
                <span class="px-2.5 py-0.5 text-xs rounded-full bg-[#F5F5F5] text-[#666]">${escapeHtml((meta && meta.quality) || '')}</span>
                <span class="px-2.5 py-0.5 text-xs rounded-full bg-[#F5F5F5] text-[#666]">${escapeHtml((meta && meta.aspectRatio) || '')}</span>
                <span class="px-2.5 py-0.5 text-xs rounded-full bg-[#F5F5F5] text-[#666]">${escapeHtml(String((meta && meta.count) || 1) + '张')}</span>
            </div>
        </div>
        <div class="px-5 pb-4">
            <div class="relative">
                ${imgHtml}
                ${imageUrl ? '<span class="absolute top-2 left-2 px-2 py-0.5 text-xs bg-black/50 text-white rounded-full backdrop-blur-sm">AI 生成</span>' : ''}
            </div>
            <div class="flex items-center gap-5 mt-3 pt-3 border-t border-[#F0F0F0] text-sm text-[#888]">
                <button type="button" class="flex items-center gap-1.5 hover:text-[#3B82F6] transition-colors" onclick="reEditFromMessage(this)">
                    <i data-lucide="pencil" class="w-3.5 h-3.5"></i> 重新编辑
                </button>
                <button type="button" class="flex items-center gap-1.5 hover:text-[#3B82F6] transition-colors" onclick="regenerateFromMessage(this)">
                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i> 再次生成
                </button>
                <a href="${imageUrl ? escapeHtml(imageUrl) : '#'}" target="_blank" class="flex items-center gap-1.5 hover:text-[#3B82F6] transition-colors ${!imageUrl ? 'pointer-events-none opacity-50' : ''}">
                    <i data-lucide="download" class="w-3.5 h-3.5"></i> 下载
                </a>
                <a href="${imageUrl ? escapeHtml(imageUrl) : '#'}" target="_blank" class="flex items-center gap-1.5 hover:text-[#3B82F6] transition-colors ${!imageUrl ? 'pointer-events-none opacity-50' : ''}">
                    <i data-lucide="external-link" class="w-3.5 h-3.5"></i> 新窗口打开
                </a>
            </div>
        </div>
    `;
    return msg;
}

// ============================
// 创建错误消息卡片（替代 alert）
// ============================
function createErrorMessage(prompt, meta, errorMsg, slotIndex = 0, totalSlots = 1) {
    const msg = document.createElement('div');
    msg.className = 'gen-result-card gen-fade-in';
    msg.dataset.prompt = prompt || '';
    msg.dataset.meta = JSON.stringify(meta || {});

    msg.innerHTML = `
        <div class="px-5 pt-4 pb-2">
            <div class="text-sm font-medium text-[#1A1A1A] mb-2">${escapeHtml(prompt || '')}</div>
            <div class="flex flex-wrap gap-2 mb-3">
                <span class="px-2.5 py-0.5 text-xs rounded-full bg-[#EEF2FF] text-[#4F46E5]">第${slotIndex + 1}/${totalSlots}张</span>
                <span class="px-2.5 py-0.5 text-xs rounded-full bg-[#F5F5F5] text-[#666]">${escapeHtml((meta && meta.model) || '')}</span>
                <span class="px-2.5 py-0.5 text-xs rounded-full bg-[#F5F5F5] text-[#666]">${escapeHtml((meta && meta.quality) || '')}</span>
                <span class="px-2.5 py-0.5 text-xs rounded-full bg-[#F5F5F5] text-[#666]">${escapeHtml((meta && meta.aspectRatio) || '')}</span>
                <span class="px-2.5 py-0.5 text-xs rounded-full bg-[#F5F5F5] text-[#666]">${escapeHtml(String((meta && meta.count) || 1) + '张')}</span>
            </div>
        </div>
        <div class="px-5 pb-4">
            <div class="gen-error-card flex items-start gap-3">
                <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                    <i data-lucide="alert-circle" class="w-4 h-4 text-red-500"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium text-red-700 mb-1">生成失败</div>
                    <div class="text-xs text-red-600/80 mb-3">${escapeHtml(errorMsg || '未知错误')}</div>
                    <div class="flex gap-2">
                        <button type="button" onclick="regenerateFromMessage(this)" class="px-3 py-1.5 text-xs font-medium bg-white border border-red-200 text-red-600 rounded-lg hover:bg-red-50 transition-colors">
                            重新生成
                        </button>
                        <button type="button" onclick="reEditFromMessage(this)" class="px-3 py-1.5 text-xs font-medium bg-white border border-[#E5E5E5] text-[#666] rounded-lg hover:bg-[#F5F5F5] transition-colors">
                            修改提示词
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    return msg;
}

// ============================
// 消息操作：重新编辑 / 再次生成
// ============================
function reEditFromMessage(btn) {
    const card = btn.closest('.gen-result-card');
    if (!card) return;
    const prompt = card.dataset.prompt || '';
    const input = document.getElementById('prompt-input');
    if (input) {
        input.value = prompt;
        input.focus();
    }
}

function regenerateFromMessage(btn) {
    const card = btn.closest('.gen-result-card');
    if (!card) return;
    const prompt = card.dataset.prompt || '';
    const input = document.getElementById('prompt-input');
    if (input) input.value = prompt;
    handleGenerate();
}

// ============================
// 显示生成进度（进入生成模式，添加进度卡片）
// ============================
function showGenerationProgress(prompt, meta, totalSlots = 1) {
    enterCreatingMode();
    const container = document.getElementById('generation-messages');
    if (!container) return;
    const count = Math.max(1, Math.min(4, Number(totalSlots || 1)));
    window.currentProcessingMsgIds = [];
    container.innerHTML = '';
    container.className = count === 1 ? 'space-y-6' : 'grid grid-cols-1 md:grid-cols-2 gap-4';

    for (let i = 0; i < count; i++) {
        const { el, id } = createProcessingMessage(prompt, meta, i, count);
        el.dataset.msgId = id;
        el.dataset.slotIndex = String(i);
        container.appendChild(el);
        window.currentProcessingMsgIds.push(id);
    }

    setGenerateBtnLoading(true);
    updateStatusBar(`0/${count} 生成中...`);

    // 滚动到最新
    setTimeout(scrollToLatestGeneration, 100);
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

// ============================
// 更新生成进度
// ============================
function updateGenerationProgress(percent, text, slotIndex = 0) {
    const ids = window.currentProcessingMsgIds || [];
    const id = ids[slotIndex];
    if (!id) return;
    const msg = document.getElementById(id) || document.querySelector('[data-msg-id="' + id + '"]');
    if (!msg) return;
    const textEl = msg.querySelector('.msg-progress-text');
    const fillEl = msg.querySelector('.msg-progress-fill');
    if (textEl) textEl.textContent = text || (percent + '% 生成中...');
    if (fillEl) fillEl.style.width = percent + '%';
}

// ============================
// 显示生成结果（替换进度卡片为结果卡片）
// ============================
function showGenerationResult(imageUrl, prompt, meta, slotIndex = 0) {
    const container = document.getElementById('generation-messages');
    const ids = window.currentProcessingMsgIds || [];
    const id = ids[slotIndex];
    if (!container) return;

    const oldMsg = id ? (document.getElementById(id) || document.querySelector('[data-msg-id="' + id + '"]')) : null;
    const finalPrompt = prompt || (oldMsg && oldMsg.dataset.prompt) || '';
    const finalMeta = meta || (oldMsg && oldMsg.dataset.meta ? JSON.parse(oldMsg.dataset.meta || '{}') : {});
    const totalSlots = (window.currentProcessingMsgIds || []).length || 1;
    const newMsg = createResultMessage(finalPrompt, finalMeta, imageUrl, slotIndex, totalSlots);

    if (oldMsg) oldMsg.replaceWith(newMsg);
    else container.appendChild(newMsg);

    completeOneGeneration();

    setTimeout(scrollToLatestGeneration, 100);
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

// ============================
// 显示生成错误（替换进度卡片为错误卡片，不用alert）
// ============================
function showGenerationError(errorMsg, prompt, meta, slotIndex = 0) {
    const container = document.getElementById('generation-messages');
    const ids = window.currentProcessingMsgIds || [];
    const id = ids[slotIndex];
    if (!container) return;

    const oldMsg = id ? (document.getElementById(id) || document.querySelector('[data-msg-id="' + id + '"]')) : null;
    const finalPrompt = prompt || (oldMsg && oldMsg.dataset.prompt) || '';
    const finalMeta = meta || (oldMsg && oldMsg.dataset.meta ? JSON.parse(oldMsg.dataset.meta || '{}') : {});
    const totalSlots = (window.currentProcessingMsgIds || []).length || 1;
    const newMsg = createErrorMessage(finalPrompt, finalMeta, errorMsg, slotIndex, totalSlots);

    if (oldMsg) oldMsg.replaceWith(newMsg);
    else container.appendChild(newMsg);

    completeOneGeneration();

    setTimeout(scrollToLatestGeneration, 100);
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

// ============================
// 隐藏生成进度（移除进度卡片）
// ============================
function hideGenerationProgress() {
    const ids = window.currentProcessingMsgIds || [];
    ids.forEach((id) => {
        const msg = document.getElementById(id) || document.querySelector('[data-msg-id="' + id + '"]');
        if (msg) msg.remove();
    });
    window.currentProcessingMsgIds = [];
    setGeneratingNow(false);
    window.__activePollId = null;
    window.__activePollGroupId = null;
    setGenerateBtnLoading(false);
    updateStatusBar(null);
}

// ============================
// 状态栏更新
// ============================
function updateStatusBar(text) {
    const bar = document.getElementById('gen-status-bar');
    const textEl = document.getElementById('gen-status-text');
    if (!bar) return;
    if (text) {
        bar.classList.remove('hidden');
        if (textEl) textEl.textContent = text;
    } else {
        bar.classList.add('hidden');
    }
}

// ============================
// 核心：生成处理 - 调用后端 API
// ============================
async function handleGenerate() {
    // 全局防重：已有任务在生成时，禁止再次提交生图请求（避免重复扣点）
    if (isGeneratingNow()) {
        updateStatusBar('已有任务生成中，请稍候...');
        return;
    }

    const promptInput = document.getElementById('prompt-input');
    if (!promptInput) return;

    const prompt = promptInput.value.trim();
    if (!prompt) {
        // 轻提示：输入框抖动
        promptInput.classList.add('border-red-400');
        promptInput.setAttribute('placeholder', '⚠ 请输入提示词');
        setTimeout(() => {
            promptInput.classList.remove('border-red-400');
            promptInput.setAttribute('placeholder', '输入图片生成的提示词，例如：浩瀚的银河中一艘宇宙飞船驶过');
        }, 2000);
        promptInput.focus();
        return;
    }

    const type = window.currentCreationType || 'image';
    const modelId = window.currentSettings?.selectedModel || document.getElementById('selected-model')?.textContent || '';
    const settings = window.currentSettings || {};

    const aspectRatio = type === 'video'
        ? (document.getElementById('video-aspect-ratio')?.textContent || '16:9')
        : (document.getElementById('aspect-ratio')?.textContent || '3:4');

    const meta = {
        model: document.getElementById('selected-model')?.textContent || modelId,
        quality: (settings.quality || '2k').toUpperCase(),
        aspectRatio: aspectRatio,
        count: Number(settings.count || 1),
    };

    const payload = {
        prompt,
        model: modelId,
        type,
        aspectRatio,
        mode: settings.mode || 'single',
        quality: settings.quality || '2k',
        count: Number(settings.count || 1),
    };

    if (window.referenceImageUrls && window.referenceImageUrls.length > 0) {
        payload.referenceImageUrls = window.referenceImageUrls;
    }

    if (type === 'video') {
        const durationEl = document.getElementById('video-duration');
        payload.duration = durationEl ? parseInt(durationEl.textContent) || 5 : 5;
        payload.quality = window.videoQuality || 'standard';
        if (window.frameUrls) {
            if (window.frameUrls['first-frame']) payload.firstFrameUrl = window.frameUrls['first-frame'];
            if (window.frameUrls['last-frame']) payload.lastFrameUrl = window.frameUrls['last-frame'];
        }
    }

    // 先进入生成模式，显示占位卡片（按选择张数）
    setGeneratingNow(true);
    window.__batchState = null;
    showGenerationProgress(prompt, meta, meta.count);

    try {
        const response = await fetch('api/generation/create.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });

        const text = await response.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('API 返回非 JSON:', text.slice(0, 200));
            showGenerationError('服务器返回格式异常，请稍后重试', prompt, meta);
            return;
        }

        if (data.success) {
            const taskId = data.data?.taskId;
            const taskIds = Array.isArray(data.data?.taskIds) && data.data.taskIds.length > 0
                ? data.data.taskIds
                : (taskId ? [taskId] : []);
            const status = data.data?.status;
            const submittedCount = Number(data.data?.submittedCount || taskIds.length || 1);
            if (type === 'image' && taskIds.length > 0 && status === 'processing') {
                const slotCount = (window.currentProcessingMsgIds || []).length || 1;
                initGenerationBatch(slotCount);

                // 有些任务提交失败时，补充错误卡片
                if (submittedCount < slotCount) {
                    for (let i = submittedCount; i < slotCount; i++) {
                        showGenerationError('该张任务提交失败，请重试', prompt, meta, i);
                    }
                }

                updateStatusBar(`0/${slotCount} 生成中...`);
                taskIds.forEach((id, idx) => {
                    pollTaskStatus(id, type, prompt, meta, idx, slotCount);
                });
            } else if (type === 'video') {
                // 视频接口暂未接入
                showGenerationError(data.data?.message || '视频生成接口暂未接入', prompt, meta, 0);
            } else {
                showGenerationError('不支持的任务类型或模型', prompt, meta, 0);
            }
        } else {
            showGenerationError(data.message || '未知错误', prompt, meta, 0);
        }
    } catch (error) {
        console.error('Error:', error);
        showGenerationError('网络请求失败：' + (error.message || '请检查网络连接后重试'), prompt, meta, 0);
    }
}

// ============================
// 轮询任务状态（图片生成）
// ============================
async function pollTaskStatus(taskId, type, prompt, meta, slotIndex = 0, totalCount = 1) {
    const groupId = window.__activePollGroupId;
    const interval = 2500;
    let progress = 5;
    let emptyUrlRetry = 0;
    let networkErrorStreak = 0;
    let loopCount = 0;

    while (true) {
        // 只允许当前批次轮询继续执行
        if (window.__activePollGroupId !== groupId) {
            return;
        }

        try {
            const res = await fetch('api/generation/status.php?taskId=' + encodeURIComponent(taskId));
            const data = await res.json();
            networkErrorStreak = 0;

            if (!data.success) {
                // 查询接口偶发异常时，不立即失败，继续轮询
                console.warn('[轮询告警] status接口返回失败，继续重试:', data.message);
                updateStatusBar('状态查询重试中...');
                await new Promise(r => setTimeout(r, 3000));
                continue;
            }

            const status = data.data?.status;
            if (status === 'completed') {
                const url = data.data?.resultUrl || '';
                if (!url) {
                    // completed 但URL为空：继续轮询几次等待落库
                    console.warn('[生图调试] completed但无图片URL，继续等待:', JSON.stringify(data.data, null, 2));
                    if (emptyUrlRetry < 10) {
                        emptyUrlRetry++;
                        updateGenerationProgress(99, '已完成，等待图片地址...', slotIndex);
                        await new Promise(r => setTimeout(r, 3000));
                        continue;
                    }
                }
                updateGenerationProgress(100, `第${slotIndex + 1}张完成`, slotIndex);
                setTimeout(function () {
                    if (url) {
                        showGenerationResult(url, prompt, meta, slotIndex);
                    } else {
                        showGenerationError('图片生成成功但未拿到地址，请去资产中心查看', prompt, meta, slotIndex);
                    }
                }, 400);
                return;
            }

            if (status === 'failed') {
                showGenerationError(data.data?.errorMessage || '生成失败，请尝试更换提示词或图片后重试', prompt, meta, slotIndex);
                return;
            }

            // 0=排队中 1=生成中：持续轮询，进度卡在 99%
            loopCount += 1;
            progress = Math.min(99, progress + (loopCount < 40 ? 2 : 1));
            updateGenerationProgress(Math.round(progress), `第${slotIndex + 1}张 ${Math.round(progress)}% 生成中...`, slotIndex);
            await new Promise(r => setTimeout(r, interval));
        } catch (e) {
            console.error('[轮询异常]', e);
            networkErrorStreak += 1;
            if (networkErrorStreak >= 20) {
                showGenerationError('网络异常次数过多，请检查网络后重试', prompt, meta, slotIndex);
                return;
            }
            updateStatusBar('网络抖动，自动重试中...');
            await new Promise(r => setTimeout(r, 3500));
        }
    }
}

// ============================
// 模板半屏
// ============================
function openTemplateSheet() {
    const sheet = document.getElementById('template-sheet');
    if (sheet) {
        sheet.classList.remove('hidden');
        sheet.style.display = 'flex';
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }
}

function closeTemplateSheet() {
    const sheet = document.getElementById('template-sheet');
    if (sheet) {
        sheet.classList.add('hidden');
        sheet.style.display = 'none';
    }
}

// ============================
// 页面初始化
// ============================
document.addEventListener('DOMContentLoaded', function () {
    // 初始化 Lucide 图标
    setTimeout(function () {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }, 100);

    // 监听动态内容变化，重新渲染图标
    const observer = new MutationObserver(function () {
        if (typeof lucide !== 'undefined') {
            setTimeout(function () {
                lucide.createIcons();
            }, 50);
        }
    });
    observer.observe(document.body, { childList: true, subtree: true });

    // 确保对话框初始状态隐藏
    const modelDialog = document.getElementById('model-dialog');
    const paramsDialog = document.getElementById('params-dialog');
    if (modelDialog) modelDialog.style.display = 'none';
    if (paramsDialog) paramsDialog.style.display = 'none';

    // Ctrl+Enter / Cmd+Enter 快捷键生成
    const promptInput = document.getElementById('prompt-input');
    if (promptInput) {
        promptInput.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                e.preventDefault();
                handleGenerate();
            }
        });
    }
});
