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
// 账号认证：登录/注册/退出
// ============================
function openAuthDialog(mode = 'login') {
    const dialog = document.getElementById('auth-dialog');
    if (!dialog) return;
    dialog.classList.remove('hidden');
    dialog.style.display = 'flex';
    switchAuthTab(mode);
}

function closeAuthDialog() {
    const dialog = document.getElementById('auth-dialog');
    if (!dialog) return;
    dialog.classList.add('hidden');
    dialog.style.display = 'none';
    setAuthError('');
}

function switchAuthTab(mode) {
    const normalized = 'login';
    const modeInput = document.getElementById('auth-mode');
    const loginTab = document.getElementById('auth-tab-login');
    const registerTab = document.getElementById('auth-tab-register');
    const subtitle = document.getElementById('auth-subtitle');
    const submitBtn = document.getElementById('auth-submit-btn');
    if (modeInput) modeInput.value = normalized;

    const selectedClass = 'bg-white text-[#2563EB] shadow-sm';
    const normalClass = 'text-[#64748B]';
    if (loginTab) {
        loginTab.className = 'flex-1 h-9 text-sm font-medium rounded-lg transition-colors ' + (normalized === 'login' ? selectedClass : normalClass);
    }
    if (registerTab) {
        registerTab.className = 'flex-1 h-9 text-sm font-medium rounded-lg transition-colors ' + normalClass;
    }

    if (subtitle) subtitle.textContent = '请输入手机号与短信验证码，未注册手机号将自动创建账号';
    if (submitBtn) submitBtn.textContent = '登录 / 注册';
    setAuthError('');
}

function setAuthError(msg) {
    const errEl = document.getElementById('auth-error');
    if (!errEl) return;
    if (msg) {
        errEl.textContent = msg;
        errEl.classList.remove('hidden');
    } else {
        errEl.classList.add('hidden');
        errEl.textContent = '';
    }
}

function normalizePhone(phone) {
    const digits = String(phone || '').replace(/\D+/g, '');
    if (/^86(1\d{10})$/.test(digits)) return digits.slice(2);
    return digits;
}

async function submitAuthForm(event) {
    event.preventDefault();
    const phone = normalizePhone((document.getElementById('auth-phone')?.value || '').trim());
    const code = (document.getElementById('auth-code')?.value || '').trim();
    const submitBtn = document.getElementById('auth-submit-btn');

    if (!/^1\d{10}$/.test(phone)) {
        setAuthError('请输入正确的11位手机号');
        return;
    }
    if (!/^\d{6}$/.test(code)) {
        setAuthError('请输入6位短信验证码');
        return;
    }

    setAuthError('');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-70', 'cursor-not-allowed');
        submitBtn.textContent = '登录中...';
    }

    try {
        const res = await fetch('api/auth/login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ phone, code }),
        });
        const data = await res.json();
        if (!data.success) {
            setAuthError(data.message || '操作失败，请稍后重试');
            return;
        }
        window.location.reload();
    } catch (err) {
        setAuthError('网络异常，请稍后重试');
    } finally {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-70', 'cursor-not-allowed');
            submitBtn.textContent = '登录 / 注册';
        }
    }
}

function setSendCodeBtnText(text, disabled = false) {
    const btn = document.getElementById('send-code-btn');
    if (!btn) return;
    btn.textContent = text;
    btn.disabled = disabled;
    if (disabled) btn.classList.add('opacity-70', 'cursor-not-allowed');
    else btn.classList.remove('opacity-70', 'cursor-not-allowed');
}

function startSendCodeCountdown(seconds) {
    let left = Math.max(1, Number(seconds || 60));
    setSendCodeBtnText(`${left}s后重发`, true);
    const timer = setInterval(() => {
        left -= 1;
        if (left <= 0) {
            clearInterval(timer);
            setSendCodeBtnText('获取验证码', false);
            return;
        }
        setSendCodeBtnText(`${left}s后重发`, true);
    }, 1000);
}

async function sendLoginCode() {
    const phone = normalizePhone((document.getElementById('auth-phone')?.value || '').trim());
    if (!/^1\d{10}$/.test(phone)) {
        setAuthError('请输入正确的11位手机号');
        return;
    }
    setAuthError('');
    setSendCodeBtnText('发送中...', true);
    try {
        const res = await fetch('api/auth/send_code.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ phone }),
        });
        const data = await res.json();
        if (!data.success) {
            setAuthError(data.message || '验证码发送失败');
            setSendCodeBtnText('获取验证码', false);
            return;
        }
        if (data.data?.debugCode) {
            setAuthError(`调试验证码：${data.data.debugCode}`);
        }
        startSendCodeCountdown(data.data?.resendIn || 60);
    } catch (err) {
        setAuthError('网络异常，验证码发送失败');
        setSendCodeBtnText('获取验证码', false);
    }
}

async function logout() {
    try {
        const res = await fetch('api/auth/logout.php', { method: 'POST' });
        const data = await res.json();
        if (!data.success) {
            updateStatusBar(data.message || '退出失败');
            return;
        }
        window.location.reload();
    } catch (err) {
        updateStatusBar('网络异常，退出失败');
    }
}

function openPointsDialog() {
    if (!window.currentUser || !window.currentUser.id) {
        openAuthDialog('login');
        return;
    }
    const dialog = document.getElementById('points-dialog');
    if (!dialog) return;
    dialog.classList.remove('hidden');
    dialog.style.display = 'flex';
}

function closePointsDialog() {
    const dialog = document.getElementById('points-dialog');
    if (!dialog) return;
    dialog.classList.add('hidden');
    dialog.style.display = 'none';
}

