<?php
// 模型选择对话框
$models = $creationType === 'image' ? $imageModels : $videoModels;
$defaultAspectRatio = $defaultAspectRatio ?? (($creationType ?? 'image') === 'video' ? '16:9' : '3:4');
$currentModel = $currentModel ?? ($models[0] ?? ['id' => '']);
?>
<!-- Model Select Dialog -->
<div id="model-dialog" class="hidden fixed inset-0 z-50 dialog-overlay" onclick="closeModelDialog()" style="display: none;">
    <div class="dialog-content max-w-[680px] p-0 gap-0 overflow-hidden" onclick="event.stopPropagation()">
        <div class="px-6 py-4 border-b border-[#E5E5E5]">
            <h2 class="text-base font-medium text-[#1A1A1A]">选择模型</h2>
        </div>
        
        <div class="p-4 max-h-[500px] overflow-y-auto">
            <div class="grid grid-cols-2 gap-3">
                <?php foreach ($models as $model): ?>
                    <button
                        onclick="selectModel('<?= htmlspecialchars($model['id']) ?>', '<?= htmlspecialchars($model['name']) ?>')"
                        class="text-left p-4 rounded-xl border border-[#E5E5E5] bg-[#F9FAFB] transition-all duration-200 hover:border-[#3B82F6] hover:bg-[#F0F7FF] model-option"
                        data-model-id="<?= htmlspecialchars($model['id']) ?>"
                    >
                        <div class="flex items-start gap-3">
                            <!-- Model Icon -->
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 bg-gradient-to-br <?= $model['icon'] === 'banana' ? 'from-yellow-400 to-yellow-600' : ($model['icon'] === 'doubao' ? 'from-emerald-500 to-teal-600' : ($model['icon'] === 'sora' ? 'from-purple-500 to-pink-500' : 'from-blue-400 to-blue-600')) ?>">
                                <?php
                                $iconHtml = '';
                                switch($model['icon']) {
                                    case 'seedream':
                                        $iconHtml = '<div class="w-5 h-5 bg-white/90 rounded flex items-center justify-center"><div class="w-3 h-3 bg-gradient-to-r from-blue-400 to-blue-600 rounded-sm"></div></div>';
                                        break;
                                    case 'universal':
                                        $iconHtml = '<div class="w-5 h-5 bg-amber-400 rounded-full"></div>';
                                        break;
                                    case 'qwen':
                                        $iconHtml = '<div class="w-5 h-5 bg-purple-500 rounded-lg"></div>';
                                        break;
                                    case 'ai':
                                        $iconHtml = '<div class="w-5 h-5 bg-pink-500 rounded-full"></div>';
                                        break;
                                    case 'base':
                                        $iconHtml = '<div class="w-5 h-5 bg-orange-500 rounded-lg"></div>';
                                        break;
                                    case 'zimage':
                                        $iconHtml = '<div class="w-5 h-5 bg-indigo-500 rounded-lg"></div>';
                                        break;
                                    case 'pixverse':
                                        $iconHtml = '<div class="w-5 h-5 bg-cyan-400 rounded-full"></div>';
                                        break;
                                    case 'kling':
                                        $iconHtml = '<div class="w-5 h-5 bg-blue-500 rounded-full"></div>';
                                        break;
                                    case 'tongyi':
                                        $iconHtml = '<div class="w-5 h-5 bg-purple-600 rounded-lg"></div>';
                                        break;
                                    case 'vidu':
                                        $iconHtml = '<div class="w-5 h-5 bg-orange-400 rounded-lg"></div>';
                                        break;
                                    case 'hailuo':
                                        $iconHtml = '<div class="w-5 h-5 bg-red-500 rounded-full"></div>';
                                        break;
                                    case 'banana':
                                        $iconHtml = '<div class="w-5 h-5 bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-lg"></div>';
                                        break;
                                    case 'doubao':
                                        $iconHtml = '<div class="w-5 h-5 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-lg"></div>';
                                        break;
                                    case 'sora':
                                        $iconHtml = '<div class="w-5 h-5 bg-gradient-to-br from-purple-500 to-pink-500 rounded-lg"></div>';
                                        break;
                                    default:
                                        $iconHtml = '<div class="w-5 h-5 bg-blue-500 rounded-lg"></div>';
                                }
                                echo $iconHtml;
                                ?>
                            </div>
                            
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-medium text-[#1A1A1A] text-sm"><?= htmlspecialchars($model['name']) ?></span>
                                    <?php if (!empty($model['isNew'])): ?>
                                        <span class="h-4 px-1.5 text-[10px] bg-amber-100 text-amber-600 rounded border-0">NEW</span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-xs text-[#666666] line-clamp-2 mb-2"><?= htmlspecialchars($model['description']) ?></p>
                                
                                <!-- Tags -->
                                <div class="flex flex-wrap gap-1.5">
                                    <?php foreach ($model['tags'] as $tag): ?>
                                        <span class="px-2 py-0.5 text-[10px] bg-white border border-[#E5E5E5] rounded text-[#666666]">
                                            <?= htmlspecialchars($tag) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Params Dialog -->
<div id="params-dialog" class="hidden fixed inset-0 z-50 dialog-overlay" onclick="closeParamsDialog()" style="display: none;">
    <div class="dialog-content max-w-[400px] p-0 gap-0" onclick="event.stopPropagation()">
        <div class="px-5 py-4 border-b border-[#E5E5E5]">
            <h2 class="text-base font-medium text-[#1A1A1A]">图片设置</h2>
        </div>
        
        <div class="p-5 space-y-6">
            <!-- Quality Selection -->
            <div>
                <label class="text-sm text-[#666666] mb-2 block">图像质量</label>
                <div class="flex gap-2">
                    <button
                        id="quality-2k"
                        onclick="setQuality('2k')"
                        class="flex-1 py-2 text-sm rounded-lg border transition-all duration-200 quality-btn border-[#3B82F6] bg-[#F0F7FF] text-[#3B82F6]"
                        data-quality="2k"
                    >
                        高清 2K
                    </button>
                    <button
                        id="quality-4k"
                        onclick="setQuality('4k')"
                        class="flex-1 py-2 text-sm rounded-lg border transition-all duration-200 quality-btn border-[#E5E5E5] text-[#666666] hover:border-[#3B82F6]"
                        data-quality="4k"
                    >
                        超清 4K
                    </button>
                </div>
            </div>

            <!-- Aspect Ratio -->
            <div>
                <label class="text-sm text-[#666666] mb-3 block">图片尺寸</label>
                <div class="grid grid-cols-5 gap-2">
                    <?php
                    $aspectRatios = [
                        ['value' => '1:1', 'label' => '1:1', 'w' => 1024, 'h' => 1024],
                        ['value' => '2:3', 'label' => '2:3', 'w' => 768, 'h' => 1152],
                        ['value' => '3:2', 'label' => '3:2', 'w' => 1152, 'h' => 768],
                        ['value' => '3:4', 'label' => '3:4', 'w' => 768, 'h' => 1024],
                        ['value' => '4:3', 'label' => '4:3', 'w' => 1024, 'h' => 768],
                        ['value' => '9:16', 'label' => '9:16', 'w' => 576, 'h' => 1024],
                        ['value' => '16:9', 'label' => '16:9', 'w' => 1024, 'h' => 576],
                        ['value' => '9:21', 'label' => '9:21', 'w' => 448, 'h' => 1024],
                        ['value' => '21:9', 'label' => '21:9', 'w' => 1024, 'h' => 448],
                    ];
                    foreach ($aspectRatios as $ratio):
                        $isSelected = $ratio['value'] === $defaultAspectRatio;
                    ?>
                        <button
                            onclick="setAspectRatio('<?= $ratio['value'] ?>', <?= $ratio['w'] ?>, <?= $ratio['h'] ?>)"
                            class="flex flex-col items-center gap-1.5 p-2 rounded-lg border transition-all duration-200 <?= $isSelected ? 'border-[#3B82F6] bg-[#F0F7FF]' : 'border-[#E5E5E5] hover:border-[#3B82F6]' ?> aspect-ratio-btn"
                            data-ratio="<?= $ratio['value'] ?>"
                        >
                            <div 
                                class="border-2 rounded-sm <?= $isSelected ? 'border-[#3B82F6]' : 'border-[#999999]' ?>"
                                style="width: <?= $ratio['value'] === '1:1' ? 16 : ($ratio['w'] > $ratio['h'] ? 18 : 12) ?>px; height: <?= $ratio['value'] === '1:1' ? 16 : ($ratio['w'] > $ratio['h'] ? 12 : 18) ?>px;"
                            ></div>
                            <span class="text-[10px] <?= $isSelected ? 'text-[#3B82F6]' : 'text-[#666666]' ?>"><?= $ratio['label'] ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
                
                <!-- Custom Size Input -->
                <div class="flex items-center gap-2 mt-3">
                    <div class="flex items-center gap-2 flex-1">
                        <span class="text-xs text-[#999999]">W</span>
                        <input 
                            type="text" 
                            id="width-input"
                            value="<?= $aspectRatios[3]['w'] ?>"
                            readonly
                            class="flex-1 h-8 px-3 text-sm bg-[#F5F5F5] rounded border border-[#E5E5E5] text-[#666666]"
                        />
                    </div>
                    <span class="text-[#999999]">×</span>
                    <div class="flex items-center gap-2 flex-1">
                        <span class="text-xs text-[#999999]">H</span>
                        <input 
                            type="text" 
                            id="height-input"
                            value="<?= $aspectRatios[3]['h'] ?>"
                            readonly
                            class="flex-1 h-8 px-3 text-sm bg-[#F5F5F5] rounded border border-[#E5E5E5] text-[#666666]"
                        />
                    </div>
                </div>
            </div>

            <!-- Image Count -->
            <div>
                <label class="text-sm text-[#666666] mb-2 block">图片张数</label>
                <div class="grid grid-cols-4 gap-2">
                    <?php for ($i = 1; $i <= 4; $i++): ?>
                        <button
                            onclick="setCount(<?= $i ?>)"
                            id="count-<?= $i ?>"
                            class="py-2 text-sm rounded-lg border transition-all duration-200 count-btn <?= $i === 1 ? 'border-[#3B82F6] bg-[#F0F7FF] text-[#3B82F6]' : 'border-[#E5E5E5] text-[#666666] hover:border-[#3B82F6]' ?>"
                            data-count="<?= $i ?>"
                        >
                            <?= $i ?>张
                        </button>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// 存储当前设置
window.currentSettings = {
    mode: 'single',
    quality: '2k',
    aspectRatio: '<?= $defaultAspectRatio ?>',
    selectedModel: '<?= $currentModel['id'] ?>',
    count: 1
};
</script>

<!-- Auth Dialog：手机号验证码登录（登录注册一体） -->
<div id="auth-dialog" class="hidden fixed inset-0 z-50 dialog-overlay" onclick="closeAuthDialog()" style="display: none;">
    <div class="dialog-content max-w-[420px] w-[92vw] p-0 gap-0 overflow-hidden rounded-2xl shadow-xl" onclick="event.stopPropagation()">
        <!-- 顶部促销区 -->
        <div class="relative px-5 pt-5 pb-4 bg-gradient-to-br from-[#E0F2FE] via-[#BAE6FD] to-[#7DD3FC] overflow-hidden">
            <div class="absolute inset-0 opacity-30" style="background-image: radial-gradient(circle, #fff 1px, transparent 1px); background-size: 12px 12px;"></div>
            <div class="absolute top-2 left-3 text-[10px] font-bold text-[#0EA5E9]/40 tracking-widest">FREE</div>
            <div class="relative flex flex-wrap items-center gap-2 mb-3">
                <span class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-[#0369A1] bg-white/90 rounded-full shadow-sm">免费生视频 · 免费生图</span>
                <span class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-[#0284C7] rounded-full shadow-sm">登录即送 AI 创作大礼包</span>
                <span class="inline-flex items-center px-2.5 py-1 text-[10px] text-[#0369A1] border border-dashed border-[#0EA5E9]/60 rounded-full">对新用户</span>
            </div>
            <h3 class="relative text-[22px] font-bold text-[#0C4A6E] mb-0.5">手机验证码登录</h3>
            <p id="auth-subtitle" class="relative text-xs text-[#0369A1]/90">请输入手机号与短信验证码，未注册手机号将自动创建账号</p>
        </div>

        <!-- 保留结构兼容旧脚本，实际统一为验证码登录 -->
        <div class="flex mx-5 mt-4 mb-2 bg-[#F1F5F9] rounded-xl p-1">
            <button type="button" id="auth-tab-login" onclick="switchAuthTab('login')" class="flex-1 h-9 text-sm font-medium rounded-lg transition-colors bg-white text-[#2563EB] shadow-sm">验证码登录</button>
            <button type="button" id="auth-tab-register" onclick="switchAuthTab('register')" class="hidden flex-1 h-9 text-sm font-medium rounded-lg transition-colors text-[#64748B]">自动注册</button>
        </div>

        <form id="auth-form" class="p-5 space-y-4 bg-white" onsubmit="submitAuthForm(event)">
            <input type="hidden" id="auth-mode" value="login">

            <div>
                <label class="text-sm text-[#64748B] mb-2 block font-medium">手机号</label>
                <div class="flex items-center h-12 px-4 rounded-xl bg-[#F8FAFC] border border-[#E2E8F0] focus-within:border-[#3B82F6] focus-within:ring-2 focus-within:ring-[#3B82F6]/20 transition-all">
                    <span class="text-[#334155] text-sm font-medium">+86</span>
                    <div class="w-px h-5 mx-3 bg-[#CBD5E1]"></div>
                    <input id="auth-phone" type="text" maxlength="11" autocomplete="tel" placeholder="请输入手机号"
                        class="flex-1 h-full text-sm bg-transparent outline-none border-0 placeholder:text-[#94A3B8]" />
                </div>
            </div>

            <div>
                <label class="text-sm text-[#64748B] mb-2 block font-medium">短信验证码</label>
                <div class="flex items-center gap-2">
                    <input id="auth-code" type="text" maxlength="6" inputmode="numeric" autocomplete="one-time-code" placeholder="请输入6位验证码"
                        class="flex-1 h-12 px-4 text-sm rounded-xl bg-[#F8FAFC] border border-[#E2E8F0] focus:border-[#3B82F6] focus:ring-2 focus:ring-[#3B82F6]/20 outline-none transition-all" />
                    <button id="send-code-btn" type="button" onclick="sendLoginCode()"
                        class="h-12 px-4 text-sm font-medium text-[#1D4ED8] bg-[#EFF6FF] border border-[#BFDBFE] rounded-xl hover:bg-[#DBEAFE] transition-colors whitespace-nowrap">
                        获取验证码
                    </button>
                </div>
            </div>

            <div id="auth-error" class="hidden text-xs text-red-600 bg-red-50 border border-red-100 rounded-xl px-3 py-2.5"></div>

            <button id="auth-submit-btn" type="submit"
                class="w-full h-12 text-base font-semibold bg-[#2563EB] hover:bg-[#1D4ED8] active:scale-[0.98] text-white rounded-xl transition-all shadow-lg shadow-blue-500/25">
                登录 / 注册
            </button>

            <p class="text-[11px] text-center text-[#94A3B8] leading-relaxed">
                登录即代表同意 <a href="#" class="text-[#3B82F6] hover:underline">《用户协议》</a> 和 <a href="#" class="text-[#3B82F6] hover:underline">《隐私政策》</a>
            </p>
        </form>
    </div>
</div>

<!-- Points Recharge Dialog -->
<div id="points-dialog" class="hidden fixed inset-0 z-50 dialog-overlay" onclick="closePointsDialog()" style="display:none;">
    <div class="dialog-content max-w-[460px] w-[92vw] p-0" onclick="event.stopPropagation()">
        <div class="px-5 pt-5 pb-3 border-b border-[#E5E5E5]">
            <h3 class="text-base font-medium text-[#1A1A1A]">购买积分</h3>
            <p class="text-xs text-[#999] mt-1">4K 图像消耗比 2K 高 80%</p>
        </div>
        <div class="p-5 space-y-3">
            <button type="button" onclick="rechargePoints('pkg_test_0_1')" class="w-full h-11 rounded-lg border border-[#F59E0B] bg-[#FFF7ED] hover:border-[#D97706] text-left px-4 flex items-center justify-between">
                <span class="text-sm text-[#92400E]">0.1 元（测试）</span>
                <span class="text-sm text-[#D97706]">3 积分</span>
            </button>
            <button type="button" onclick="rechargePoints('pkg_9_9')" class="w-full h-11 rounded-lg border border-[#E5E5E5] hover:border-[#3B82F6] text-left px-4 flex items-center justify-between">
                <span class="text-sm text-[#1A1A1A]">9.9 元</span>
                <span class="text-sm text-[#3B82F6]">165 积分</span>
            </button>
            <button type="button" onclick="rechargePoints('pkg_19_9')" class="w-full h-11 rounded-lg border border-[#E5E5E5] hover:border-[#3B82F6] text-left px-4 flex items-center justify-between">
                <span class="text-sm text-[#1A1A1A]">19.9 元</span>
                <span class="text-sm text-[#3B82F6]">335 积分</span>
            </button>
            <button type="button" onclick="rechargePoints('pkg_29_9')" class="w-full h-11 rounded-lg border border-[#E5E5E5] hover:border-[#3B82F6] text-left px-4 flex items-center justify-between">
                <span class="text-sm text-[#1A1A1A]">29.9 元</span>
                <span class="text-sm text-[#3B82F6]">505 积分</span>
            </button>
            <p class="text-xs text-[#999]">点击套餐后将展示站内支付二维码，支付成功自动到账</p>
        </div>
    </div>
</div>

<!-- Membership Dialog -->
<div id="membership-dialog" class="hidden fixed inset-0 z-50 dialog-overlay" onclick="closeMembershipDialog()" style="display:none;">
    <div class="dialog-content max-w-[980px] w-[94vw] p-0" onclick="event.stopPropagation()">
        <div class="px-5 pt-5 pb-3 border-b border-[#E5E5E5]">
            <h3 class="text-base font-medium text-[#1A1A1A]">会员方案</h3>
            <p class="text-xs text-[#999] mt-1">每日签到领积分（当天有效，次日12:00清零）；会员另享开通赠送积分</p>
        </div>
        <div class="p-5">
            <div id="membership-current-tip" class="mb-4 text-sm rounded-xl border border-[#E5E5E5] bg-[#F8FAFC] text-[#475569] px-4 py-2.5">
                当前状态：普通用户；可通过每日签到领取积分，也可按需选择会员套餐。
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <div id="membership-card-member_first_month" class="rounded-2xl border border-[#E5E7EB] bg-white p-4">
                    <div class="text-base font-semibold text-[#1A1A1A]">首月会员</div>
                    <div class="mt-2">
                        <span class="text-3xl font-bold text-[#111]">¥29.9</span>
                        <span class="text-sm text-[#666]"> / 30天</span>
                    </div>
                    <div class="text-xs text-[#666] mt-1">开通即送：500 积分</div>
                    <div class="text-xs text-[#666] mt-1">每日签到：16 积分（当天有效）</div>
                    <button id="membership-btn-member_first_month" type="button" onclick="subscribeMembership('member_first_month')" class="mt-3 h-10 w-full rounded-xl bg-[#111827] hover:bg-black text-white text-sm font-medium">立即开通</button>
                    <ul class="mt-3 text-xs text-[#666] space-y-1">
                        <li>• 每日签到领16积分</li>
                        <li>• 专属会员加速通道</li>
                    </ul>
                </div>
                <div id="membership-card-member_renew_month" class="rounded-2xl border border-[#E5E7EB] bg-white p-4">
                    <div class="text-base font-semibold text-[#1A1A1A]">连续续费月会员</div>
                    <div class="mt-2">
                        <span class="text-3xl font-bold text-[#111]">¥39.9</span>
                        <span class="text-sm text-[#666]"> / 30天</span>
                    </div>
                    <div class="text-xs text-[#666] mt-1">开通即送：670 积分</div>
                    <div class="text-xs text-[#666] mt-1">每日签到：16 积分（当天有效）</div>
                    <button id="membership-btn-member_renew_month" type="button" onclick="subscribeMembership('member_renew_month')" class="mt-3 h-10 w-full rounded-xl bg-[#111827] hover:bg-black text-white text-sm font-medium">立即开通</button>
                    <ul class="mt-3 text-xs text-[#666] space-y-1">
                        <li>• 每日签到领16积分</li>
                        <li>• 适合连续创作用户</li>
                    </ul>
                </div>
                <div id="membership-card-member_year" class="rounded-2xl border border-[#FCD34D] bg-gradient-to-b from-[#FFFBEB] to-white p-4">
                    <div class="text-xs inline-flex px-2 py-0.5 rounded-full bg-[#FDE68A] text-[#92400E] mb-2">推荐</div>
                    <div class="text-base font-semibold text-[#1A1A1A]">年会员</div>
                    <div class="mt-2">
                        <span class="text-3xl font-bold text-[#111]">¥299</span>
                        <span class="text-sm text-[#666]"> / 365天</span>
                    </div>
                    <div class="text-xs text-[#666] mt-1">开通即送：5000 积分（约417分/月）</div>
                    <div class="text-xs text-[#666] mt-1">每日签到：16 积分（当天有效）</div>
                    <button id="membership-btn-member_year" type="button" onclick="subscribeMembership('member_year')" class="mt-3 h-10 w-full rounded-xl bg-[#F59E0B] hover:bg-[#D97706] text-white text-sm font-medium">立即开通</button>
                    <ul class="mt-3 text-xs text-[#666] space-y-1">
                        <li>• 长周期更省心</li>
                        <li>• 全年持续会员权益</li>
                    </ul>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3">
                <button id="membership-btn-member_single_month" type="button" onclick="subscribeMembership('member_single_month')" class="p-3 rounded-xl border border-[#E5E5E5] hover:border-[#3B82F6] text-left">
                    <div class="text-sm font-medium text-[#1A1A1A]">单月会员</div>
                    <div class="text-xs text-[#666] mt-1">49.9 元 / 30 天</div>
                    <div class="text-xs text-[#999] mt-1">开通即送：835 积分（每日签到奖励另算）</div>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Inline Payment Dialog -->
<div id="inline-pay-dialog" class="hidden fixed inset-0 z-[60] dialog-overlay" onclick="closeInlinePayDialog()" style="display:none;">
    <div class="dialog-content max-w-[420px] w-[92vw] p-0" onclick="event.stopPropagation()">
        <div class="px-5 pt-5 pb-3 border-b border-[#E5E5E5] flex items-center justify-between">
            <h3 class="text-base font-medium text-[#1A1A1A]">请完成支付</h3>
            <button type="button" onclick="closeInlinePayDialog()" class="text-[#999] hover:text-[#333]">✕</button>
        </div>
        <div class="p-5">
            <p id="inline-pay-desc" class="text-sm text-[#666] mb-3">请使用支付宝扫码支付</p>
            <div class="w-full flex items-center justify-center mb-4">
                <img id="inline-pay-img" src="" alt="支付二维码" class="w-52 h-52 rounded-lg border border-[#E5E5E5] object-cover hidden" />
            </div>
            <div class="space-y-2">
                <a id="inline-pay-open-link" href="#" target="_blank" rel="noopener" class="w-full h-11 rounded-lg bg-[#2563EB] hover:bg-[#1D4ED8] text-white text-sm flex items-center justify-center">打开支付页面</a>
                <button type="button" onclick="checkInlinePayStatus(true)" class="w-full h-11 rounded-lg border border-[#E5E5E5] hover:border-[#3B82F6] text-sm text-[#1A1A1A]">
                    我已支付，刷新状态
                </button>
                <p id="inline-pay-status" class="text-xs text-[#999] text-center mt-2">等待支付中...</p>
            </div>
        </div>
    </div>
</div>
