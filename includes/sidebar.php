<?php
$navItems = [
    ['id' => 'create', 'icon' => 'sparkles', 'label' => '创作'],
    ['id' => 'assets', 'icon' => 'folder-open', 'label' => '资产'],
    ['id' => 'publish', 'icon' => 'send', 'label' => '发布'],
    ['id' => 'tutorial', 'icon' => 'book-open', 'label' => '教程'],
];
?>
<aside class="fixed left-0 top-0 h-screen w-16 bg-white border-r border-[#E5E5E5] flex flex-col items-center py-4 z-50">
    <!-- Logo -->
    <div class="mb-6">
        <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center">
            <i data-lucide="sparkles" class="w-5 h-5 text-white"></i>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 flex flex-col items-center gap-1">
        <?php foreach ($navItems as $item): ?>
            <?php $isActive = $activeTab === $item['id']; ?>
            <button
                onclick="changeTab('<?= $item['id'] ?>')"
                class="w-10 h-10 rounded-lg flex items-center justify-center transition-all duration-200 hover:bg-[#F5F5F5] <?= $isActive ? 'bg-[#EEF2FF]' : '' ?>"
                title="<?= htmlspecialchars($item['label']) ?>"
            >
                <i data-lucide="<?= $item['icon'] ?>" class="w-5 h-5 transition-colors duration-200 <?= $isActive ? 'text-[#3B82F6]' : 'text-[#666666]' ?>"></i>
            </button>
        <?php endforeach; ?>
    </nav>

    <!-- Settings -->
    <button 
        class="w-10 h-10 rounded-lg flex items-center justify-center transition-all duration-200 hover:bg-[#F5F5F5]"
        title="设置"
    >
        <i data-lucide="settings" class="w-5 h-5 text-[#666666]"></i>
    </button>
</aside>
