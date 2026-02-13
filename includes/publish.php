<?php
// $imageModels, $videoModels, $categories 由 index.php 从 api/data 加载
$contentType = $_POST['content_type'] ?? 'image';
$selectedModel = $_POST['selected_model'] ?? '';
$selectedCategory = $_POST['selected_category'] ?? '';
$currentUserId = !empty($currentUser['id']) ? (int)$currentUser['id'] : 0;
$myPublishedTemplates = $currentUserId > 0 ? get_templates_by_user($currentUserId, 'all', 30) : [];

$availableModels = $contentType === 'image' ? $imageModels : $videoModels;
?>
<div class="max-w-[1200px] mx-auto p-6">
    <h1 class="text-2xl font-semibold text-[#1A1A1A] mb-6">发布模板</h1>
    
    <div class="bg-white rounded-lg p-6 border border-[#E5E5E5]">
        <form id="publish-form" method="POST" action="?tab=publish" onsubmit="return handlePublishSubmit(event)">
            <!-- Content Type Selection (Image/Video) -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-[#1A1A1A] mb-3">类型</label>
                <div class="flex gap-4">
                    <button
                        type="button"
                        onclick="setContentType('image')"
                        id="btn-image"
                        class="px-4 py-2 rounded-lg transition-colors flex items-center gap-2 bg-[#3B82F6] text-white"
                    >
                        <i data-lucide="image" class="w-4 h-4"></i>
                        图片
                    </button>
                    <button
                        type="button"
                        onclick="setContentType('video')"
                        id="btn-video"
                        class="px-4 py-2 rounded-lg transition-colors flex items-center gap-2 bg-[#F5F5F5] text-[#666666] hover:bg-[#E5E5E5]"
                    >
                        <i data-lucide="video" class="w-4 h-4"></i>
                        视频
                    </button>
                </div>
                <input type="hidden" name="content_type" id="content_type" value="image">
            </div>

            <!-- Model Selection -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-[#1A1A1A] mb-2">选择模型</label>
                <button
                    type="button"
                    onclick="openPublishModelDialog()"
                    class="w-full px-4 py-2 border border-[#E5E5E5] rounded-lg flex items-center justify-between hover:border-[#3B82F6] transition-colors bg-white"
                >
                    <span id="selected-model-display" class="text-[#999999]">请选择模型</span>
                    <i data-lucide="chevron-down" class="w-4 h-4 text-[#666666]"></i>
                </button>
                <input type="hidden" name="selected_model" id="selected_model" value="">
            </div>

            <!-- Category Selection -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-[#1A1A1A] mb-2">分类</label>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($categories as $category): ?>
                        <button
                            type="button"
                            onclick="setCategory('<?= htmlspecialchars($category) ?>')"
                            id="category-btn-<?= htmlspecialchars($category) ?>"
                            class="px-4 py-1.5 text-sm font-medium rounded-lg transition-colors bg-[#F5F5F5] text-[#666666] hover:bg-[#E5E5E5]"
                        >
                            <?= htmlspecialchars($category) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="selected_category" id="selected_category" value="">
            </div>

            <!-- Preview Media (上传到 OSS) -->
            <div class="mb-4">
                <label id="publish-media-label" class="block text-sm font-medium text-[#1A1A1A] mb-2">预览图</label>
                <div class="flex gap-4 items-start">
                    <div 
                        id="publish-image-upload" 
                        class="w-[120px] h-[120px] border-2 border-dashed border-[#E5E5E5] rounded-lg flex flex-col items-center justify-center cursor-pointer hover:border-[#3B82F6] hover:bg-[#F0F7FF] transition-all relative overflow-hidden"
                        onclick="document.getElementById('publish-image-input').click()"
                    >
                        <input type="file" id="publish-image-input" accept="image/*" class="hidden" onchange="handlePublishMediaUpload(this)">
                        <div id="publish-image-preview" class="w-full h-full flex flex-col items-center justify-center">
                            <i data-lucide="image-plus" class="w-8 h-8 text-[#999999] mb-1"></i>
                            <span class="text-xs text-[#999999]">点击上传</span>
                        </div>
                    </div>
                    <div id="publish-media-hint" class="flex-1 text-xs text-[#666666]">支持 JPG、PNG、GIF、WebP，最大 5MB。将存储至阿里云 OSS。</div>
                </div>
                <input type="hidden" name="image" id="publish-image-url" value="">
            </div>

            <!-- Title -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-[#1A1A1A] mb-2">模板标题</label>
                <input 
                    type="text" 
                    name="title"
                    class="w-full px-4 py-2 border border-[#E5E5E5] rounded-lg focus:outline-none focus:border-[#3B82F6]"
                    placeholder="输入模板标题（提示词）"
                    required
                />
            </div>

            <!-- Content -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-[#1A1A1A] mb-2">模板内容（提示词）</label>
                <textarea 
                    name="content"
                    class="w-full px-4 py-2 border border-[#E5E5E5] rounded-lg focus:outline-none focus:border-[#3B82F6] min-h-[200px]"
                    placeholder="输入模板内容（提示词）"
                    required
                ></textarea>
            </div>

            <!-- Publish Button -->
            <button 
                type="submit"
                class="px-6 py-2 bg-[#3B82F6] hover:bg-[#2563EB] text-white rounded-lg transition-colors"
            >
                发布
            </button>
        </form>
    </div>

    <!-- Publish History -->
    <div class="mt-6 bg-white rounded-lg p-6 border border-[#E5E5E5]">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-medium text-[#1A1A1A]">我的发布历史</h2>
            <?php if ($currentUserId <= 0): ?>
                <button type="button" onclick="openAuthDialog('login')" class="h-8 px-3 text-xs font-medium bg-[#3B82F6] hover:bg-[#2563EB] text-white rounded-lg transition-colors">
                    登录后查看
                </button>
            <?php endif; ?>
        </div>

        <?php if ($currentUserId <= 0): ?>
            <p class="text-sm text-[#666666]">登录后可查看你发布过的模板。</p>
        <?php elseif (empty($myPublishedTemplates)): ?>
            <p class="text-sm text-[#666666]">你还没有发布过模板。</p>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <?php foreach ($myPublishedTemplates as $tpl): ?>
                    <div class="bg-[#FAFAFA] border border-[#EAEAEA] rounded-xl overflow-hidden">
                        <div class="relative aspect-[3/4] bg-[#F4F4F5]">
                            <?php if (($tpl['type'] ?? 'image') === 'video'): ?>
                                <video
                                    src="<?= htmlspecialchars($tpl['image']) ?>"
                                    class="w-full h-full object-cover"
                                    muted
                                    playsinline
                                    preload="metadata"
                                ></video>
                            <?php else: ?>
                                <img
                                    src="<?= htmlspecialchars($tpl['image']) ?>"
                                    alt="<?= htmlspecialchars($tpl['title']) ?>"
                                    class="w-full h-full object-cover"
                                    loading="lazy"
                                />
                            <?php endif; ?>
                            <div class="absolute top-2 left-2 flex items-center gap-1">
                                <span class="px-2 py-0.5 text-[10px] rounded text-white <?= ($tpl['type'] ?? 'image') === 'video' ? 'bg-purple-500/80' : 'bg-blue-500/80' ?>">
                                    <?= ($tpl['type'] ?? 'image') === 'video' ? '视频' : '图片' ?>
                                </span>
                                <?php if (!empty($tpl['category'])): ?>
                                    <span class="px-2 py-0.5 text-[10px] rounded bg-black/45 text-white">
                                        <?= htmlspecialchars($tpl['category']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="p-3">
                            <p class="text-sm font-medium text-[#1A1A1A] mb-1 line-clamp-1"><?= htmlspecialchars($tpl['title']) ?></p>
                            <p class="text-xs text-[#666666] line-clamp-1"><?= htmlspecialchars($tpl['model']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Model Select Dialog for Publish -->
<div id="publish-model-dialog" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50" onclick="closePublishModelDialog()">
    <div class="bg-white rounded-lg max-w-[680px] w-full mx-4" onclick="event.stopPropagation()">
        <div class="px-6 py-4 border-b border-[#E5E5E5] flex items-center justify-between">
            <h2 class="text-base font-medium text-[#1A1A1A]">选择模型</h2>
            <button onclick="closePublishModelDialog()" class="p-2 hover:bg-[#F5F5F5] rounded-lg">
                <i data-lucide="x" class="w-5 h-5 text-[#666666]"></i>
            </button>
        </div>
        <div class="p-4 max-h-[500px] overflow-y-auto">
            <div class="grid grid-cols-2 gap-3" id="publish-models-grid">
                <!-- Models will be dynamically loaded based on content type -->
            </div>
        </div>
    </div>
</div>

<script>
const imageModels = <?= json_encode($imageModels, JSON_UNESCAPED_UNICODE) ?>;
const videoModels = <?= json_encode($videoModels, JSON_UNESCAPED_UNICODE) ?>;
let publishSubmitting = false;

function publishEscapeHtml(s) {
    if (!s) return '';
    const div = document.createElement('div');
    div.textContent = String(s);
    return div.innerHTML;
}

function publishSanitizeMediaUrl(url) {
    const raw = String(url || '').trim();
    if (!raw) return '';
    try {
        const parsed = new URL(raw, window.location.origin);
        if (!['http:', 'https:'].includes(parsed.protocol)) return '';
        return parsed.href;
    } catch (e) {
        return '';
    }
}

function setContentType(type) {
    document.getElementById('content_type').value = type;
    document.getElementById('selected_model').value = '';
    document.getElementById('selected-model-display').textContent = '请选择模型';
    document.getElementById('selected-model-display').classList.remove('text-[#1A1A1A]');
    document.getElementById('selected-model-display').classList.add('text-[#999999]');
    
    if (type === 'image') {
        document.getElementById('btn-image').classList.remove('bg-[#F5F5F5]', 'text-[#666666]');
        document.getElementById('btn-image').classList.add('bg-[#3B82F6]', 'text-white');
        document.getElementById('btn-video').classList.remove('bg-[#3B82F6]', 'text-white');
        document.getElementById('btn-video').classList.add('bg-[#F5F5F5]', 'text-[#666666]');
    } else {
        document.getElementById('btn-video').classList.remove('bg-[#F5F5F5]', 'text-[#666666]');
        document.getElementById('btn-video').classList.add('bg-[#3B82F6]', 'text-white');
        document.getElementById('btn-image').classList.remove('bg-[#3B82F6]', 'text-white');
        document.getElementById('btn-image').classList.add('bg-[#F5F5F5]', 'text-[#666666]');
    }
    resetPublishMediaPreview();
    updatePublishMediaUploaderByType(type);
}

function setCategory(category) {
    document.getElementById('selected_category').value = category;
    // Update button styles
    document.querySelectorAll('[id^="category-btn-"]').forEach(btn => {
        const btnCategory = btn.id.replace('category-btn-', '');
        if (btnCategory === category) {
            btn.classList.remove('bg-[#F5F5F5]', 'text-[#666666]');
            btn.classList.add('bg-[#3B82F6]', 'text-white');
        } else {
            btn.classList.remove('bg-[#3B82F6]', 'text-white');
            btn.classList.add('bg-[#F5F5F5]', 'text-[#666666]');
        }
    });
}

function openPublishModelDialog() {
    const contentType = document.getElementById('content_type').value;
    const models = contentType === 'image' ? imageModels : videoModels;
    const grid = document.getElementById('publish-models-grid');
    
    grid.innerHTML = models.map(model => `
        <button
            type="button"
            onclick="selectPublishModel('${model.id}', '${model.name.replace(/'/g, "\\'")}')"
            class="text-left p-4 rounded-xl border border-[#E5E5E5] bg-[#F9FAFB] hover:border-[#3B82F6] hover:bg-[#F0F7FF] transition-all duration-200"
        >
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 ${model.icon === 'banana' ? 'bg-gradient-to-br from-yellow-400 to-yellow-600' : (model.icon === 'doubao' ? 'bg-gradient-to-br from-emerald-500 to-teal-600' : (model.icon === 'sora' ? 'bg-gradient-to-br from-purple-500 to-pink-500' : 'bg-gradient-to-br from-blue-400 to-blue-600'))}">
                    <div class="w-5 h-5 bg-white/90 rounded-lg"></div>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="font-medium text-[#1A1A1A] text-sm mb-1">${model.name}</div>
                    <p class="text-xs text-[#666666] line-clamp-2 mb-2">${model.description}</p>
                    <div class="flex flex-wrap gap-1.5">
                        ${model.tags.map(tag => `<span class="px-2 py-0.5 text-[10px] bg-white border border-[#E5E5E5] rounded text-[#666666]">${tag}</span>`).join('')}
                    </div>
                </div>
            </div>
        </button>
    `).join('');
    
    document.getElementById('publish-model-dialog').classList.remove('hidden');
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

function closePublishModelDialog() {
    document.getElementById('publish-model-dialog').classList.add('hidden');
}

function selectPublishModel(modelId, modelName) {
    document.getElementById('selected_model').value = modelId;
    document.getElementById('selected-model-display').textContent = modelName;
    document.getElementById('selected-model-display').classList.remove('text-[#999999]');
    document.getElementById('selected-model-display').classList.add('text-[#1A1A1A]');
    closePublishModelDialog();
}

function getPublishMediaEmptyState(type) {
    if (type === 'video') {
        return '<i data-lucide="video" class="w-8 h-8 text-[#999999] mb-1"></i><span class="text-xs text-[#999999]">点击上传</span>';
    }
    return '<i data-lucide="image-plus" class="w-8 h-8 text-[#999999] mb-1"></i><span class="text-xs text-[#999999]">点击上传</span>';
}

function resetPublishMediaPreview() {
    const contentType = document.getElementById('content_type').value;
    const preview = document.getElementById('publish-image-preview');
    const hiddenInput = document.getElementById('publish-image-url');
    const fileInput = document.getElementById('publish-image-input');
    if (hiddenInput) hiddenInput.value = '';
    if (fileInput) fileInput.value = '';
    if (preview) preview.innerHTML = getPublishMediaEmptyState(contentType);
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function updatePublishMediaUploaderByType(type) {
    const normalizedType = type === 'video' ? 'video' : 'image';
    const label = document.getElementById('publish-media-label');
    const hint = document.getElementById('publish-media-hint');
    const fileInput = document.getElementById('publish-image-input');
    if (label) label.textContent = normalizedType === 'video' ? '预览视频' : '预览图';
    if (hint) {
        hint.textContent = normalizedType === 'video'
            ? '支持 MP4、WebM、MOV、AVI、MPEG，最大 200MB。将存储至阿里云 OSS。'
            : '支持 JPG、PNG、GIF、WebP，最大 5MB。将存储至阿里云 OSS。';
    }
    if (fileInput) {
        fileInput.accept = normalizedType === 'video'
            ? 'video/mp4,video/webm,video/quicktime,video/x-msvideo,video/mpeg,.mp4,.webm,.mov,.avi,.mpeg,.mpg'
            : 'image/*';
    }
}

async function handlePublishMediaUpload(input) {
    const file = input.files[0];
    const contentType = document.getElementById('content_type').value === 'video' ? 'video' : 'image';
    if (!file) return;
    if (contentType === 'image' && !file.type.startsWith('image/')) return;
    if (contentType === 'video' && !file.type.startsWith('video/')) return;
    const formData = new FormData();
    formData.append('file', file);
    formData.append('prefix', contentType === 'video' ? 'assets/videos/templates' : 'assets/images/templates');
    try {
        const preview = document.getElementById('publish-image-preview');
        preview.innerHTML = '<span class="text-xs text-[#3B82F6]">上传中...</span>';
        const uploadEndpoint = contentType === 'video' ? 'api/upload/video.php' : 'api/upload/image.php';
        const res = await fetch(uploadEndpoint, {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success && data.data?.url) {
            const safeUrl = publishSanitizeMediaUrl(data.data.url);
            if (!safeUrl) {
                preview.innerHTML = '<span class="text-xs text-red-500">返回地址无效</span>';
                return;
            }
            document.getElementById('publish-image-url').value = safeUrl;
            if (contentType === 'video') {
                preview.innerHTML = `<video src="${safeUrl}" class="w-full h-full object-cover" controls muted playsinline preload="metadata"></video>`;
            } else {
                preview.innerHTML = `<img src="${safeUrl}" alt="预览" class="w-full h-full object-cover" />`;
            }
        } else {
            preview.innerHTML = '<span class="text-xs text-red-500">' + publishEscapeHtml(data.message || '上传失败') + '</span>';
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    } catch (err) {
        document.getElementById('publish-image-preview').innerHTML = '<span class="text-xs text-red-500">上传失败</span>';
    }
}

async function handlePublishSubmit(e) {
    e.preventDefault();
    if (publishSubmitting) return false;
    const form = document.getElementById('publish-form');
    const submitBtn = form.querySelector('button[type="submit"]');
    const contentType = document.getElementById('content_type').value;
    const modelId = document.getElementById('selected_model').value;
    const category = document.getElementById('selected_category').value;
    const title = form.querySelector('[name="title"]').value.trim();
    const content = form.querySelector('[name="content"]').value.trim();
    const image = document.getElementById('publish-image-url').value.trim();

    if (!modelId || !category || !title || !content) {
        alert('请填写完整：模型、分类、标题、内容');
        return false;
    }

    publishSubmitting = true;
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-70', 'cursor-not-allowed');
        submitBtn.textContent = '发布中...';
    }

    try {
        const modelName = document.getElementById('selected-model-display').textContent;
        const response = await fetch('api/publish/create.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                contentType,
                modelId,
                modelName: modelName !== '请选择模型' ? modelName : '',
                category,
                title,
                content,
                image: image || undefined
            }),
        });
        const data = await response.json();
        if (data.success) {
            alert('发布成功！');
            form.reset();
            document.getElementById('selected_model').value = '';
            document.getElementById('selected-model-display').textContent = '请选择模型';
            document.getElementById('selected-model-display').classList.add('text-[#999999]');
            resetPublishMediaPreview();
        } else {
            alert('发布失败：' + (data.message || '未知错误'));
        }
    } catch (err) {
        console.error(err);
        alert('请求失败，请稍后重试');
    } finally {
        publishSubmitting = false;
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-70', 'cursor-not-allowed');
            submitBtn.textContent = '发布模板';
        }
    }
    return false;
}

document.addEventListener('DOMContentLoaded', function () {
    updatePublishMediaUploaderByType(document.getElementById('content_type').value || 'image');
});
</script>
