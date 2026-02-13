<div class="bg-white rounded-xl border border-[#E5E5E5] p-5">
    <h2 class="text-base font-medium text-[#1A1A1A] mb-1">用户管理</h2>
    <p class="text-sm text-[#666666]">查看、筛选并管理用户账号状态。</p>

    <div class="mt-4 flex flex-wrap gap-2">
        <input id="admin-users-q" type="text" placeholder="搜索ID/账号/手机号/昵称" class="h-9 px-3 text-sm border border-[#E5E5E5] rounded-lg w-[260px]" />
        <select id="admin-users-status" class="h-9 px-3 text-sm border border-[#E5E5E5] rounded-lg">
            <option value="">全部状态</option>
            <option value="active">active</option>
            <option value="disabled">disabled</option>
        </select>
        <select id="admin-users-role" class="h-9 px-3 text-sm border border-[#E5E5E5] rounded-lg">
            <option value="">全部角色</option>
            <option value="user">user</option>
            <option value="admin">admin</option>
        </select>
        <button type="button" id="admin-users-search-btn" class="h-9 px-4 text-sm bg-[#3B82F6] hover:bg-[#2563EB] text-white rounded-lg">查询</button>
    </div>

    <div class="mt-4 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
            <tr class="text-left text-[#666666] border-b border-[#EEEEEE]">
                <th class="py-2 pr-4">用户</th>
                <th class="py-2 pr-4">角色</th>
                <th class="py-2 pr-4">状态</th>
                <th class="py-2 pr-4">积分</th>
                <th class="py-2 pr-4">任务/模板</th>
                <th class="py-2 pr-4">注册时间</th>
                <th class="py-2">操作</th>
            </tr>
            </thead>
            <tbody id="admin-users-tbody"></tbody>
        </table>
    </div>
    <div id="admin-users-empty" class="hidden text-sm text-[#999999] py-6 text-center">暂无数据</div>
</div>

<script>
function adminUsersEsc(s) {
    if (s == null) return '';
    const div = document.createElement('div');
    div.textContent = String(s);
    return div.innerHTML;
}

async function adminUsersLoad() {
    const q = document.getElementById('admin-users-q')?.value?.trim() || '';
    const status = document.getElementById('admin-users-status')?.value || '';
    const role = document.getElementById('admin-users-role')?.value || '';
    const params = new URLSearchParams();
    if (q) params.set('q', q);
    if (status) params.set('status', status);
    if (role) params.set('role', role);
    params.set('limit', '50');

    const tbody = document.getElementById('admin-users-tbody');
    const empty = document.getElementById('admin-users-empty');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="7" class="py-4 text-[#999999]">加载中...</td></tr>';
    if (empty) empty.classList.add('hidden');

    try {
        const res = await fetch('api/admin/users/list.php?' + params.toString());
        const ret = await res.json();
        if (!ret.success) {
            tbody.innerHTML = '<tr><td colspan="7" class="py-4 text-red-500">' + adminUsersEsc(ret.message || '加载失败') + '</td></tr>';
            return;
        }
        const list = ret.data?.list || [];
        if (!list.length) {
            tbody.innerHTML = '';
            if (empty) empty.classList.remove('hidden');
            return;
        }
        tbody.innerHTML = list.map((u) => {
            const name = u.nickname || u.account || ('用户' + u.id);
            const phone = u.phone || '-';
            const roleOptions = ['user', 'admin'].map((r) => `<option value="${adminUsersEsc(r)}" ${u.role === r ? 'selected' : ''}>${adminUsersEsc(r)}</option>`).join('');
            const statusText = u.status || 'active';
            return `
                <tr class="border-b border-[#F4F4F4]">
                    <td class="py-2 pr-4">
                        <div class="font-medium text-[#1A1A1A]">${adminUsersEsc(name)}</div>
                        <div class="text-xs text-[#999999]">#${adminUsersEsc(u.id)} · ${adminUsersEsc(u.account)} · ${adminUsersEsc(phone)}</div>
                    </td>
                    <td class="py-2 pr-4">
                        <select class="h-8 px-2 text-xs border border-[#E5E5E5] rounded" onchange="adminUserSetRole(${u.id}, this.value)">
                            ${roleOptions}
                        </select>
                    </td>
                    <td class="py-2 pr-4">
                        <span class="px-2 py-0.5 text-xs rounded ${statusText === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}">${adminUsersEsc(statusText)}</span>
                    </td>
                    <td class="py-2 pr-4">${adminUsersEsc(u.wallet?.totalBalance ?? 0)}（付费${adminUsersEsc(u.wallet?.paidBalance ?? 0)}/赠送${adminUsersEsc(u.wallet?.bonusBalance ?? 0)}）</td>
                    <td class="py-2 pr-4">${adminUsersEsc(u.taskCount || 0)} / ${adminUsersEsc(u.templateCount || 0)}</td>
                    <td class="py-2 pr-4 text-xs text-[#666666]">${adminUsersEsc((u.createdAt || '').replace('T', ' ').slice(0, 16))}</td>
                    <td class="py-2">
                        <div class="flex flex-wrap gap-1">
                            <button class="h-7 px-2 text-xs rounded bg-[#F5F5F5] hover:bg-[#EDEDED]" onclick="adminUserToggleStatus(${u.id}, '${statusText}')">${statusText === 'active' ? '禁用' : '启用'}</button>
                            <button class="h-7 px-2 text-xs rounded bg-[#FFF4E6] hover:bg-[#FFE8CC] text-[#B45309]" onclick="adminUserResetPassword(${u.id})">重置密码</button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="7" class="py-4 text-red-500">网络错误</td></tr>';
    }
}

async function adminUserPost(payload) {
    const res = await fetch('api/admin/users/update.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(payload),
    });
    return await res.json();
}

async function adminUserSetRole(userId, role) {
    const ret = await adminUserPost({action: 'set_role', userId, role});
    if (!ret.success) {
        alert(ret.message || '角色更新失败');
    }
    adminUsersLoad();
}

async function adminUserToggleStatus(userId, currentStatus) {
    const status = currentStatus === 'active' ? 'disabled' : 'active';
    const ok = confirm('确认将用户状态设置为：' + status + ' ?');
    if (!ok) return;
    const ret = await adminUserPost({action: 'set_status', userId, status});
    if (!ret.success) {
        alert(ret.message || '状态更新失败');
    }
    adminUsersLoad();
}

async function adminUserResetPassword(userId) {
    const customPwd = prompt('输入新密码（留空则自动生成）:', '');
    const payload = {action: 'reset_password', userId};
    if (customPwd && customPwd.trim()) payload.newPassword = customPwd.trim();
    const ret = await adminUserPost(payload);
    if (!ret.success) {
        alert(ret.message || '重置失败');
        return;
    }
    alert('新密码：' + (ret.data?.newPassword || '已重置'));
}

document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('admin-users-search-btn');
    if (btn) btn.addEventListener('click', adminUsersLoad);
    const q = document.getElementById('admin-users-q');
    if (q) q.addEventListener('keydown', function(e){ if (e.key === 'Enter') adminUsersLoad(); });
    adminUsersLoad();
});
</script>
