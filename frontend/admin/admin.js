const API = '../api/index.php';
let csrfToken = '';
let currentRole = '';
const escapeHtml = (value = '') => String(value).replace(/[&<>'"]/g, char => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'
}[char]));

async function api(resource, options = {}) {
    const headers = { ...(options.headers || {}) };
    if (!(options.body instanceof FormData)) headers['Content-Type'] = 'application/json';
    if (csrfToken) headers['X-CSRF-Token'] = csrfToken;
    const response = await fetch(`${API}?resource=${resource}`, { credentials: 'same-origin', ...options, headers });
    const result = await response.json().catch(() => ({ ok: false, message: 'Respons server tidak valid.' }));
    if (!response.ok) throw new Error(result.message || 'Permintaan gagal.');
    return result;
}

window.switchTab = tab => {
    document.getElementById('content-dashboard')?.classList.toggle('hidden', tab !== 'dashboard');
    document.getElementById('content-articles')?.classList.toggle('hidden', tab !== 'articles');
    document.getElementById('content-programs-admin')?.classList.toggle('hidden', tab !== 'programs-admin');
    document.getElementById('content-admins')?.classList.toggle('hidden', tab !== 'admins');
    document.querySelector('.preview-group')?.classList.toggle('hidden', tab === 'dashboard' || tab === 'admins');
    document.getElementById('tab-dashboard')?.classList.toggle('active', tab === 'dashboard');
    document.getElementById('tab-articles')?.classList.toggle('active', tab === 'articles');
    document.getElementById('tab-programs-admin')?.classList.toggle('active', tab === 'programs-admin');
    document.getElementById('tab-admins')?.classList.toggle('active', tab === 'admins');
    if (tab === 'dashboard') fetchStats();
    updatePreview();
};

window.formatDoc = (command, value = null) => {
    document.execCommand(command, false, value);
    updatePreview();
};

function updatePreview() {
    const article = !document.getElementById('content-articles')?.classList.contains('hidden');
    const program = !document.getElementById('content-programs-admin')?.classList.contains('hidden');
    if (!article && !program) return;
    const prefix = article ? 'post' : 'prog';
    const editor = document.getElementById(`${prefix}-content-editor`);
    const content = editor?.innerHTML || '';
    const hidden = document.getElementById(`${prefix}-content`);
    if (hidden) hidden.value = content;
    const title = document.getElementById(`${prefix}-title`)?.value || 'Judul';
    const image = document.getElementById(`${prefix}-image-url`)?.value || '';
    const pTitle = document.getElementById('p-title');
    const pSub = document.getElementById('p-sub');
    const pImage = document.getElementById('p-img');
    const pBody = document.getElementById('p-body');
    if (pTitle) pTitle.textContent = title;
    if (pSub) pSub.textContent = article ? `Diterbitkan pada ${new Date().toLocaleDateString('id-ID')}` : (document.getElementById('prog-hero-subtitle')?.value || 'Subjudul program');
    if (pImage) { pImage.src = image; pImage.style.display = image ? 'block' : 'none'; }
    if (pBody) pBody.innerHTML = content || 'Mulai mengetik untuk melihat hasil...';
}

document.addEventListener('input', event => {
    if (event.target.closest('#post-form, #program-form')) updatePreview();
});

function setupDropZone(zoneId, inputId, urlId, previewId) {
    const zone = document.getElementById(zoneId);
    const input = document.getElementById(inputId);
    if (!zone || !input) return;
    zone.addEventListener('click', () => input.click());
    input.addEventListener('change', () => uploadImage(input.files?.[0], urlId, previewId));
    zone.addEventListener('dragover', event => event.preventDefault());
    zone.addEventListener('drop', event => { event.preventDefault(); uploadImage(event.dataTransfer?.files?.[0], urlId, previewId); });
}

async function uploadImage(file, urlId, previewId) {
    if (!file) return;
    try {
        const url = await uploadImageFile(file);
        document.getElementById(urlId).value = url;
        const preview = document.getElementById(previewId);
        if (preview) preview.innerHTML = `<img src="${escapeHtml(url)}" alt="Preview" style="max-height:100px;border-radius:8px">`;
        updatePreview();
    } catch (error) {
        alert(error.message);
    }
}

