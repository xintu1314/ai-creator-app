<div class="bg-white rounded-xl border border-[#E5E5E5] p-5">
    <h2 class="text-base font-medium text-[#1A1A1A] mb-1">运营看板</h2>
    <p class="text-sm text-[#666666]">查看用户增长、生成任务与积分消耗。</p>
    <div id="admin-dashboard-root" class="mt-4"></div>
</div>

<script>
function adminDashboardCard(title, value, subText) {
    return `
        <div class="rounded-xl border border-[#EAEAEA] bg-white p-4">
            <div class="text-xs text-[#666666]">${title}</div>
            <div class="text-2xl font-semibold text-[#1A1A1A] mt-1">${value}</div>
            <div class="text-xs text-[#999999] mt-1">${subText || ''}</div>
        </div>
    `;
}

async function adminDashboardLoad() {
    const root = document.getElementById('admin-dashboard-root');
    if (!root) return;
    root.innerHTML = '<div class="text-sm text-[#999999]">加载中...</div>';
    try {
        const res = await fetch('api/admin/dashboard/overview.php');
        const ret = await res.json();
        if (!ret.success) {
            root.innerHTML = '<div class="text-sm text-red-500">' + (ret.message || '加载失败') + '</div>';
            return;
        }
        const d = ret.data || {};
        const k = d.kpis || {};
        const taskType = d.todayTaskTypeDistribution || [];
        const modelTop = d.todayModelTop || [];
        const recentUsers = d.recentUsers || [];

        root.innerHTML = `
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                ${adminDashboardCard('总用户数', k.totalUsers || 0, '今日新增 ' + (k.todayUsers || 0))}
                ${adminDashboardCard('任务总数', k.totalTasks || 0, '今日任务 ' + (k.todayTasks || 0))}
                ${adminDashboardCard('今日完成/失败', (k.todayCompleted || 0) + ' / ' + (k.todayFailed || 0), '任务状态')}
                ${adminDashboardCard('今日消耗积分', k.todayPointsConsume || 0, 'source=generate_consume')}
                ${adminDashboardCard('模板总数', k.totalTemplates || 0, '今日新增 ' + (k.todayTemplates || 0))}
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-4">
                <div class="rounded-xl border border-[#EAEAEA] p-4">
                    <div class="text-sm font-medium text-[#1A1A1A] mb-2">今日任务类型分布</div>
                    ${
                        taskType.length
                        ? taskType.map((x) => `<div class="flex items-center justify-between text-sm py-1"><span>${x.type}</span><span class="font-medium">${x.count}</span></div>`).join('')
                        : '<div class="text-sm text-[#999999]">暂无数据</div>'
                    }
                </div>

                <div class="rounded-xl border border-[#EAEAEA] p-4">
                    <div class="text-sm font-medium text-[#1A1A1A] mb-2">今日模型使用 Top</div>
                    ${
                        modelTop.length
                        ? modelTop.map((x) => `<div class="flex items-center justify-between text-sm py-1"><span class="line-clamp-1">${x.model}</span><span class="font-medium">${x.count}</span></div>`).join('')
                        : '<div class="text-sm text-[#999999]">暂无数据</div>'
                    }
                </div>
            </div>

            <div class="rounded-xl border border-[#EAEAEA] p-4 mt-4">
                <div class="text-sm font-medium text-[#1A1A1A] mb-2">最近注册用户</div>
                ${
                    recentUsers.length
                    ? `<div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead><tr class="text-left text-[#666666] border-b border-[#EEEEEE]">
                                <th class="py-2 pr-4">用户</th>
                                <th class="py-2 pr-4">角色</th>
                                <th class="py-2 pr-4">状态</th>
                                <th class="py-2">注册时间</th>
                            </tr></thead>
                            <tbody>
                                ${recentUsers.map((u) => `
                                    <tr class="border-b border-[#F4F4F4]">
                                        <td class="py-2 pr-4">#${u.id} · ${(u.nickname || u.account || '')}<div class="text-xs text-[#999999]">${u.account || ''} ${u.phone || ''}</div></td>
                                        <td class="py-2 pr-4">${u.role || 'user'}</td>
                                        <td class="py-2 pr-4">${u.status || 'active'}</td>
                                        <td class="py-2">${(u.createdAt || '').replace('T', ' ').slice(0, 16)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>`
                    : '<div class="text-sm text-[#999999]">暂无数据</div>'
                }
            </div>
        `;
    } catch (e) {
        root.innerHTML = '<div class="text-sm text-red-500">网络错误</div>';
    }
}

document.addEventListener('DOMContentLoaded', adminDashboardLoad);
</script>