function openMembershipDialog() {
    if (!window.currentUser || !window.currentUser.id) {
        openAuthDialog('login');
        return;
    }
    const dialog = document.getElementById('membership-dialog');
    if (!dialog) return;
    syncMembershipDialogState(window.pointsSummary || null);
    dialog.classList.remove('hidden');
    dialog.style.display = 'flex';
}

function closeMembershipDialog() {
    const dialog = document.getElementById('membership-dialog');
    if (!dialog) return;
    dialog.classList.add('hidden');
    dialog.style.display = 'none';
}

let inlinePayOrderNo = '';
let inlinePayTimer = null;
let inlinePayDoneShown = false;
let inlinePayActionPending = false;
let inlinePayTitle = '';

function formatPaymentErrorHint(msg, statusCode) {
    const text = String(msg || '');
    if (text.includes('支付配置缺失') || text.includes('创建支付订单失败') || Number(statusCode) >= 500) {
        return '支付暂不可用，请联系管理员检查支付配置（EPAY_API_BASE / EPAY_PID / EPAY_KEY）。';
    }
    return text || '支付请求失败，请稍后重试';
}

async function dailyCheckin() {
    const btn = document.getElementById('user-center-checkin-btn');
    const tip = document.getElementById('user-center-checkin-tip');
    if (btn && btn.disabled) return;
    if (btn) {
        btn.disabled = true;
        btn.textContent = '签到中...';
        btn.classList.add('opacity-70', 'cursor-not-allowed');
    }
    try {
        const res = await fetch('api/points/checkin.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({}),
        });
        const data = await res.json();
        if (!data.success) {
            showInlineNotice(data.message || '签到失败', 'error');
            if (btn) {
                btn.disabled = false;
                btn.textContent = '每日签到';
                btn.classList.remove('opacity-70', 'cursor-not-allowed');
            }
            return;
        }
        showInlineNotice(data.message || '签到成功', 'success');
        await refreshPointsSummary();
        if (btn) {
            btn.disabled = true;
            btn.textContent = '今日已签到';
            btn.classList.remove('bg-emerald-600', 'hover:bg-emerald-700', 'text-white');
            btn.classList.add('bg-[#F5F5F5]', 'text-[#999999]', 'cursor-not-allowed');
        }
        if (tip) {
            const reward = data.data?.wallet?.checkin?.rewardPoints ?? 16;
            tip.textContent = `今日已签到，已领取 ${reward} 积分（当天有效，次日 12:00 清零）`;
        }
    } catch (err) {
        showInlineNotice('网络异常，签到失败', 'error');
        if (btn) {
            btn.disabled = false;
            btn.textContent = '每日签到';
            btn.classList.remove('opacity-70', 'cursor-not-allowed');
        }
    }
}

function showInlineNotice(message, type = 'info') {
    const text = String(message || '').trim();
    if (!text) return;
    let box = document.getElementById('inline-global-notice');
    if (!box) {
        box = document.createElement('div');
        box.id = 'inline-global-notice';
        box.className = 'fixed top-4 left-1/2 -translate-x-1/2 z-[90] px-4 py-2 rounded-lg text-sm shadow-lg';
        document.body.appendChild(box);
    }
    if (type === 'success') {
        box.className = 'fixed top-4 left-1/2 -translate-x-1/2 z-[90] px-4 py-2 rounded-lg text-sm shadow-lg bg-emerald-50 text-emerald-700 border border-emerald-200';
    } else if (type === 'error') {
        box.className = 'fixed top-4 left-1/2 -translate-x-1/2 z-[90] px-4 py-2 rounded-lg text-sm shadow-lg bg-red-50 text-red-700 border border-red-200';
    } else {
        box.className = 'fixed top-4 left-1/2 -translate-x-1/2 z-[90] px-4 py-2 rounded-lg text-sm shadow-lg bg-slate-50 text-slate-700 border border-slate-200';
    }
    box.textContent = text;
    clearTimeout(window.__inlineNoticeTimer);
    window.__inlineNoticeTimer = setTimeout(() => {
        if (box) box.remove();
    }, 2600);
}

if (!window.__nativeAlertPatched) {
    window.__nativeAlertPatched = true;
    if (typeof window.alert === 'function') {
        window.__nativeAlertOriginal = window.alert.bind(window);
    }
    window.alert = function (message) {
        showInlineNotice(String(message || ''), 'info');
    };
}

function syncCheckinButtons(wallet) {
    const checkedToday = !!(wallet?.checkin && wallet.checkin.checkedToday);
    const reward = wallet?.checkin?.rewardPoints ?? 16;
    const checkinBtn = document.getElementById('user-center-checkin-btn');
    const checkinTip = document.getElementById('user-center-checkin-tip');
    const headerCheckinBtn = document.getElementById('header-checkin-btn');

    if (checkinBtn) {
        if (checkedToday) {
            checkinBtn.disabled = true;
            checkinBtn.textContent = '今日已签到';
            checkinBtn.classList.remove('bg-emerald-600', 'hover:bg-emerald-700', 'text-white');
            checkinBtn.classList.add('bg-[#F5F5F5]', 'text-[#999999]', 'cursor-not-allowed');
        } else {
            checkinBtn.disabled = false;
            checkinBtn.textContent = '每日签到';
            checkinBtn.classList.remove('bg-[#F5F5F5]', 'text-[#999999]', 'cursor-not-allowed');
            checkinBtn.classList.add('bg-emerald-600', 'hover:bg-emerald-700', 'text-white');
        }
    }

    if (checkinTip) {
        checkinTip.textContent = checkedToday
            ? '今日已签到，赠送积分当天有效，次日 12:00 清零'
            : `今日未签到，签到可领 ${reward} 积分（当天有效）`;
    }

    if (headerCheckinBtn) {
        if (checkedToday) {
            headerCheckinBtn.disabled = true;
            headerCheckinBtn.textContent = '今日已签到';
            headerCheckinBtn.className = 'h-9 px-3 text-sm rounded-lg transition-colors bg-[#F5F5F5] text-[#999999] cursor-not-allowed';
        } else {
            headerCheckinBtn.disabled = false;
            headerCheckinBtn.textContent = `每日签到 +${reward}`;
            headerCheckinBtn.className = 'h-9 px-3 text-sm rounded-lg transition-colors text-[#666666] hover:text-[#1A1A1A] hover:bg-[#F5F5F5]';
        }
    }
}

