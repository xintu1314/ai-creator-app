<header class="h-14 bg-white border-b border-[#E5E5E5] flex items-center justify-between px-6 fixed top-0 left-16 right-0 z-40">
    <!-- Left - Breadcrumb -->
    <div class="flex items-center gap-2">
        <span class="text-sm text-[#666666]">首页</span>
        <span class="text-sm text-[#999999]">/</span>
        <span class="text-sm text-[#1A1A1A] font-medium">创作中心</span>
    </div>

    <!-- Right - Actions -->
    <div class="flex items-center gap-2">
        <!-- 灵感库 -->
        <a 
            href="?tab=inspiration"
            class="h-9 px-3 text-sm text-[#666666] hover:text-[#1A1A1A] hover:bg-[#F5F5F5] rounded-lg transition-colors flex items-center gap-1.5"
        >
            <i data-lucide="zap" class="w-4 h-4"></i>
            灵感库
        </a>

        <!-- 邀请有礼 -->
        <button class="h-9 px-3 text-sm bg-[#FFF4E6] hover:bg-[#FFE8CC] text-[#D97706] rounded-lg transition-colors flex items-center gap-1.5">
            <i data-lucide="gift" class="w-4 h-4"></i>
            邀请有礼
        </button>

        <!-- 积分 -->
        <div class="flex items-center gap-2 px-3 h-9 rounded-lg bg-[#F8FAFF] border border-[#E6EEFF]" title="会员赠送积分每天12点重置">
            <i data-lucide="zap" class="w-4 h-4 text-[#3B82F6]"></i>
            <span id="header-points-balance" class="text-sm text-[#1A1A1A] font-medium"><?= (int)($pointsSummary['totalBalance'] ?? 0) ?></span>
            <button type="button" onclick="openPointsDialog()" class="text-sm text-[#3B82F6] hover:text-[#2563EB] transition-colors">
                充值
            </button>
        </div>

        <div class="w-px h-6 bg-[#E5E5E5] mx-1"></div>

        <?php if (!empty($currentUser)): ?>
            <!-- 会员中心 -->
            <button type="button" onclick="openMembershipDialog()" class="h-9 px-3 text-sm text-[#666666] hover:text-[#1A1A1A] hover:bg-[#F5F5F5] rounded-lg transition-colors flex items-center gap-1.5">
                <i data-lucide="diamond" class="w-4 h-4 text-amber-500"></i>
                会员中心
            </button>

            <!-- 通知 -->
            <button class="relative h-9 w-9 flex items-center justify-center text-[#666666] hover:text-[#1A1A1A] hover:bg-[#F5F5F5] rounded-lg transition-colors">
                <i data-lucide="bell" class="w-5 h-5"></i>
                <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full"></span>
            </button>

            <!-- 用户信息 -->
            <div class="h-9 px-3 rounded-lg bg-[#F5F5F5] flex items-center gap-2">
                <button
                    type="button"
                    class="h-7 w-7 rounded-full bg-[#3B82F6] flex items-center justify-center text-white"
                    title="<?= htmlspecialchars($currentUser['account']) ?>"
                >
                    <i data-lucide="user" class="w-4 h-4"></i>
                </button>
                <span class="text-sm text-[#1A1A1A] max-w-[120px] truncate">
                    <?= htmlspecialchars($currentUser['nickname'] ?: $currentUser['account']) ?>
                </span>
                <button type="button" onclick="logout()" class="text-xs text-[#3B82F6] hover:text-[#2563EB]">
                    退出
                </button>
            </div>
        <?php else: ?>
            <button
                type="button"
                onclick="openAuthDialog('login')"
                class="h-9 px-4 text-sm text-[#3B82F6] hover:bg-[#F0F7FF] rounded-lg transition-colors"
            >
                登录
            </button>
            <button
                type="button"
                onclick="openAuthDialog('register')"
                class="h-9 px-4 text-sm font-medium bg-[#3B82F6] hover:bg-[#2563EB] text-white rounded-lg transition-colors"
            >
                注册
            </button>
        <?php endif; ?>
    </div>
</header>