function validateImageFile(file) {
    if (!file || !['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024) {
        throw new Error('Gunakan gambar JPG, PNG, atau WebP maksimal 5 MB per foto.');
    }
}

async function uploadImageFile(file) {
    validateImageFile(file);
    const form = new FormData();
    form.append('image', file);
    const result = await api('upload', { method: 'POST', body: form });
    return result.url;
}

function setupContentPhotoUpload(prefix) {
    const button = document.getElementById(`${prefix}-add-content-photos`);
    const input = document.getElementById(`${prefix}-content-image-files`);
    if (!button || !input) return;
    button.addEventListener('click', () => input.click());
    input.addEventListener('change', () => uploadContentPhotos(prefix, Array.from(input.files || [])));
}

async function uploadContentPhotos(prefix, files) {
    const input = document.getElementById(`${prefix}-content-image-files`);
    const button = document.getElementById(`${prefix}-add-content-photos`);
    const status = document.getElementById(`${prefix}-content-upload-status`);
    const editor = document.getElementById(`${prefix}-content-editor`);
    if (!editor || files.length === 0) return;
    if (files.length > 10) {
        alert('Maksimal 10 foto dalam satu kali upload.');
        if (input) input.value = '';
        return;
    }

    try {
        files.forEach(validateImageFile);
    } catch (error) {
        alert(error.message);
        if (input) input.value = '';
        return;
    }

    if (button) button.disabled = true;
    const uploaded = [];
    const failed = [];
    for (let index = 0; index < files.length; index += 1) {
        if (status) status.textContent = `Mengunggah ${index + 1}/${files.length}...`;
        try {
            uploaded.push(await uploadImageFile(files[index]));
        } catch (error) {
            failed.push(files[index].name);
        }
    }

    if (uploaded.length) {
        const title = document.getElementById(`${prefix}-title`)?.value.trim() || 'Dokumentasi Dompet Dana Umat';
        const figures = uploaded.map((url, index) => `<figure><img src="${escapeHtml(url)}" alt="${escapeHtml(title)} - foto ${index + 1}" loading="lazy"></figure>`).join('');
        editor.insertAdjacentHTML('beforeend', `<div class="content-photo-grid">${figures}</div><p><br></p>`);
        updatePreview();
    }

    if (status) status.textContent = uploaded.length ? `${uploaded.length} foto berhasil ditambahkan.` : '';
    if (failed.length) alert(`${failed.length} foto gagal diunggah. Silakan coba kembali.`);
    if (button) button.disabled = false;
    if (input) input.value = '';
}

document.getElementById('login-form')?.addEventListener('submit', async event => {
    event.preventDefault();
    const button = event.currentTarget.querySelector('button[type="submit"]');
    button.disabled = true;
    try {
        const result = await api('login', { method: 'POST', body: JSON.stringify({
            email: document.getElementById('admin-email').value,
            password: document.getElementById('admin-password').value
        }) });
        csrfToken = result.csrf;
        currentRole = result.role || '';
        document.getElementById('tab-admins')?.classList.toggle('hidden', currentRole !== 'super_admin');
        await showDashboard();
    } catch (error) {
        alert(error.message);
    } finally {
        button.disabled = false;
    }
});

document.getElementById('btn-logout')?.addEventListener('click', async () => {
    try { await api('logout', { method: 'POST', body: '{}' }); } finally { location.reload(); }
});

document.getElementById('post-form')?.addEventListener('submit', event => saveForm(event, 'posts'));
document.getElementById('program-form')?.addEventListener('submit', event => saveForm(event, 'programs'));
document.getElementById('admin-create-form')?.addEventListener('submit', createAdmin);

async function saveForm(event, resource) {
    event.preventDefault();
    updatePreview();
    const prefix = resource === 'posts' ? 'post' : 'prog';
    const payload = {
        id: document.getElementById(`${prefix}-id`).value,
        title: document.getElementById(`${prefix}-title`).value.trim(),
        slug: document.getElementById(`${prefix}-slug`).value.trim().toLowerCase(),
        image: document.getElementById(`${prefix}-image-url`).value,
        excerpt: document.getElementById(`${prefix}-excerpt`).value,
        content: document.getElementById(`${prefix}-content`).value,
        whatsapp_number: document.getElementById(`${prefix}-wa`).value,
        whatsapp_message: document.getElementById(`${prefix}-wa-message`).value
    };
    if (resource === 'programs') {
        payload.hero_title = document.getElementById('prog-hero-title').value;
        payload.hero_subtitle = document.getElementById('prog-hero-subtitle').value;
    }
    try {
        await api(resource, { method: 'POST', body: JSON.stringify(payload) });
        alert('Data berhasil disimpan.');
        location.reload();
    } catch (error) {
        alert(error.message);
    }
}

async function fetchStats() {
    try {
        const result = await api('stats');
        document.getElementById('count-visits').textContent = result.data.visit || 0;
        document.getElementById('count-wa').textContent = result.data.wa_click || 0;
    } catch (error) { console.error(error); }
}

async function showDashboard() {
    document.getElementById('login-section')?.classList.add('hidden');
    document.getElementById('dashboard-section')?.classList.remove('hidden');
    document.querySelector('.preview-group')?.classList.add('hidden');
    await Promise.all([fetchStats(), loadLists()]);
}

async function loadLists() {
    try {
        const [posts, programs] = await Promise.all([api('posts'), api('programs')]);
        renderList('admin-post-list', posts.data || [], 'posts');
        renderList('admin-program-list', programs.data || [], 'programs');
        if (currentRole === 'super_admin') await loadAdminAccounts();
    } catch (error) { console.error(error); }
}

async function createAdmin(event) {
    event.preventDefault();
    const button = event.currentTarget.querySelector('button[type="submit"]');
    button.disabled = true;
    try {
        await api('admins', { method: 'POST', body: JSON.stringify({
            display_name: document.getElementById('new-admin-name').value,
            email: document.getElementById('new-admin-email').value,
            password: document.getElementById('new-admin-password').value,
            role: document.getElementById('new-admin-role').value
        }) });
        event.currentTarget.reset();
        await loadAdminAccounts();
        alert('Admin berhasil ditambahkan atau diperbarui.');
    } catch (error) {
        alert(error.message);
    } finally {
        button.disabled = false;
    }
}

async function loadAdminAccounts() {
    const result = await api('admins');
    const container = document.getElementById('admin-account-list');
    if (!container) return;
    container.innerHTML = (result.data || []).map(admin => `<div class="post-list-item">
        <span><strong>${escapeHtml(admin.display_name || admin.email)}</strong><br><small>${escapeHtml(admin.email)} · ${escapeHtml(admin.role)} · ${Number(admin.is_active) ? 'Aktif' : 'Nonaktif'}</small></span>
        ${Number(admin.is_active) ? `<button type="button" class="btn-delete" data-disable-admin="${Number(admin.id)}">Nonaktifkan</button>` : ''}
    </div>`).join('');
}

function renderList(containerId, items, resource) {
    const container = document.getElementById(containerId);
    if (!container) return;
    container.innerHTML = items.map(item => `<div class="post-list-item"><span><strong>${escapeHtml(item.title)}</strong></span><div>
        <button type="button" class="btn-secondary" data-edit="${resource}" data-id="${Number(item.id)}" style="padding:5px 10px;font-size:.8rem">Edit</button>
        <button type="button" class="btn-delete" data-delete="${resource}" data-id="${Number(item.id)}">Hapus</button>
    </div></div>`).join('');
}

document.addEventListener('click', event => {
    const edit = event.target.closest('[data-edit]');
    const remove = event.target.closest('[data-delete]');
    if (edit) editItem(edit.dataset.edit, edit.dataset.id);
    if (remove) deleteItem(remove.dataset.delete, remove.dataset.id);
    const disableAdmin = event.target.closest('[data-disable-admin]');
    if (disableAdmin) deactivateAdminAccount(disableAdmin.dataset.disableAdmin);
});

async function deactivateAdminAccount(id) {
    if (!confirm('Nonaktifkan akun admin ini?')) return;
    try {
        await api(`admins&id=${encodeURIComponent(id)}`, { method: 'DELETE' });
        await loadAdminAccounts();
    } catch (error) { alert(error.message); }
}

async function editItem(resource, id) {
    try {
        const result = await api(`${resource}&id=${encodeURIComponent(id)}`);
        const data = result.data;
        const prefix = resource === 'posts' ? 'post' : 'prog';
        window.switchTab(resource === 'posts' ? 'articles' : 'programs-admin');
        ['id', 'title', 'slug', 'excerpt'].forEach(field => { const el = document.getElementById(`${prefix}-${field}`); if (el) el.value = data[field] || ''; });
        document.getElementById(`${prefix}-image-url`).value = data.image || '';
        document.getElementById(`${prefix}-content-editor`).innerHTML = data.content || '';
        document.getElementById(`${prefix}-content`).value = data.content || '';
        document.getElementById(`${prefix}-wa`).value = data.whatsapp_number || '';
        document.getElementById(`${prefix}-wa-message`).value = data.whatsapp_message || '';
        if (resource === 'programs') {
            document.getElementById('prog-hero-title').value = data.hero_title || '';
            document.getElementById('prog-hero-subtitle').value = data.hero_subtitle || '';
        }
        updatePreview();
        scrollTo({ top: 0, behavior: 'smooth' });
    } catch (error) { alert(error.message); }
}

async function deleteItem(resource, id) {
    if (!confirm('Hapus data ini secara permanen?')) return;
    try {
        await api(`${resource}&id=${encodeURIComponent(id)}`, { method: 'DELETE' });
        await loadLists();
    } catch (error) { alert(error.message); }
}

async function init() {
    setupDropZone('article-drop-zone', 'post-image-file', 'post-image-url', 'image-preview');
    setupDropZone('prog-drop-zone', 'prog-image-file', 'prog-image-url', 'prog-image-preview');
    setupContentPhotoUpload('post');
    setupContentPhotoUpload('prog');
    try {
        const session = await api('session');
        if (session.authenticated) {
            csrfToken = session.csrf;
            currentRole = session.role || '';
            document.getElementById('tab-admins')?.classList.toggle('hidden', currentRole !== 'super_admin');
            await showDashboard();
        }
    } catch (error) { console.error(error); }
}

init();