function syncMembershipDialogState(wallet) {
    const membership = wallet?.membership || null;
    const activePlan = membership && membership.status === 'active' ? String(membership.planCode || '') : '';
    const planLabelMap = {
        member_first_month: '首月会员',
        member_renew_month: '连续续费月会员',
        member_single_month: '单月会员',
        member_year: '年会员',
    };
    const tipEl = document.getElementById('membership-current-tip');
    if (tipEl) {
        if (activePlan) {
            tipEl.className = 'mb-4 text-sm rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-700 px-4 py-2.5';
            const planLabel = planLabelMap[activePlan] || activePlan;
            tipEl.textContent = `当前会员：${planLabel}，到期时间：${membership.expiresAt || '-'}；每日签到可领 ${membership.dailyBonusPoints || 16} 积分`;
        } else {
            tipEl.className = 'mb-4 text-sm rounded-xl border border-[#E5E5E5] bg-[#F8FAFC] text-[#475569] px-4 py-2.5';
            tipEl.textContent = '当前状态：普通用户；可通过每日签到领取积分，也可按需选择会员套餐。';
        }
    }

    const planIds = ['member_first_month', 'member_renew_month', 'member_single_month', 'member_year'];
    planIds.forEach(function (planId) {
        const card = document.getElementById('membership-card-' + planId);
        const btn = document.getElementById('membership-btn-' + planId);
        const isActive = activePlan === planId;
        if (card) {
            if (isActive) {
                card.classList.add('ring-2', 'ring-emerald-400', 'border-emerald-300');
            } else {
                card.classList.remove('ring-2', 'ring-emerald-400', 'border-emerald-300');
            }
        }
        if (btn) {
            if (isActive) {
                btn.disabled = true;
                btn.textContent = '当前生效中';
                btn.classList.add('opacity-70', 'cursor-not-allowed');
            } else {
                btn.disabled = false;
                btn.textContent = '立即开通';
                btn.classList.remove('opacity-70', 'cursor-not-allowed');
            }
        }
    });
}

function closeInlinePayDialog() {
    const dialog = document.getElementById('inline-pay-dialog');
    if (!dialog) return;
    dialog.classList.add('hidden');
    dialog.style.display = 'none';
    if (inlinePayTimer) {
        clearInterval(inlinePayTimer);
        inlinePayTimer = null;
    }
    inlinePayOrderNo = '';
    inlinePayTitle = '';
    inlinePayDoneShown = false;
}

function openInlinePayDialog(payload) {
    const dialog = document.getElementById('inline-pay-dialog');
    if (!dialog) return;

    inlinePayOrderNo = payload?.outTradeNo || '';
    inlinePayTitle = String(payload?.title || '');
    const imgUrl = payload?.payInfo?.img || '';
    const qrcodeUrl = payload?.payInfo?.qrcode || '';
    const payUrl = payload?.payInfo?.payUrl || qrcodeUrl || '';
    const amount = payload?.amount || '';
    const title = payload?.title || '订单支付';

    const descEl = document.getElementById('inline-pay-desc');
    const imgEl = document.getElementById('inline-pay-img');
    const linkEl = document.getElementById('inline-pay-open-link');
    const statusEl = document.getElementById('inline-pay-status');

    if (descEl) {
        descEl.textContent = `${title}${amount ? `（${amount}元）` : ''}，请扫码支付`;
    }
    if (imgEl) {
        const source = imgUrl || qrcodeUrl || '';
        if (source) {
            imgEl.src = source;
            imgEl.classList.remove('hidden');
        } else {
            imgEl.classList.add('hidden');
            imgEl.src = '';
        }
    }
    if (linkEl) {
        if (payUrl) {
            linkEl.href = payUrl;
            linkEl.classList.remove('hidden');
        } else {
            linkEl.classList.add('hidden');
        }
    }
    if (statusEl) {
        statusEl.textContent = '等待支付中...';
        statusEl.classList.remove('text-emerald-600');
        statusEl.classList.add('text-[#999]');
    }
    inlinePayDoneShown = false;

    dialog.classList.remove('hidden');
    dialog.style.display = 'flex';

    if (inlinePayTimer) clearInterval(inlinePayTimer);
    inlinePayTimer = setInterval(function () {
        checkInlinePayStatus(false);
    }, 3000);
}

async function checkInlinePayStatus(showMsg = true) {
    if (!inlinePayOrderNo) return;
    const statusEl = document.getElementById('inline-pay-status');
    try {
        const res = await fetch('api/payment/status.php?outTradeNo=' + encodeURIComponent(inlinePayOrderNo));
        const data = await res.json();
        if (!data.success) {
            if (showMsg) showInlineNotice(data.message || '查询支付状态失败', 'error');
            return;
        }
        if (data.data?.done) {
            if (statusEl) {
                statusEl.textContent = '支付成功，权益已到账';
                statusEl.classList.remove('text-[#999]');
                statusEl.classList.add('text-emerald-600');
            }
            if (inlinePayTimer) {
                clearInterval(inlinePayTimer);
                inlinePayTimer = null;
            }
            await refreshPointsSummary();
            if (!inlinePayDoneShown) {
                inlinePayDoneShown = true;
                const doneMsg = inlinePayTitle.includes('会员')
                    ? '支付成功，会员状态已自动刷新'
                    : '支付成功，积分余额已自动刷新';
                showInlineNotice(doneMsg, 'success');
            }
            setTimeout(function () {
                closeInlinePayDialog();
            }, 2000);
            return;
        }
        if (statusEl) statusEl.textContent = '等待支付中...';
        if (showMsg) showInlineNotice('尚未支付成功，请完成支付后再刷新', 'info');
    } catch (err) {
        if (showMsg) showInlineNotice('网络异常，查询失败', 'error');
    }
}

