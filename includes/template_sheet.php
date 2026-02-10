<?php
// 模板半屏组件
?>
<!-- Template Sheet - Half Screen -->
<div id="template-sheet" class="hidden fixed inset-x-0 bottom-0 z-50" onclick="closeTemplateSheet()" style="display: none;">
    <div class="bg-white rounded-t-2xl shadow-2xl h-[60vh] max-h-[60vh] flex flex-col" onclick="event.stopPropagation()">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-[#E5E5E5] flex flex-col flex-shrink-0">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-medium text-[#1A1A1A]">
                    <?= $creationType === 'image' ? '图片灵感' : '视频灵感' ?>
                </h2>
                <button
                    onclick="closeTemplateSheet()"
                    class="p-2 hover:bg-[#F5F5F5] rounded-lg transition-colors"
                >
                    <i data-lucide="x" class="w-5 h-5 text-[#666666]"></i>
                </button>
            </div>
            
            <!-- Category Filter -->
            <div class="flex items-center gap-2 overflow-x-auto pb-2">
                <?php 
                $categories = ['全部', '室内', '景观', '建筑', '电商', '人物', '动物', '自然'];
                foreach ($categories as $category): 
                ?>
                    <button
                        onclick="setTemplateCategory('<?= $category ?>')"
                        id="category-btn-<?= htmlspecialchars($category) ?>"
                        class="px-4 py-1.5 text-sm font-medium rounded-lg whitespace-nowrap transition-colors template-category-btn"
                        data-category="<?= htmlspecialchars($category) ?>"
                    >
                        <?= htmlspecialchars($category) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Templates Grid -->
        <div class="flex-1 overflow-y-auto p-4 min-h-0">
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3" id="template-grid">
                <?php foreach ($templates as $template): ?>
                    <div
                        class="group cursor-pointer template-item"
                        data-template-id="<?= $template['id'] ?>"
                        data-category="<?= htmlspecialchars($template['category'] ?? '') ?>"
                        onclick="selectTemplate(<?= htmlspecialchars(json_encode($template, JSON_UNESCAPED_UNICODE)) ?>)"
                    >
                        <div class="relative rounded-xl overflow-hidden shadow-md group-hover:shadow-lg transition-all duration-300 aspect-[3/4]">
                            <!-- Image -->
                            <img
                                src="<?= htmlspecialchars($template['image']) ?>"
                                alt="<?= htmlspecialchars($template['title']) ?>"
                                class="w-full h-full object-cover"
                            />
                            
                            <!-- Model Tag -->
                            <div class="absolute top-1.5 left-1.5">
                                <span class="px-1.5 py-0.5 text-[9px] bg-black/60 text-white rounded backdrop-blur-sm">
                                    <?= htmlspecialchars($template['model']) ?>
                                </span>
                            </div>

                            <!-- Title Overlay -->
                            <div class="absolute bottom-0 left-0 right-0 p-1.5 bg-gradient-to-t from-black/80 to-transparent">
                                <p class="text-[10px] text-white line-clamp-2 leading-tight">
                                    <?= htmlspecialchars($template['title']) ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Bottom Action Bar -->
        <div id="template-action-bar" class="hidden border-t border-[#E5E5E5] bg-white p-4 flex-shrink-0">
            <div class="max-w-[1200px] mx-auto flex items-center gap-4">
                <!-- Preview Image -->
                <div class="w-20 h-20 rounded-lg overflow-hidden flex-shrink-0">
                    <img
                        id="selected-template-image"
                        src=""
                        alt=""
                        class="w-full h-full object-cover"
                    />
                </div>

                <!-- Input Area -->
                <div class="flex-1 flex items-center gap-3">
                    <div class="w-[80px] h-[80px] border-2 border-dashed border-[#E5E5E5] rounded-lg flex items-center justify-center cursor-pointer hover:border-[#3B82F6] hover:bg-[#F0F7FF] transition-all">
                        <span class="text-2xl text-[#999999]">+</span>
                    </div>
                    <input
                        type="text"
                        id="template-prompt-input"
                        placeholder="试试描述一段简短的故事情节，最关键的是主体、环境、时间、风格"
                        class="flex-1 h-[80px] px-4 text-sm text-[#1A1A1A] placeholder:text-[#999999] border border-[#E5E5E5] rounded-lg focus:outline-none focus:border-[#3B82F6] transition-colors"
                    />
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-3">
                    <button
                        onclick="makeSimilar()"
                        class="h-[80px] px-6 text-sm font-medium bg-[#3B82F6] hover:bg-[#2563EB] text-white rounded-lg transition-colors whitespace-nowrap"
                    >
                        做同款
                    </button>
                    <button
                        onclick="generateFromTemplate()"
                        class="h-[80px] px-6 text-sm font-medium bg-white border border-[#E5E5E5] hover:bg-[#F5F5F5] text-[#1A1A1A] rounded-lg transition-colors whitespace-nowrap"
                    >
                        生成
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let selectedTemplateData = null;
let currentCategory = '全部';

function setTemplateCategory(category) {
    currentCategory = category;
    
    // 重置选中的模板
    selectedTemplateData = null;
    const actionBar = document.getElementById('template-action-bar');
    if (actionBar) {
        actionBar.classList.add('hidden');
    }
    
    // 清除所有选中状态
    document.querySelectorAll('.template-item').forEach(item => {
        item.classList.remove('ring-2', 'ring-[#3B82F6]', 'rounded-xl', 'p-1');
    });
    
    // 更新按钮样式
    document.querySelectorAll('.template-category-btn').forEach(btn => {
        const btnCategory = btn.getAttribute('data-category');
        if (btnCategory === category) {
            btn.classList.remove('bg-[#F5F5F5]', 'text-[#666666]');
            btn.classList.add('bg-[#3B82F6]', 'text-white');
        } else {
            btn.classList.remove('bg-[#3B82F6]', 'text-white');
            btn.classList.add('bg-[#F5F5F5]', 'text-[#666666]');
        }
    });
    
    // 过滤模板
    const templateItems = document.querySelectorAll('.template-item');
    templateItems.forEach(item => {
        const itemCategory = item.getAttribute('data-category') || '';
        if (category === '全部' || itemCategory === category) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
}

function selectTemplate(template) {
    selectedTemplateData = template;
    
    // 更新选中状态
    document.querySelectorAll('.template-item').forEach(item => {
        const itemId = item.getAttribute('data-template-id');
        if (itemId === template.id) {
            item.classList.add('ring-2', 'ring-[#3B82F6]', 'rounded-xl', 'p-1');
        } else {
            item.classList.remove('ring-2', 'ring-[#3B82F6]', 'rounded-xl', 'p-1');
        }
    });
    
    // 显示底部操作栏
    const actionBar = document.getElementById('template-action-bar');
    const previewImage = document.getElementById('selected-template-image');
    if (actionBar && previewImage) {
        actionBar.classList.remove('hidden');
        previewImage.src = template.image;
        previewImage.alt = template.title;
    }
}

function makeSimilar() {
    if (selectedTemplateData) {
        useTemplate(selectedTemplateData);
        closeTemplateSheet();
    }
}

function generateFromTemplate() {
    if (selectedTemplateData) {
        useTemplate(selectedTemplateData);
        closeTemplateSheet();
    }
}

// 初始化类别按钮样式
document.addEventListener('DOMContentLoaded', function() {
    const allBtn = document.getElementById('category-btn-全部');
    if (allBtn) {
        allBtn.classList.add('bg-[#3B82F6]', 'text-white');
        allBtn.classList.remove('bg-[#F5F5F5]', 'text-[#666666]');
    }
});
</script>
