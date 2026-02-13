<div class="bg-white rounded-xl border border-[#E5E5E5] p-5">
    <h2 class="text-base font-medium text-[#1A1A1A] mb-1">积分管理</h2>
    <p class="text-sm text-[#666666]">查询用户积分，手工加减并查看流水。</p>

    <div class="mt-4 p-4 rounded-lg bg-[#F8FAFF] border border-[#E6EEFF]">
        <div class="text-sm font-medium text-[#1A1A1A] mb-3">手工调整积分</div>
        <div class="flex flex-wrap gap-2">
            <input id="admin-points-user-id" type="number" min="1" placeholder="用户ID" class="h-9 px-3 text-sm border border-[#E5E5E5] rounded-lg w-[120px]" />
            <input id="admin-points-delta" type="number" placeholder="增减分值（如 100 / -50）" class="h-9 px-3 text-sm border border-[#E5E5E5] rounded-lg w-[220px]" />
            <input id="admin-points-reason" type="text" placeholder="调整原因（必填）" class="h-9 px-3 text-sm border border-[#E5E5E5] rounded-lg flex-1 min-w-[220px]" />
            <button type="button" id="admin-points-adjust-btn" class="h-9 px-4 text-sm bg-[#3B82F6] hover:bg-[#2563EB] text-white rounded-lg">提交调整</button>
        </div>
    </div>

    <div class="mt-4 flex flex-wrap gap-2">
        <input id="admin-points-ledger-user-id" type="number" min="1" placeholder="按用户ID筛选" class="h-9 px-3 text-sm border border-[#E5E5E5] rounded-lg w-[140px]" />
        <input id="admin-points-ledger-q" type="text" placeholder="按描述/source检索" class="h-9 px-3 text-sm border border-[#E5E5E5] rounded-lg w-[260px]" />
        <button type="button" id="admin-points-ledger-search-btn" class="h-9 px-4 text-sm bg-[#111827] hover:bg-black text-white rounded-lg">查询流水</button>
    </div>

    <div class="mt-4 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
            <tr class="text-left text-[#666666] border-b border-[#EEEEEE]">
                <th class="py-2 pr-4">时间</th>
                <th class="py-2 pr-4">用户</th>
                <th class="py-2 pr-4">变动</th>
                <th class="py-2 pr-4">余额</th>
                <th class="py-2 pr-4">来源</th>
                <th class="py-2">说明</th>
            </tr>
            </thead>
            <tbody id="admin-points-ledger-tbody"></tbody>
        </table>
    </div>
    <div id="admin-points-ledger-empty" class="hidden text-sm text-[#999999] py-6 text-center">暂无流水数据</div>
</div>

<script>
function adminPointsEsc(s) {
    if (s == null) return '';
    const div = document.createElement('div');
    div.textContent = String(s);
    return div.innerHTML;
}

async function adminPointsLoadLedger() {
    const userId = document.getElementById('admin-points-ledger-user-id')?.value?.trim() || '';
    const q = document.getElementById('admin-points-ledger-q')?.value?.trim() || '';
    const params = new URLSearchParams();
    if (userId) params.set('userId', userId);
    if (q) params.set('q', q);
    params.set('limit', '80');

    const tbody = document.getElementById('admin-points-ledger-tbody');
    const empty = document.getElementById('admin-points-ledger-empty');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="6" class="py-4 text-[#999999]">加载中...</td></tr>';
    if (empty) empty.classList.add('hidden');

    try {
        const res = await fetch('api/admin/points/ledger.php?' + params.toString());
        const ret = await res.json();
        if (!ret.success) {
            tbody.innerHTML = '<tr><td colspan="6" class="py-4 text-red-500">' + adminPointsEsc(ret.message || '加载失败') + '</td></tr>';
            return;
        }
        const list = ret.data?.list || [];
        if (!list.length) {
            tbody.innerHTML = '';
            if (empty) empty.classList.remove('hidden');
            return;
        }
        tbody.innerHTML = list.map((item) => {
            const delta = Number(item.changeAmount || 0);
            const name = item.user?.nickname || item.user?.account || ('用户' + item.userId);
            return `
                <tr class="border-b border-[#F4F4F4]">
                    <td class="py-2 pr-4 text-xs text-[#666666]">${adminPointsEsc((item.createdAt || '').replace('T', ' ').slice(0, 16))}</td>
                    <td class="py-2 pr-4">
                        <div class="text-[#1A1A1A]">#${adminPointsEsc(item.userId)} · ${adminPointsEsc(name)}</div>
                        <div class="text-xs text-[#999999]">${adminPointsEsc(item.user?.phone || '')}</div>
                    </td>
                    <td class="py-2 pr-4 ${delta >= 0 ? 'text-emerald-600' : 'text-red-500'} font-medium">${delta >= 0 ? '+' + delta : delta}</td>
                    <td class="py-2 pr-4">${adminPointsEsc(item.balanceAfter)}</td>
                    <td class="py-2 pr-4 text-xs text-[#666666]">${adminPointsEsc(item.source || '')}</td>
                    <td class="py-2">${adminPointsEsc(item.description || '')}</td>
                </tr>
            `;
        }).join('');
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="6" class="py-4 text-red-500">网络错误</td></tr>';
    }
}

async function adminPointsAdjust() {
    const userId = Number(document.getElementById('admin-points-user-id')?.value || 0);
    const delta = Number(document.getElementById('admin-points-delta')?.value || 0);
    const reason = (document.getElementById('admin-points-reason')?.value || '').trim();
    if (!userId || !delta || !reason) {
        alert('请填写完整：用户ID、增减分值、调整原因');
        return;
    }
    const ok = confirm('确认要执行积分调整吗？');
    if (!ok) return;

    try {
        const res = await fetch('api/admin/points/adjust.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({userId, delta, reason}),
        });
        const ret = await res.json();
        if (!ret.success) {
            alert(ret.message || '调整失败');
            return;
        }
        alert('调整成功');
        document.getElementById('admin-points-delta').value = '';
        document.getElementById('admin-points-reason').value = '';
        adminPointsLoadLedger();
    } catch (e) {
        alert('网络错误，调整失败');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const adjustBtn = document.getElementById('admin-points-adjust-btn');
    if (adjustBtn) adjustBtn.addEventListener('click', adminPointsAdjust);
    const searchBtn = document.getElementById('admin-points-ledger-search-btn');
    if (searchBtn) searchBtn.addEventListener('click', adminPointsLoadLedger);
    adminPointsLoadLedger();
});
</script>