async function refreshPointsSummary() {
    if (!window.currentUser || !window.currentUser.id) return;
    try {
        const res = await fetch('api/points/me.php');
        const data = await res.json();
        if (!data.success || !data.data?.wallet) return;
        applyWalletSummary(data.data.wallet);
    } catch (err) {
        // ignore
    }
}

function applyWalletSummary(wallet) {
    if (!wallet) return;
    window.pointsSummary = wallet;
    const el = document.getElementById('header-points-balance');
    if (el) el.textContent = String(wallet.totalBalance || 0);
    const memberStatusEl = document.getElementById('header-membership-status');
    if (memberStatusEl) {
        const isActiveMember = !!(wallet.membership && wallet.membership.status === 'active');
        memberStatusEl.textContent = isActiveMember ? '会员中' : '普通用户';
        memberStatusEl.className = isActiveMember
            ? 'h-7 px-2.5 rounded-full text-xs flex items-center border bg-amber-50 text-amber-700 border-amber-200'
            : 'h-7 px-2.5 rounded-full text-xs flex items-center border bg-[#F5F5F5] text-[#666] border-[#E5E5E5]';
    }
    syncMembershipDialogState(wallet);
    syncCheckinButtons(wallet);
}

async function rechargePoints(packageId) {
    if (inlinePayActionPending) return;
    inlinePayActionPending = true;
    try {
        const res = await fetch('api/points/recharge.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ packageId, payType: 'alipay' }),
        });
        const raw = await res.text();
        let data = null;
        try { data = JSON.parse(raw); } catch (e) { /* ignore */ }
        if (!data) {
            alert('服务端返回异常：' + raw.slice(0, 120));
            return;
        }
        if (!data.success) {
            alert(formatPaymentErrorHint(data.message, res.status));
            return;
        }
        if (!data.data?.payInfo) {
            alert('创建支付信息失败，请稍后重试');
            return;
        }
        openInlinePayDialog({
            title: '积分充值',
            amount: data.data?.package?.price,
            outTradeNo: data.data?.outTradeNo,
            payInfo: data.data?.payInfo,
        });
    } catch (err) {
        alert('网络异常，充值失败');
    } finally {
        inlinePayActionPending = false;
    }
}

async function subscribeMembership(planId) {
    if (inlinePayActionPending) return;
    inlinePayActionPending = true;
    try {
        const activePlan = window.pointsSummary?.membership?.status === 'active'
            ? String(window.pointsSummary?.membership?.planCode || '')
            : '';
        if (activePlan === planId) {
            alert('当前套餐已生效，无需重复开通');
            return;
        }
        const res = await fetch('api/points/subscribe.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ planId, payType: 'alipay' }),
        });
        const raw = await res.text();
        let data = null;
        try { data = JSON.parse(raw); } catch (e) { /* ignore */ }
        if (!data) {
            alert('服务端返回异常：' + raw.slice(0, 120));
            return;
        }
        if (!data.success) {
            alert(formatPaymentErrorHint(data.message, res.status));
            return;
        }
        if (!data.data?.payInfo) {
            alert('创建支付信息失败，请稍后重试');
            return;
        }
        openInlinePayDialog({
            title: '会员开通',
            amount: data.data?.plan?.price,
            outTradeNo: data.data?.outTradeNo,
            payInfo: data.data?.payInfo,
        });
    } catch (err) {
        alert('网络异常，开通失败');
    } finally {
        inlinePayActionPending = false;
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
    if (typeof updateGeneratePointsDisplay === 'function') updateGeneratePointsDisplay();
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
// inputOrFile: <input> 或 File 对象（支持拖拽传入）
// ============================
async function handleFrameUpload(inputOrFile, previewId, frameType) {
    const file = inputOrFile instanceof File ? inputOrFile : (inputOrFile?.files?.[0]);
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
            const safeUrl = sanitizeMediaUrl(data.data.url);
            if (!safeUrl) {
                if (preview) preview.innerHTML = '<span class="text-xs text-red-500">返回地址无效</span>';
                return;
            }
            if (!window.frameUrls) window.frameUrls = {};
            window.frameUrls[frameType] = safeUrl;
            if (preview) preview.innerHTML = `<img src="${safeUrl}" alt="${escapeHtml(frameType)}" class="w-full h-full object-cover rounded-lg" />`;
        } else {
            if (preview) preview.innerHTML = '<span class="text-xs text-red-500">' + escapeHtml(data.message || '上传失败') + '</span>';
        }
    } catch (err) {
        if (preview) preview.innerHTML = '<span class="text-xs text-red-500">上传失败</span>';
    }
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

