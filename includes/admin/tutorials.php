<div class="bg-white rounded-xl border border-[#E5E5E5] p-5">
    <h2 class="text-base font-medium text-[#1A1A1A] mb-1">教程管理</h2>
    <p class="text-sm text-[#666666]">维护教程视频内容，上下线与排序。</p>

    <div class="mt-4 p-4 rounded-lg bg-[#F8FAFF] border border-[#E6EEFF]">
        <div class="text-sm font-medium text-[#1A1A1A] mb-3">新建 / 编辑教程</div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
            <input id="admin-tutorial-id" type="number" min="0" placeholder="教程ID（编辑时自动填充）" readonly class="h-9 px-3 text-sm border border-[#E5E5E5] rounded-lg bg-[#F8FAFC] text-[#666666]" />
            <input id="admin-tutorial-title" type="text" placeholder="标题" class="h-9 px-3 text-sm border border-[#E5E5E5] rounded-lg" />
            <input id="admin-tutorial-cover" type="text" placeholder="封面URL（可选）" class="h-9 px-3 text-sm border border-[#E5E5E5] rounded-lg" />
            <input id="admin-tutorial-video" type="hidden" />
            <input id="admin-tutorial-sort" type="number" value="0" placeholder="排序值（越小越靠前）" class="h-9 px-3 text-sm border border-[#E5E5E5] rounded-lg" />
            <select id="admin-tutorial-published" class="h-9 px-3 text-sm border border-[#E5E5E5] rounded-lg">
                <option value="1">已发布</option>
                <option value="0">未发布</option>
            </select>
        </div>
        <div class="mt-2 flex flex-wrap items-center gap-2">
            <input id="admin-tutorial-video-file" type="file" accept="video/mp4,video/webm,video/quicktime,video/x-msvideo,video/mpeg,.mp4,.webm,.mov,.avi,.mpeg,.mpg" class="text-sm" />
            <span class="text-xs text-[#666666]">选择文件后将自动上传到 OSS</span>
        </div>
        <div class="mt-1">
            <div id="admin-tutorial-video-upload-status" class="text-xs text-[#666666]"></div>
            <div id="admin-tutorial-video-upload-url" class="text-xs text-[#3B82F6] line-clamp-1"></div>
        </div>
        <textarea id="admin-tutorial-desc" placeholder="描述（可选）" class="mt-2 w-full min-h-[88px] px-3 py-2 text-sm border border-[#E5E5E5] rounded-lg"></textarea>
        <div class="mt-2 flex gap-2">
            <button type="button" id="admin-tutorial-save-btn" class="h-9 px-4 text-sm bg-[#3B82F6] hover:bg-[#2563EB] text-white rounded-lg">保存</button>
            <button type="button" id="admin-tutorial-clear-btn" class="h-9 px-4 text-sm bg-[#F5F5F5] hover:bg-[#EDEDED] rounded-lg">清空</button>
        </div>
    </div>

    <div class="mt-4 flex gap-2">
        <input id="admin-tutorials-q" type="text" placeholder="搜索标题/描述" class="h-9 px-3 text-sm border border-[#E5E5E5] rounded-lg w-[260px]" />
        <select id="admin-tutorials-published-filter" class="h-9 px-3 text-sm border border-[#E5E5E5] rounded-lg">
            <option value="">全部状态</option>
            <option value="1">已发布</option>
            <option value="0">未发布</option>
        </select>
        <button type="button" id="admin-tutorials-search-btn" class="h-9 px-4 text-sm bg-[#111827] hover:bg-black text-white rounded-lg">查询</button>
    </div>

    <div class="mt-4 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
            <tr class="text-left text-[#666666] border-b border-[#EEEEEE]">
                <th class="py-2 pr-4">ID</th>
                <th class="py-2 pr-4">标题</th>
                <th class="py-2 pr-4">状态</th>
                <th class="py-2 pr-4">排序</th>
                <th class="py-2 pr-4">更新时间</th>
                <th class="py-2">操作</th>
            </tr>
            </thead>
            <tbody id="admin-tutorials-tbody"></tbody>
        </table>
    </div>
</div>

<script>
let adminTutorialsCache = [];

