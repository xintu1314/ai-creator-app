<header class="h-14 bg-white border-b border-[#E5E5E5] flex items-center justify-between px-6 fixed top-0 left-16 right-0 z-40">
    <!-- Left - Breadcrumb -->
    <?php
    $tabTitleMap = [
        'create' => '创作中心',
        'assets' => '资产中心',
        'profile' => '用户中心',
        'publish' => '发布模板',
        'tutorial' => '教程中心',
        'inspiration' => '灵感库',
        'admin' => '管理后台',
    ];
    $currentTabTitle = $tabTitleMap[$activeTab] ?? '创作中心';
    ?>
    <div class="flex items-center gap-2">
        <span class="text-sm text-[#666666]">首页</span>
        <span class="text-sm text-[#999999]">/</span>
        <span class="text-sm text-[#1A1A1A] font-medium"><?= htmlspecialchars($currentTabTitle) ?></span>
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

        <!-- 积分 -->
        <div class="flex items-center gap-2 px-3 h-9 rounded-lg bg-[#F8FAFF] border border-[#E6EEFF]" title="每日签到领积分，当天有效，次日12点清零">
            <i data-lucide="zap" class="w-4 h-4 text-[#3B82F6]"></i>
            <span id="header-points-balance" class="text-sm text-[#1A1A1A] font-medium"><?= (int)($pointsSummary['totalBalance'] ?? 0) ?></span>
            <button type="button" onclick="openPointsDialog()" class="text-sm text-[#3B82F6] hover:text-[#2563EB] transition-colors">
                充值
            </button>
        </div>

        <?php if (!empty($currentUser)): ?>
        <button
            type="button"
            id="header-gen-status"
            onclick="jumpToPendingGeneration()"
            class="hidden h-9 px-3 text-sm rounded-lg transition-colors text-[#4F46E5] bg-[#EEF2FF] hover:bg-[#E0E7FF] border border-[#C7D2FE]"
            title="查看进行中的生成任务"
        >
            生成中 0 / 已完成 0
        </button>
        <?php endif; ?>

        <?php if (!empty($currentUser)): ?>
        <?php
            $headerCheckin = $pointsSummary['checkin'] ?? [];
            $headerCheckedToday = !empty($headerCheckin['checkedToday']);
            $headerCheckinReward = (int)($headerCheckin['rewardPoints'] ?? 16);
        ?>
        <button
            type="button"
            id="header-checkin-btn"
            onclick="dailyCheckin()"
            <?= $headerCheckedToday ? 'disabled' : '' ?>
            class="h-9 px-3 text-sm rounded-lg transition-colors <?= $headerCheckedToday ? 'bg-[#F5F5F5] text-[#999999] cursor-not-allowed' : 'text-[#666666] hover:text-[#1A1A1A] hover:bg-[#F5F5F5]' ?>"
            title="每日签到领积分，当天有效，次日12点清零"
        >
            <?= $headerCheckedToday ? '今日已签到' : ('每日签到 +' . $headerCheckinReward) ?>
        </button>
        <?php endif; ?>

        <div class="w-px h-6 bg-[#E5E5E5] mx-1"></div>

        <?php if (!empty($currentUser)): ?>
            <!-- 会员中心 -->
            <button type="button" onclick="openMembershipDialog()" class="h-9 px-3 text-sm text-[#666666] hover:text-[#1A1A1A] hover:bg-[#F5F5F5] rounded-lg transition-colors flex items-center gap-1.5">
                <i data-lucide="diamond" class="w-4 h-4 text-amber-500"></i>
                会员中心
            </button>
            <?php
                $headerMembership = $pointsSummary['membership'] ?? null;
                $headerMembershipActive = !empty($headerMembership) && (($headerMembership['status'] ?? '') === 'active');
            ?>
            <span
                id="header-membership-status"
                class="h-7 px-2.5 rounded-full text-xs flex items-center border <?= $headerMembershipActive ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-[#F5F5F5] text-[#666] border-[#E5E5E5]' ?>"
            >
                <?= $headerMembershipActive ? '会员中' : '普通用户' ?>
            </span>

            <!-- 通知 -->
            <button class="relative h-9 w-9 flex items-center justify-center text-[#666666] hover:text-[#1A1A1A] hover:bg-[#F5F5F5] rounded-lg transition-colors">
                <i data-lucide="bell" class="w-5 h-5"></i>
                <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full"></span>
            </button>

            <!-- 用户信息 -->
            <div class="h-9 px-3 rounded-lg bg-[#F5F5F5] flex items-center gap-2">
                <a
                    href="?tab=profile"
                    class="flex items-center gap-2 min-w-0 hover:opacity-90 transition-opacity"
                    title="进入用户中心"
                >
                    <span
                        class="h-7 w-7 rounded-full bg-[#3B82F6] flex items-center justify-center text-white flex-shrink-0"
                        title="<?= htmlspecialchars($currentUser['account']) ?>"
                    >
                        <i data-lucide="user" class="w-4 h-4"></i>
                    </span>
                    <span class="text-sm text-[#1A1A1A] max-w-[120px] truncate">
                        <?= htmlspecialchars($currentUser['nickname'] ?: $currentUser['account']) ?>
                    </span>
                </a>
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