// ============================
// 上传：多张参考图（OSS）
// inputOrFiles: <input> 或 File[] 数组（支持拖拽传入）
// ============================
async function handleRefImagesUpload(inputOrFiles) {
    const files = Array.isArray(inputOrFiles) ? inputOrFiles : Array.from(inputOrFiles?.files || []);
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
                const safeUrl = sanitizeMediaUrl(data.data.url);
                if (!safeUrl) continue;
                window.referenceImageUrls.push(safeUrl);
                const div = document.createElement('div');
                div.className = 'relative w-[60px] h-[60px] rounded-lg overflow-hidden flex-shrink-0 group';
                div.dataset.url = safeUrl;
                div.innerHTML = `<img src="${safeUrl}" alt="参考" class="w-full h-full object-cover" /><button type="button" onclick="removeRefImage(this)" class="absolute top-0 right-0 w-5 h-5 bg-black/60 text-white text-xs rounded-bl flex items-center justify-center opacity-0 group-hover:opacity-100">×</button>`;
                if (preview) preview.appendChild(div);
            }
        } catch (e) { /* skip */ }
    }
    if (inputOrFiles && !Array.isArray(inputOrFiles) && inputOrFiles.value !== undefined) inputOrFiles.value = '';
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
    if (typeof updateGeneratePointsDisplay === 'function') updateGeneratePointsDisplay();
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
    if (typeof updateGeneratePointsDisplay === 'function') updateGeneratePointsDisplay();
}

function setAspectRatio(ratio, width, height) {
    if (window.currentSettings) window.currentSettings.aspectRatio = ratio;
    document.getElementById('aspect-ratio').textContent = ratio;
    document.getElementById('width-input').value = width;
    document.getElementById('height-input').value = height;
    updateParamsDialogUI();
}

