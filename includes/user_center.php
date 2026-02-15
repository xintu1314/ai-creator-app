<?php
require_once __DIR__ . '/../api/common/db.php';

$currentUserId = !empty($currentUser['id']) ? (int)$currentUser['id'] : 0;
$userProfile = null;
$recentLedger = [];
$recentAssets = [];
$myTemplates = [];

if ($currentUserId > 0) {
    try {
        $pdo = get_db();

        $userStmt = $pdo->prepare("
            SELECT id, account, phone, nickname, created_at
            FROM users
            WHERE id = :id
            LIMIT 1
        ");
        $userStmt->execute(['id' => $currentUserId]);
        $userProfile = $userStmt->fetch(PDO::FETCH_ASSOC) ?: null;

        $ledgerStmt = $pdo->prepare("
            SELECT change_amount, balance_after, source, description, created_at
            FROM points_ledger
            WHERE user_id = :user_id
            ORDER BY created_at DESC
            LIMIT 10
        ");
        $ledgerStmt->execute(['user_id' => $currentUserId]);
        $recentLedger = $ledgerStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        // keep graceful fallback
    }

    $recentAssets = get_assets('all', 1, 6, $currentUserId);
    if (function_exists('get_templates_by_user')) {
        $myTemplates = get_templates_by_user($currentUserId, 'all', 6);
    }
}
?>
<div class="max-w-[1200px] mx-auto p-6">
    <h1 class="text-2xl font-semibold text-[#1A1A1A] mb-6">用户中心</h1>

    <?php if (empty($currentUser)): ?>
        <div class="bg-white rounded-xl p-8 border border-[#E5E5E5] text-center">
            <p class="text-[#666666] mb-4">登录后可查看你的个人信息、积分与历史记录。</p>
            <button type="button" onclick="openAuthDialog('login')" class="h-10 px-5 text-sm font-medium bg-[#3B82F6] hover:bg-[#2563EB] text-white rounded-lg transition-colors">
                去登录
            </button>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
            <div class="lg:col-span-2 bg-white rounded-xl p-5 border border-[#E5E5E5]">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-center gap-4 min-w-0">
                        <div class="w-14 h-14 rounded-full bg-[#3B82F6] text-white flex items-center justify-center">
                            <i data-lucide="user" class="w-7 h-7"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="text-lg font-semibold text-[#1A1A1A] truncate">
                                <?= htmlspecialchars(($userProfile['nickname'] ?? '') ?: ($userProfile['account'] ?? '用户')) ?>
                            </div>
                            <div class="text-sm text-[#666666] truncate">账号：<?= htmlspecialchars($userProfile['account'] ?? '-') ?></div>
                            <div class="text-sm text-[#666666] truncate">手机号：<?= htmlspecialchars($userProfile['phone'] ?? '-') ?></div>
                        </div>
                    </div>
                    <button type="button" onclick="logout()" class="h-9 px-3 text-sm text-[#3B82F6] hover:bg-[#F0F7FF] rounded-lg transition-colors">
                        退出登录
                    </button>
                </div>
                <div class="mt-4 pt-4 border-t border-[#F0F0F0] text-sm text-[#666666]">
                    注册时间：<?= !empty($userProfile['created_at']) ? htmlspecialchars(date('Y-m-d H:i', strtotime((string)$userProfile['created_at']))) : '-' ?>
                </div>
            </div>

            <div class="bg-white rounded-xl p-5 border border-[#E5E5E5]">
                <div class="text-sm text-[#666666] mb-2">积分总览</div>
                <div class="text-3xl font-semibold text-[#1A1A1A] mb-3"><?= (int)($pointsSummary['totalBalance'] ?? 0) ?></div>
                <?php
                    $checkin = $pointsSummary['checkin'] ?? [];
                    $checkedToday = !empty($checkin['checkedToday']);
                    $checkinReward = (int)($checkin['rewardPoints'] ?? 16);
                ?>
                <div class="space-y-1 text-sm">
                    <div class="text-[#666666]">付费积分：<span class="text-[#1A1A1A] font-medium"><?= (int)($pointsSummary['paidBalance'] ?? 0) ?></span></div>
                    <div class="text-[#666666]">赠送积分：<span class="text-[#1A1A1A] font-medium"><?= (int)($pointsSummary['bonusBalance'] ?? 0) ?></span></div>
                </div>
                <div class="mt-3 text-xs text-[#666666]" id="user-center-checkin-tip">
                    <?= $checkedToday ? '今日已签到，赠送积分当天有效，次日 12:00 清零' : ('今日未签到，签到可领 ' . $checkinReward . ' 积分（当天有效）') ?>
                </div>
                <div class="mt-4 flex gap-2">
                    <button
                        type="button"
                        id="user-center-checkin-btn"
                        onclick="dailyCheckin()"
                        <?= $checkedToday ? 'disabled' : '' ?>
                        class="h-9 px-3 text-sm <?= $checkedToday ? 'bg-[#F5F5F5] text-[#999999] cursor-not-allowed' : 'bg-emerald-600 hover:bg-emerald-700 text-white' ?> rounded-lg transition-colors"
                    >
                        <?= $checkedToday ? '今日已签到' : '每日签到' ?>
                    </button>
                    <button type="button" onclick="openPointsDialog()" class="h-9 px-3 text-sm bg-[#3B82F6] hover:bg-[#2563EB] text-white rounded-lg transition-colors">充值积分</button>
                    <button type="button" onclick="openMembershipDialog()" class="h-9 px-3 text-sm bg-[#F5F5F5] hover:bg-[#EDEDED] text-[#1A1A1A] rounded-lg transition-colors">会员中心</button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
            <div class="bg-white rounded-xl p-5 border border-[#E5E5E5]">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-base font-medium text-[#1A1A1A]">会员状态</h2>
                    <button type="button" onclick="openMembershipDialog()" class="text-sm text-[#3B82F6] hover:text-[#2563EB]">管理</button>
                </div>
                <?php
                    $membership = $pointsSummary['membership'] ?? null;
                    $membershipActive = !empty($membership) && (($membership['status'] ?? '') === 'active');
                ?>
                <?php if ($membershipActive): ?>
                    <div class="text-sm text-[#666666] space-y-1">
                        <div>套餐：<span class="text-[#1A1A1A] font-medium"><?= htmlspecialchars((string)($membership['planCode'] ?? '-')) ?></span></div>
                        <div>每日签到奖励：<span class="text-[#1A1A1A] font-medium"><?= (int)($membership['dailyBonusPoints'] ?? 0) ?> 分</span></div>
                        <div>到期时间：<span class="text-[#1A1A1A] font-medium"><?= htmlspecialchars((string)($membership['expiresAt'] ?? '-')) ?></span></div>
                        <div>状态：<span class="text-[#1A1A1A] font-medium"><?= htmlspecialchars((string)($membership['status'] ?? '-')) ?></span></div>
                    </div>
                <?php else: ?>
                    <p class="text-sm text-[#666666]">你当前还不是会员；可通过每日签到领取积分。</p>
                <?php endif; ?>
            </div>

            <div class="bg-white rounded-xl p-5 border border-[#E5E5E5]">
                <h2 class="text-base font-medium text-[#1A1A1A] mb-3">最近积分流水</h2>
                <?php if (empty($recentLedger)): ?>
                    <p class="text-sm text-[#666666]">暂无积分流水</p>
                <?php else: ?>
                    <div class="space-y-2">
                        <?php foreach ($recentLedger as $row): ?>
                            <?php $delta = (int)($row['change_amount'] ?? 0); ?>
                            <div class="flex items-center justify-between gap-2 text-sm">
                                <div class="min-w-0">
                                    <div class="text-[#1A1A1A] truncate"><?= htmlspecialchars((string)($row['description'] ?? '积分变动')) ?></div>
                                    <div class="text-xs text-[#999999]"><?= htmlspecialchars(date('m-d H:i', strtotime((string)($row['created_at'] ?? 'now')))) ?></div>
                                </div>
                                <div class="text-right">
                                    <div class="<?= $delta >= 0 ? 'text-emerald-600' : 'text-red-500' ?> font-medium">
                                        <?= $delta >= 0 ? '+' . $delta : (string)$delta ?>
                                    </div>
                                    <div class="text-xs text-[#999999]">余额 <?= (int)($row['balance_after'] ?? 0) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="bg-white rounded-xl p-5 border border-[#E5E5E5]">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-base font-medium text-[#1A1A1A]">最近资产</h2>
                    <a href="?tab=assets" class="text-sm text-[#3B82F6] hover:text-[#2563EB]">查看全部</a>
                </div>
                <?php if (empty($recentAssets)): ?>
                    <p class="text-sm text-[#666666]">还没有生成记录</p>
                <?php else: ?>
                    <div class="grid grid-cols-3 gap-2">
                        <?php foreach ($recentAssets as $asset): ?>
                            <a href="?tab=assets&filter=<?= htmlspecialchars((string)($asset['type'] ?? 'all')) ?>" class="block rounded-lg overflow-hidden bg-[#F7F7F7] border border-[#EFEFEF]">
                                <?php if (($asset['type'] ?? '') === 'video'): ?>
                                    <video src="<?= htmlspecialchars((string)$asset['image']) ?>" class="w-full aspect-[3/4] object-cover" muted playsinline preload="metadata"></video>
                                <?php else: ?>
                                    <img src="<?= htmlspecialchars((string)$asset['image']) ?>" alt="<?= htmlspecialchars((string)($asset['title'] ?? '')) ?>" class="w-full aspect-[3/4] object-cover" loading="lazy" />
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="bg-white rounded-xl p-5 border border-[#E5E5E5]">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-base font-medium text-[#1A1A1A]">最近发布模板</h2>
                    <a href="?tab=publish" class="text-sm text-[#3B82F6] hover:text-[#2563EB]">去发布页</a>
                </div>
                <?php if (empty($myTemplates)): ?>
                    <p class="text-sm text-[#666666]">还没有发布模板</p>
                <?php else: ?>
                    <div class="grid grid-cols-3 gap-2">
                        <?php foreach ($myTemplates as $tpl): ?>
                            <?php
                            $mediaUrl = (string)($tpl['image'] ?? '');
                            $isVideoType = (($tpl['type'] ?? 'image') === 'video');
                            $looksLikeVideo = $mediaUrl !== '' && preg_match('/\.(mp4|webm|mov|m3u8)(\?|$)/i', $mediaUrl);
                            $renderAsVideo = $isVideoType && $looksLikeVideo;
                            ?>
                            <div class="rounded-lg overflow-hidden border border-[#EFEFEF] bg-[#FAFAFA]">
                                <?php if ($renderAsVideo): ?>
                                    <video src="<?= htmlspecialchars($mediaUrl) ?>" class="w-full aspect-[3/4] object-cover" muted playsinline preload="metadata"></video>
                                <?php else: ?>
                                    <img src="<?= htmlspecialchars($mediaUrl) ?>" alt="<?= htmlspecialchars((string)($tpl['title'] ?? '')) ?>" class="w-full aspect-[3/4] object-cover" loading="lazy" />
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