function adminTutorialFillForm(t) {
    document.getElementById('admin-tutorial-id').value = t.id || '';
    document.getElementById('admin-tutorial-title').value = t.title || '';
    document.getElementById('admin-tutorial-cover').value = t.coverUrl || '';
    document.getElementById('admin-tutorial-video').value = t.videoUrl || '';
    document.getElementById('admin-tutorial-sort').value = t.sortOrder || 0;
    document.getElementById('admin-tutorial-published').value = t.isPublished ? '1' : '0';
    document.getElementById('admin-tutorial-desc').value = t.description || '';
    const urlEl = document.getElementById('admin-tutorial-video-upload-url');
    const statusEl = document.getElementById('admin-tutorial-video-upload-status');
    if (urlEl) urlEl.textContent = t.videoUrl ? ('当前视频：' + t.videoUrl) : '';
    if (statusEl) statusEl.textContent = t.videoUrl ? '已存在视频，可直接保存或重新选择文件自动替换' : '';
}

function adminTutorialFillByIndex(idx) {
    const item = adminTutorialsCache[idx];
    if (!item) return;
    adminTutorialFillForm(item);
}

function adminTutorialClearForm() {
    ['admin-tutorial-id', 'admin-tutorial-title', 'admin-tutorial-cover', 'admin-tutorial-video', 'admin-tutorial-desc'].forEach((id) => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
    const fileEl = document.getElementById('admin-tutorial-video-file');
    const statusEl = document.getElementById('admin-tutorial-video-upload-status');
    const urlEl = document.getElementById('admin-tutorial-video-upload-url');
    if (fileEl) fileEl.value = '';
    if (statusEl) statusEl.textContent = '';
    if (urlEl) urlEl.textContent = '';
    document.getElementById('admin-tutorial-sort').value = 0;
    document.getElementById('admin-tutorial-published').value = '1';
}

async function adminTutorialLoadList() {
    const q = document.getElementById('admin-tutorials-q')?.value?.trim() || '';
    const published = document.getElementById('admin-tutorials-published-filter')?.value || '';
    const params = new URLSearchParams();
    if (q) params.set('q', q);
    if (published !== '') params.set('published', published);
    params.set('limit', '120');

    const tbody = document.getElementById('admin-tutorials-tbody');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="6" class="py-4 text-[#999999]">加载中...</td></tr>';

    try {
        const res = await fetch('api/admin/tutorials/list.php?' + params.toString());
        const ret = await res.json();
        if (!ret.success) {
            tbody.innerHTML = '<tr><td colspan="6" class="py-4 text-red-500">' + (ret.message || '加载失败') + '</td></tr>';
            return;
        }
        const list = ret.data?.list || [];
        adminTutorialsCache = list;
        if (!list.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="py-4 text-[#999999]">暂无教程</td></tr>';
            return;
        }
        tbody.innerHTML = list.map((t, i) => `
            <tr class="border-b border-[#F4F4F4]">
                <td class="py-2 pr-4">#${t.id}</td>
                <td class="py-2 pr-4">
                    <div class="font-medium text-[#1A1A1A]">${t.title || ''}</div>
                    <div class="text-xs text-[#999999] line-clamp-1">${t.videoUrl || ''}</div>
                </td>
                <td class="py-2 pr-4">
                    <span class="px-2 py-0.5 text-xs rounded ${t.isPublished ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-700'}">${t.isPublished ? '已发布' : '未发布'}</span>
                </td>
                <td class="py-2 pr-4">${t.sortOrder || 0}</td>
                <td class="py-2 pr-4 text-xs text-[#666666]">${(t.updatedAt || t.createdAt || '').replace('T', ' ').slice(0, 16)}</td>
                <td class="py-2">
                    <div class="flex gap-1">
                        <button class="h-7 px-2 text-xs rounded bg-[#F5F5F5] hover:bg-[#EDEDED]" onclick="adminTutorialFillByIndex(${i})">编辑</button>
                        <button class="h-7 px-2 text-xs rounded bg-[#FEE2E2] text-[#B91C1C] hover:bg-[#FECACA]" onclick="adminTutorialDelete(${t.id})">删除</button>
                    </div>
                </td>
            </tr>
        `).join('');
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="6" class="py-4 text-red-500">网络错误</td></tr>';
    }
}

async function adminTutorialSave() {
    const id = Number(document.getElementById('admin-tutorial-id')?.value || 0);
    const title = (document.getElementById('admin-tutorial-title')?.value || '').trim();
    const coverUrl = (document.getElementById('admin-tutorial-cover')?.value || '').trim();
    const videoUrl = (document.getElementById('admin-tutorial-video')?.value || '').trim();
    const sortOrder = Number(document.getElementById('admin-tutorial-sort')?.value || 0);
    const isPublished = (document.getElementById('admin-tutorial-published')?.value || '1') === '1';
    const description = (document.getElementById('admin-tutorial-desc')?.value || '').trim();
    if (!title) {
        alert('标题必填');
        return;
    }
    let finalVideoUrl = videoUrl;
    if (!finalVideoUrl) {
        finalVideoUrl = await adminTutorialUploadVideo(true);
    }
    if (!finalVideoUrl) {
        alert('请先选择视频文件并等待自动上传成功');
        return;
    }
    const payload = {id, title, coverUrl, videoUrl: finalVideoUrl, sortOrder, isPublished, description};
    const res = await fetch('api/admin/tutorials/save.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(payload),
    });
    const ret = await res.json();
    if (!ret.success) {
        alert(ret.message || '保存失败');
        return;
    }
    alert('保存成功');
    adminTutorialClearForm();
    adminTutorialLoadList();
}