function useTemplate(template) {
    const promptInput = document.getElementById('prompt-input');
    if (promptInput) {
        promptInput.value = template.prompt || template.title || '';
        promptInput.focus();
    }
    if (template.modelId && window.currentCreationType === 'image' && window.modelsData) {
        const modelId = template.modelId.toLowerCase().replace(/\s+/g, '-');
        const model = window.modelsData.find(function(m) {
            return (m.id || '').toLowerCase().replace(/\s+/g, '-') === modelId ||
                   (m.id || '').toLowerCase() === modelId.replace(/-/g, '_');
        });
        if (model) {
            document.getElementById('selected-model').textContent = model.name;
            if (window.currentSettings) window.currentSettings.selectedModel = model.id || modelId;
            if (typeof updateGeneratePointsDisplay === 'function') updateGeneratePointsDisplay();
        }
    }
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

function sanitizeMediaUrl(url) {
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

const GEN_PENDING_KEY = 'gen_pending_tasks';
const GEN_RECENT_RESULTS_KEY = 'gen_recent_results';

function safeReadJSONFromStorage(key, fallback) {
    try {
        const raw = localStorage.getItem(key);
        if (!raw) return fallback;
        const parsed = JSON.parse(raw);
        return parsed ?? fallback;
    } catch (e) {
        return fallback;
    }
}

function loadPendingTasks() {
    const list = safeReadJSONFromStorage(GEN_PENDING_KEY, []);
    if (!Array.isArray(list)) return [];
    return list.filter(function (item) {
        return item && typeof item.taskId === 'string' && item.taskId.trim() !== '';
    });
}

function savePendingTasks(tasks) {
    try {
        localStorage.setItem(GEN_PENDING_KEY, JSON.stringify(Array.isArray(tasks) ? tasks : []));
    } catch (e) {
        // ignore
    }
}

function loadRecentResults() {
    const list = safeReadJSONFromStorage(GEN_RECENT_RESULTS_KEY, []);
    return Array.isArray(list) ? list : [];
}

function saveRecentResults(results) {
    try {
        localStorage.setItem(GEN_RECENT_RESULTS_KEY, JSON.stringify(Array.isArray(results) ? results : []));
    } catch (e) {
        // ignore
    }
}

function upsertPendingTasks(taskIds, type, prompt, meta, totalCount) {
    const ids = Array.isArray(taskIds) ? taskIds.filter(Boolean) : [];
    if (ids.length === 0) return;
    const current = loadPendingTasks();
    const indexMap = {};
    current.forEach(function (it, idx) {
        indexMap[it.taskId] = idx;
    });
    const now = Date.now();
    ids.forEach(function (id, idx) {
        const task = {
            taskId: String(id),
            type: type === 'video' ? 'video' : 'image',
            prompt: String(prompt || ''),
            createdAt: now,
            meta: meta || {},
            slotIndex: idx,
            totalCount: Math.max(1, Number(totalCount || ids.length || 1)),
        };
        if (indexMap[task.taskId] !== undefined) {
            current[indexMap[task.taskId]] = Object.assign({}, current[indexMap[task.taskId]], task);
        } else {
            current.push(task);
        }
    });
    savePendingTasks(current);
}

function removePendingTask(taskId) {
    const id = String(taskId || '');
    if (!id) return false;
    const current = loadPendingTasks();
    const next = current.filter(function (item) { return item.taskId !== id; });
    if (next.length === current.length) return false;
    savePendingTasks(next);
    return true;
}

function pushRecentResult(entry) {
    if (!entry || !entry.taskId) return;
    const list = loadRecentResults();
    const filtered = list.filter(function (it) { return it.taskId !== entry.taskId; });
    filtered.unshift(entry);
    saveRecentResults(filtered.slice(0, 20));
}

function renderHeaderGenerationStatus() {
    const el = document.getElementById('header-gen-status');
    if (!el) return;
    const pending = loadPendingTasks();
    const pendingCount = pending.length;
    if (pendingCount <= 0) {
        el.classList.add('hidden');
        return;
    }
    const jumpType = String((pending[0] && pending[0].type) || 'image');
    el.dataset.type = jumpType === 'video' ? 'video' : 'image';
    el.textContent = `生成中 ${pendingCount}`;
    el.classList.remove('hidden');
}

function jumpToPendingGeneration() {
    const el = document.getElementById('header-gen-status');
    const type = String(el?.dataset?.type || 'image');
    window.location.href = `?tab=create&type=${type === 'video' ? 'video' : 'image'}`;
}

function resolvePendingTask(task, status, payload, options) {
    const opts = options || {};
    if (!task || !task.taskId) return;
    const removed = removePendingTask(task.taskId);
    const finishedAt = Date.now();
    pushRecentResult({
        taskId: task.taskId,
        type: task.type || 'image',
        prompt: task.prompt || '',
        status: status,
        resultUrl: payload?.resultUrl || '',
        errorMessage: payload?.errorMessage || '',
        createdAt: task.createdAt || finishedAt,
        finishedAt: finishedAt,
    });
    renderHeaderGenerationStatus();
    if (!removed || opts.renderInCreatePage === false) return;

    // 只在创作页尝试恢复/替换进度卡片
    if (!document.getElementById('generation-messages')) return;
    const slotMap = window.__pendingTaskSlotMap || {};
    const slotIndex = Number.isInteger(slotMap[task.taskId]) ? slotMap[task.taskId] : 0;
    if (status === 'completed') {
        showGenerationResult(payload?.resultUrl || '', task.prompt || '', task.meta || { type: task.type }, slotIndex);
    } else {
        showGenerationError(payload?.errorMessage || '生成失败，请重试', task.prompt || '', task.meta || { type: task.type }, slotIndex);
    }
    delete slotMap[task.taskId];
    window.__pendingTaskSlotMap = slotMap;
}

async function pollPendingTasksLightweight() {
    if (window.__pendingPollInFlight) return;
    const pending = loadPendingTasks();
    renderHeaderGenerationStatus();
    if (!pending.length) return;
    window.__pendingPollInFlight = true;
    try {
        const checks = pending.slice(0, 8).map(async function (task) {
            try {
                const res = await fetch('api/generation/status.php?taskId=' + encodeURIComponent(task.taskId));
                const data = await res.json();
                if (!data.success) return;
                const status = data.data?.status;
                if (status === 'completed') {
                    resolvePendingTask(task, 'completed', { resultUrl: data.data?.resultUrl || '' });
                } else if (status === 'failed') {
                    resolvePendingTask(task, 'failed', { errorMessage: data.data?.errorMessage || '生成失败' });
                }
            } catch (e) {
                // ignore single task polling error
            }
        });
        await Promise.all(checks);
    } finally {
        window.__pendingPollInFlight = false;
    }
}

function restorePendingTasksOnCreatePage() {
    const container = document.getElementById('generation-messages');
    if (!container) return;
    const currentType = window.currentCreationType || 'image';
    const pending = loadPendingTasks().filter(function (item) {
        return (item.type || 'image') === currentType;
    });
    if (!pending.length) return;

    enterCreatingMode();
    container.innerHTML = '';
    container.className = pending.length === 1 ? 'space-y-6' : 'grid grid-cols-1 md:grid-cols-2 gap-4';
    window.currentProcessingMsgIds = [];
    window.__pendingTaskSlotMap = {};

    pending.forEach(function (task, idx) {
        const slotIndex = Number.isInteger(task.slotIndex) ? task.slotIndex : idx;
        const totalSlots = Math.max(1, Number(task.totalCount || pending.length));
        const meta = task.meta || { type: task.type || currentType };
        const card = createProcessingMessage(task.prompt || '生成中...', meta, slotIndex, totalSlots);
        card.el.dataset.msgId = card.id;
        card.el.dataset.slotIndex = String(slotIndex);
        container.appendChild(card.el);
        window.currentProcessingMsgIds.push(card.id);
        window.__pendingTaskSlotMap[task.taskId] = idx;
    });

    initGenerationBatch(pending.length);
    setGeneratingNow(true);
    setGenerateBtnLoading(true);
    updateStatusBar(`生成中 ${pending.length}`);
    setTimeout(scrollToLatestGeneration, 100);
    if (typeof lucide !== 'undefined') lucide.createIcons();

    // 重新启动详细轮询，否则进度会一直卡在 0%（从其他页面返回时 pollTaskStatus 已停止）
    pending.forEach(function (task, idx) {
        const slotIndex = Number.isInteger(task.slotIndex) ? task.slotIndex : idx;
        const totalSlots = Math.max(1, Number(task.totalCount || pending.length));
        const meta = task.meta || { type: task.type || currentType };
        pollTaskStatus(task.taskId, task.type || 'image', task.prompt || '', meta, slotIndex, totalSlots);
    });
}

function startGlobalPendingTaskPolling() {
    if (window.__pendingPollTimer) return;
    pollPendingTasksLightweight();
    window.__pendingPollTimer = setInterval(pollPendingTasksLightweight, 4000);
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

function updateGeneratePointsDisplay() {
    const badge = document.getElementById('generate-points-badge');
    const valueEl = document.getElementById('generate-points-value');
    if (!badge || !valueEl) return;
    const creationType = window.currentCreationType || 'image';
    if (creationType === 'video') {
        const pricing = window.pointsPricingVideo || {};
        const basePer5s = Number(pricing?.doubao_video?.points_per_5s || 55);
        const durationText = String(document.getElementById('video-duration')?.textContent || '5s');
        const duration = Math.max(1, Math.min(30, parseInt(durationText, 10) || 5));
        const total = Math.max(1, Math.ceil(basePer5s * (duration / 5)));
        valueEl.textContent = total;
        return;
    }
    const pricing = window.pointsPricingImage || {};
    const modelId = (window.currentSettings?.selectedModel || document.getElementById('selected-model')?.textContent || 'banana').toLowerCase().replace(/\s+/g, '_');
    const modelKey = modelId === 'banana_pro' || modelId === 'banana-pro' ? 'banana_pro' : 'banana';
    const quality = (window.currentSettings?.quality || '2k').toLowerCase();
    const qualityKey = quality === '4k' ? '4k' : '2k';
    const count = Math.max(1, Math.min(4, Number(window.currentSettings?.count || 1)));
    const perImage = pricing[modelKey]?.[qualityKey] ?? 5;
    const total = perImage * count;
    valueEl.textContent = total;
}

function isGeneratingNow() {
    return Boolean(window.__genInFlight);
}

function setGeneratingNow(flag) {
    window.__genInFlight = Boolean(flag);
}

function isVideoMeta(meta) {
    return Boolean(meta && meta.type === 'video');
}

function getSlotBadgeText(meta, slotIndex = 0, totalSlots = 1) {
    if (isVideoMeta(meta)) return '视频任务';
    return `第${slotIndex + 1}/${totalSlots}张`;
}

function getCountBadgeText(meta) {
    if (isVideoMeta(meta)) return '1条';
    return String((meta && meta.count) || 1) + '张';
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
                <span class="px-2.5 py-0.5 text-xs rounded-full bg-[#EEF2FF] text-[#4F46E5]">${escapeHtml(getSlotBadgeText(meta, slotIndex, totalSlots))}</span>
                <span class="px-2.5 py-0.5 text-xs rounded-full bg-[#F5F5F5] text-[#666]">${escapeHtml((meta && meta.model) || '')}</span>
                <span class="px-2.5 py-0.5 text-xs rounded-full bg-[#F5F5F5] text-[#666]">${escapeHtml((meta && meta.quality) || '')}</span>
                <span class="px-2.5 py-0.5 text-xs rounded-full bg-[#F5F5F5] text-[#666]">${escapeHtml((meta && meta.aspectRatio) || '')}</span>
                <span class="px-2.5 py-0.5 text-xs rounded-full bg-[#F5F5F5] text-[#666]">${escapeHtml(getCountBadgeText(meta))}</span>
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

    const isVideo = (meta && meta.type === 'video') || /\.(mp4|webm|mov)(\?|$)/i.test(imageUrl || '');
    const mediaHtml = imageUrl
        ? (isVideo
            ? `<video src="${escapeHtml(imageUrl)}" controls class="w-full rounded-2xl shadow-sm block max-h-[500px] object-contain bg-[#FAFAFA]"></video>`
            : `<img src="${escapeHtml(imageUrl)}" alt="生成结果" class="w-full rounded-2xl shadow-sm block max-h-[500px] object-contain bg-[#FAFAFA]" />`)
        : '<div class="h-[200px] flex items-center justify-center text-[#999] rounded-2xl bg-[#F5F5F5]">暂无预览</div>';

    msg.innerHTML = `
        <div class="px-5 pt-4 pb-2">
            <div class="text-sm font-medium text-[#1A1A1A] mb-2">${escapeHtml(prompt || '')}</div>
            <div class="flex flex-wrap gap-2 mb-3">
                <span class="px-2.5 py-0.5 text-xs rounded-full bg-[#EEF2FF] text-[#4F46E5]">${escapeHtml(getSlotBadgeText(meta, slotIndex, totalSlots))}</span>
                <span class="px-2.5 py-0.5 text-xs rounded-full bg-[#F5F5F5] text-[#666]">${escapeHtml((meta && meta.model) || '')}</span>
                <span class="px-2.5 py-0.5 text-xs rounded-full bg-[#F5F5F5] text-[#666]">${escapeHtml((meta && meta.quality) || '')}</span>
                <span class="px-2.5 py-0.5 text-xs rounded-full bg-[#F5F5F5] text-[#666]">${escapeHtml((meta && meta.aspectRatio) || '')}</span>
                <span class="px-2.5 py-0.5 text-xs rounded-full bg-[#F5F5F5] text-[#666]">${escapeHtml(getCountBadgeText(meta))}</span>
            </div>
        </div>
        <div class="px-5 pb-4">
            <div class="relative">
                ${mediaHtml}
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
                <span class="px-2.5 py-0.5 text-xs rounded-full bg-[#EEF2FF] text-[#4F46E5]">${escapeHtml(getSlotBadgeText(meta, slotIndex, totalSlots))}</span>
                <span class="px-2.5 py-0.5 text-xs rounded-full bg-[#F5F5F5] text-[#666]">${escapeHtml((meta && meta.model) || '')}</span>
                <span class="px-2.5 py-0.5 text-xs rounded-full bg-[#F5F5F5] text-[#666]">${escapeHtml((meta && meta.quality) || '')}</span>
                <span class="px-2.5 py-0.5 text-xs rounded-full bg-[#F5F5F5] text-[#666]">${escapeHtml((meta && meta.aspectRatio) || '')}</span>
                <span class="px-2.5 py-0.5 text-xs rounded-full bg-[#F5F5F5] text-[#666]">${escapeHtml(getCountBadgeText(meta))}</span>
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
    refreshPointsSummary();

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
    refreshPointsSummary();

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
    if (!window.currentUser || !window.currentUser.id) {
        openAuthDialog('login');
        updateStatusBar('请先登录后再生成');
        return;
    }

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
        type,
        model: document.getElementById('selected-model')?.textContent || modelId,
        quality: type === 'video'
            ? ((settings.videoQuality || 'standard') === 'high' ? '高品质' : '标准')
            : (settings.quality || '2k').toUpperCase(),
        aspectRatio: aspectRatio,
        count: type === 'video' ? 1 : Number(settings.count || 1),
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
        payload.quality = settings.videoQuality || 'standard';
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
            if (data.data?.wallet) {
                applyWalletSummary(data.data.wallet);
            } else {
                refreshPointsSummary();
            }
            const taskId = data.data?.taskId;
            const taskIds = Array.isArray(data.data?.taskIds) && data.data.taskIds.length > 0
                ? data.data.taskIds
                : (taskId ? [taskId] : []);
            const status = data.data?.status;
            const submittedCount = Number(data.data?.submittedCount || taskIds.length || 1);
            if (taskIds.length > 0 && status === 'processing') {
                upsertPendingTasks(taskIds, type, prompt, meta, meta.count);
                renderHeaderGenerationStatus();
            }
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
            } else if (type === 'video' && taskIds.length > 0 && status === 'processing') {
                initGenerationBatch(1);
                updateStatusBar('0/1 生成中...');
                pollTaskStatus(taskIds[0], type, prompt, meta, 0, 1);
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
    const bumpProgress = function (customLabel) {
        loopCount += 1;
        progress = Math.min(99, progress + (loopCount < 40 ? 2 : 1));
        const label = customLabel || (isVideoMeta(meta)
            ? `${Math.round(progress)}% 生成中...`
            : `第${slotIndex + 1}张 ${Math.round(progress)}% 生成中...`);
        updateGenerationProgress(Math.round(progress), label, slotIndex);
    };

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
                bumpProgress(isVideoMeta(meta) ? `${Math.round(progress)}% 状态同步中...` : `第${slotIndex + 1}张 ${Math.round(progress)}% 状态同步中...`);
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
                const doneTask = {
                    taskId: taskId,
                    type: type || 'image',
                    prompt: prompt || '',
                    meta: meta || {},
                    createdAt: Date.now(),
                };
                resolvePendingTask(doneTask, 'completed', { resultUrl: url }, { renderInCreatePage: false });
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
                const failedTask = {
                    taskId: taskId,
                    type: type || 'image',
                    prompt: prompt || '',
                    meta: meta || {},
                    createdAt: Date.now(),
                };
                resolvePendingTask(failedTask, 'failed', {
                    errorMessage: data.data?.errorMessage || '生成失败，请尝试更换提示词或图片后重试',
                }, { renderInCreatePage: false });
                showGenerationError(data.data?.errorMessage || '生成失败，请尝试更换提示词或图片后重试', prompt, meta, slotIndex);
                return;
            }

            // 0=排队中 1=生成中：持续轮询，进度卡在 99%
            bumpProgress();
            await new Promise(r => setTimeout(r, interval));
        } catch (e) {
            console.error('[轮询异常]', e);
            networkErrorStreak += 1;
            if (networkErrorStreak >= 20) {
                showGenerationError('网络异常次数过多，请检查网络后重试', prompt, meta, slotIndex);
                return;
            }
            bumpProgress(isVideoMeta(meta) ? `${Math.round(progress)}% 网络重试中...` : `第${slotIndex + 1}张 ${Math.round(progress)}% 网络重试中...`);
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
        sheet.style.display = 'block';
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
    const authDialog = document.getElementById('auth-dialog');
    const pointsDialog = document.getElementById('points-dialog');
    const membershipDialog = document.getElementById('membership-dialog');
    if (modelDialog) modelDialog.style.display = 'none';
    if (paramsDialog) paramsDialog.style.display = 'none';
    if (authDialog) authDialog.style.display = 'none';
    if (pointsDialog) pointsDialog.style.display = 'none';
    if (membershipDialog) membershipDialog.style.display = 'none';

    refreshPointsSummary();
    if (typeof updateGeneratePointsDisplay === 'function') updateGeneratePointsDisplay();
    renderHeaderGenerationStatus();
    restorePendingTasksOnCreatePage();
    startGlobalPendingTaskPolling();

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

    // 参考图、首帧、尾帧 拖拽上传
    initImageDropZones();
});

// ============================
// 拖拽上传：参考图、首帧、尾帧
// ============================
function initImageDropZones() {
    function addDropHandlers(el, onDrop) {
        if (!el) return;
        el.addEventListener('dragover', function (e) {
            e.preventDefault();
            e.stopPropagation();
            el.classList.add('border-[#3B82F6]', 'bg-[#F0F7FF]');
        });
        el.addEventListener('dragleave', function (e) {
            e.preventDefault();
            e.stopPropagation();
            if (!el.contains(e.relatedTarget)) {
                el.classList.remove('border-[#3B82F6]', 'bg-[#F0F7FF]');
            }
        });
        el.addEventListener('drop', function (e) {
            e.preventDefault();
            e.stopPropagation();
            el.classList.remove('border-[#3B82F6]', 'bg-[#F0F7FF]');
            const files = Array.from(e.dataTransfer?.files || []);
            if (files.length) onDrop(files);
        });
    }

    // 首帧
    const firstFrameDrop = document.getElementById('first-frame-drop');
    addDropHandlers(firstFrameDrop, function (files) {
        const img = files.find(function (f) { return f.type.startsWith('image/'); });
        if (img) handleFrameUpload(img, 'first-frame-preview', 'first-frame');
    });

    // 尾帧
    const lastFrameDrop = document.getElementById('last-frame-drop');
    addDropHandlers(lastFrameDrop, function (files) {
        const img = files.find(function (f) { return f.type.startsWith('image/'); });
        if (img) handleFrameUpload(img, 'last-frame-preview', 'last-frame');
    });

    // 参考图（图片生成模式）
    const refImagesUpload = document.getElementById('ref-images-upload');
    addDropHandlers(refImagesUpload, function (files) {
        const images = files.filter(function (f) { return f.type.startsWith('image/'); });
        if (images.length) handleRefImagesUpload(images);
    });
}
