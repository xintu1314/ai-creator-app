<div class="bg-white rounded-xl border border-[#E5E5E5] p-5">
    <h2 class="text-base font-medium text-[#1A1A1A] mb-1">模板管理</h2>
    <p class="text-sm text-[#666666]">审核模板、上下线与推荐管理。</p>

    <div class="mt-4 flex flex-wrap gap-2">
        <select id="admin-templates-type" class="h-9 px-3 text-sm border border-[#E5E5E5] rounded-lg">
            <option value="">全部类型</option>
            <option value="image">image</option>
            <option value="video">video</option>
        </select>
        <select id="admin-templates-review" class="h-9 px-3 text-sm border border-[#E5E5E5] rounded-lg">
            <option value="">全部审核状态</option>
            <option value="pending">pending</option>
            <option value="approved">approved</option>
            <option value="rejected">rejected</option>
        </select>
        <select id="admin-templates-online" class="h-9 px-3 text-sm border border-[#E5E5E5] rounded-lg">
            <option value="">全部上下线</option>
            <option value="1">在线</option>
            <option value="0">下线</option>
        </select>
        <input id="admin-templates-q" type="text" placeholder="搜索标题/作者/模型" class="h-9 px-3 text-sm border border-[#E5E5E5] rounded-lg w-[260px]" />
        <button type="button" id="admin-templates-search-btn" class="h-9 px-4 text-sm bg-[#111827] hover:bg-black text-white rounded-lg">查询</button>
    </div>

    <div class="mt-4 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
            <tr class="text-left text-[#666666] border-b border-[#EEEEEE]">
                <th class="py-2 pr-4">模板</th>
                <th class="py-2 pr-4">作者</th>
                <th class="py-2 pr-4">审核</th>
                <th class="py-2 pr-4">上线</th>
                <th class="py-2 pr-4">创建时间</th>
                <th class="py-2">操作</th>
            </tr>
            </thead>
            <tbody id="admin-templates-tbody"></tbody>
        </table>
    </div>
</div>

<script>
function adminEsc(s) {
    if (s == null) return '';
    const div = document.createElement('div');
    div.textContent = String(s);
    return div.innerHTML;
}

function adminSafeMediaUrl(url) {
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

async function adminTemplatesLoad() {
    const type = document.getElementById('admin-templates-type')?.value || '';
    const reviewStatus = document.getElementById('admin-templates-review')?.value || '';
    const isOnline = document.getElementById('admin-templates-online')?.value || '';
    const q = document.getElementById('admin-templates-q')?.value?.trim() || '';
    const params = new URLSearchParams();
    if (type) params.set('type', type);
    if (reviewStatus) params.set('reviewStatus', reviewStatus);
    if (isOnline !== '') params.set('isOnline', isOnline);
    if (q) params.set('q', q);
    params.set('limit', '120');

    const tbody = document.getElementById('admin-templates-tbody');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="6" class="py-4 text-[#999999]">加载中...</td></tr>';

    try {
        const res = await fetch('api/admin/templates/list.php?' + params.toString());
        const ret = await res.json();
        if (!ret.success) {
            tbody.innerHTML = '<tr><td colspan="6" class="py-4 text-red-500">' + adminEsc(ret.message || '加载失败') + '</td></tr>';
            return;
        }
        const list = ret.data?.list || [];
        if (!list.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="py-4 text-[#999999]">暂无模板</td></tr>';
            return;
        }
        tbody.innerHTML = list.map((t) => {
            const author = t.author?.nickname || t.author?.account || ('用户' + t.userId);
            const review = t.reviewStatus || 'approved';
            const reviewClass = review === 'approved' ? 'bg-green-100 text-green-700' : (review === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700');
            const safeImage = adminSafeMediaUrl(t.image);
            const isVideo = String(t.type || '') === 'video' || /\.(mp4|webm|mov|avi|mpeg)(\?|$)/i.test(safeImage || '');
            const mediaHtml = !safeImage
                ? '<div class="w-10 h-12 rounded bg-[#F3F4F6]"></div>'
                : (isVideo
                    ? `<video src="${safeImage}" class="w-10 h-12 rounded object-cover border border-[#EEE]" muted playsinline preload="metadata"></video>`
                    : `<img src="${safeImage}" alt="" class="w-10 h-12 rounded object-cover border border-[#EEE]" />`);
            return `
                <tr class="border-b border-[#F4F4F4]">
                    <td class="py-2 pr-4">
                        <div class="flex items-center gap-2">
                            ${mediaHtml}
                            <div>
                                <div class="font-medium text-[#1A1A1A] line-clamp-1">${adminEsc(t.title || '')}</div>
                                <div class="text-xs text-[#999999]">#${adminEsc(t.id)} · ${adminEsc(t.type)} · ${adminEsc(t.modelName || t.modelId || '')}</div>
                            </div>
                        </div>
                    </td>
                    <td class="py-2 pr-4 text-xs text-[#666666]">${adminEsc(author)}<br/>${adminEsc(t.author?.phone || '')}</td>
                    <td class="py-2 pr-4"><span class="px-2 py-0.5 text-xs rounded ${reviewClass}">${adminEsc(review)}</span></td>
                    <td class="py-2 pr-4">${t.isOnline ? '在线' : '下线'}</td>
                    <td class="py-2 pr-4 text-xs text-[#666666]">${adminEsc((t.createdAt || '').replace('T', ' ').slice(0, 16))}</td>
                    <td class="py-2">
                        <div class="flex flex-wrap gap-1">
                            <button class="h-7 px-2 text-xs rounded bg-[#ECFDF5] text-[#166534] hover:bg-[#D1FAE5]" onclick="adminTemplateSetReview(${t.id}, 'approved')">通过</button>
                            <button class="h-7 px-2 text-xs rounded bg-[#FEF3C7] text-[#92400E] hover:bg-[#FDE68A]" onclick="adminTemplateSetReview(${t.id}, 'pending')">待审</button>
                            <button class="h-7 px-2 text-xs rounded bg-[#FEE2E2] text-[#B91C1C] hover:bg-[#FECACA]" onclick="adminTemplateSetReview(${t.id}, 'rejected')">驳回</button>
                            <button class="h-7 px-2 text-xs rounded bg-[#F5F5F5] hover:bg-[#EDEDED]" onclick="adminTemplateToggleOnline(${t.id}, ${t.isOnline ? 'true' : 'false'})">${t.isOnline ? '下线' : '上线'}</button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="6" class="py-4 text-red-500">网络错误</td></tr>';
    }
}

async function adminTemplatePost(payload) {
    const res = await fetch('api/admin/templates/update.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(payload),
    });
    return await res.json();
}

async function adminTemplateSetReview(id, reviewStatus) {
    let reviewNote = '';
    if (reviewStatus === 'rejected') {
        reviewNote = prompt('请输入驳回原因（可选）:', '') || '';
    }
    const ret = await adminTemplatePost({action: 'set_review', id, reviewStatus, reviewNote});
    if (!ret.success) alert(ret.message || '更新失败');
    adminTemplatesLoad();
}

async function adminTemplateToggleOnline(id, isOnline) {
    const ret = await adminTemplatePost({action: 'set_online', id, isOnline: !isOnline});
    if (!ret.success) alert(ret.message || '更新失败');
    adminTemplatesLoad();
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('admin-templates-search-btn')?.addEventListener('click', adminTemplatesLoad);
    adminTemplatesLoad();
});
</script>
