<?php
$adminSections = [
    'dashboard' => '运营看板',
    'users' => '用户管理',
    'points' => '积分管理',
    'tutorials' => '教程管理',
    'templates' => '模板管理',
];
$section = $_GET['section'] ?? 'dashboard';
if (!array_key_exists($section, $adminSections)) $section = 'dashboard';
$isAdmin = !empty($currentUser) && auth_is_admin();
?>

<div class="max-w-[1200px] mx-auto p-6">
    <h1 class="text-2xl font-semibold text-[#1A1A1A] mb-6">管理后台</h1>

    <?php if (!$isAdmin): ?>
        <div class="bg-white rounded-xl border border-[#E5E5E5] p-8 text-center">
            <p class="text-[#666666] mb-3">你没有管理员权限，无法访问管理后台。</p>
            <a href="?tab=create" class="inline-flex h-9 px-4 items-center justify-center text-sm font-medium bg-[#3B82F6] hover:bg-[#2563EB] text-white rounded-lg transition-colors">返回创作页</a>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-xl border border-[#E5E5E5] mb-4 p-3">
            <div class="flex flex-wrap gap-2">
                <?php foreach ($adminSections as $key => $label): ?>
                    <a
                        href="?tab=admin&section=<?= urlencode($key) ?>"
                        class="px-4 py-2 text-sm rounded-lg transition-colors <?= $section === $key ? 'bg-[#3B82F6] text-white' : 'bg-[#F5F5F5] text-[#666666] hover:bg-[#EAEAEA]' ?>"
                    >
                        <?= htmlspecialchars($label) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php
        $sectionFile = __DIR__ . '/admin/' . $section . '.php';
        if (is_file($sectionFile)) {
            include $sectionFile;
        } else {
            echo '<div class="bg-white rounded-xl border border-[#E5E5E5] p-6 text-sm text-[#666666]">模块不存在</div>';
        }
        ?>
    <?php endif; ?>
</div>