async function adminTutorialUploadVideo(silent = false) {
    const fileInput = document.getElementById('admin-tutorial-video-file');
    const statusEl = document.getElementById('admin-tutorial-video-upload-status');
    const videoUrlInput = document.getElementById('admin-tutorial-video');
    const urlEl = document.getElementById('admin-tutorial-video-upload-url');
    const file = fileInput?.files?.[0];
    if (!file) {
        if (!silent) alert('请先选择视频文件');
        return '';
    }
    const maxSize = 200 * 1024 * 1024; // 与后端保持一致
    if (file.size > maxSize) {
        const msg = '视频文件不能超过 200MB';
        if (statusEl) statusEl.textContent = msg;
        if (!silent) alert(msg);
        return '';
    }

    const fd = new FormData();
    fd.append('file', file);
    fd.append('prefix', 'assets/videos/tutorials');

    if (statusEl) statusEl.textContent = '上传中，请稍候...';
    if (urlEl) urlEl.textContent = '';

    try {
        const res = await fetch('api/upload/video.php', { method: 'POST', body: fd });
        const raw = await res.text();
        let ret = null;
        try {
            ret = JSON.parse(raw);
        } catch (e) {
            ret = null;
        }
        if (!ret) {
            // PHP 超限时常返回 400 非 JSON
            const isTooLarge = raw.includes('Content-Length') || raw.includes('exceeds the limit') || res.status === 400;
            const msg = isTooLarge
                ? '上传失败：请求体超过服务器限制。请重启服务后再试（已在 start.php 调整为 256MB）。'
                : ('上传失败：服务器返回异常（HTTP ' + res.status + '）');
            if (statusEl) statusEl.textContent = msg;
            return '';
        }
        if (!ret.success || !ret.data?.url) {
            if (statusEl) statusEl.textContent = ret.message || '上传失败';
            return '';
        }
        if (videoUrlInput) videoUrlInput.value = ret.data.url;
        if (statusEl) statusEl.textContent = '上传成功，已自动填入视频URL';
        if (urlEl) urlEl.textContent = ret.data.url;
        if (fileInput) fileInput.value = '';
        return ret.data.url;
    } catch (e) {
        if (statusEl) statusEl.textContent = '网络错误，上传失败，请检查服务是否在运行';
        return '';
    }
}

async function adminTutorialDelete(id) {
    if (!confirm('确认删除该教程？')) return;
    const res = await fetch('api/admin/tutorials/delete.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({id}),
    });
    const ret = await res.json();
    if (!ret.success) {
        alert(ret.message || '删除失败');
        return;
    }
    adminTutorialLoadList();
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('admin-tutorial-save-btn')?.addEventListener('click', adminTutorialSave);
    document.getElementById('admin-tutorial-clear-btn')?.addEventListener('click', adminTutorialClearForm);
    document.getElementById('admin-tutorial-video-file')?.addEventListener('change', function () {
        const hasFile = !!this.files?.[0];
        const statusEl = document.getElementById('admin-tutorial-video-upload-status');
        if (!hasFile) return;
        if (statusEl) statusEl.textContent = '检测到新视频文件，正在自动上传...';
        adminTutorialUploadVideo(true);
    });
    document.getElementById('admin-tutorials-search-btn')?.addEventListener('click', adminTutorialLoadList);
    adminTutorialLoadList();
});
</script>
