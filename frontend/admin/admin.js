const API = '../api/index.php';
let csrfToken = '';
let currentRole = '';
const escapeHtml = (value = '') => String(value).replace(/[&<>'"]/g, char => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'
}[char]));

function managedImageVariant(url, variant) {
    return String(url || '').replace(
        /(\/uploads\/[a-f0-9]{32})\/(?:thumb|card|content|hero|social)\.(?:webp|jpg)$/i,
        `$1/${variant}.${variant === 'social' ? 'jpg' : 'webp'}`
    );
}

function serializeEditorContent(editor) {
    if (!editor) return '';
    const clone = editor.cloneNode(true);
    clone.querySelectorAll('.content-photo-remove').forEach(button => button.remove());
    return clone.innerHTML;
}

function addGalleryRemoveButtons(editor) {
    editor?.querySelectorAll('.content-photo-grid figure').forEach(figure => {
        if (figure.querySelector('.content-photo-remove')) return;
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'content-photo-remove';
        button.contentEditable = 'false';
        button.setAttribute('aria-label', 'Hapus foto');
        button.textContent = '×';
        figure.appendChild(button);
    });
}

function parseGalleryImages(value, limit = 3) {
    if (Array.isArray(value)) return value.filter(Boolean).slice(0, limit);
    try {
        const parsed = JSON.parse(value || '[]');
        return Array.isArray(parsed) ? parsed.filter(Boolean).slice(0, limit) : [];
    } catch (error) {
        return [];
    }
}

function slugify(value) {
    return String(value || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '')
        .slice(0, 180)
        .replace(/-+$/g, '');
}

function setupAutomaticSlug(prefix) {
    const title = document.getElementById(`${prefix}-title`);
    const slug = document.getElementById(`${prefix}-slug`);
    const id = document.getElementById(`${prefix}-id`);
    if (!title || !slug) return;
    title.addEventListener('input', () => {
        if (!id?.value) slug.value = slugify(title.value);
    });
}

function setSliderImages(prefix, previewId, images) {
    const normalized = [...new Set(images.filter(Boolean))].slice(0, 3);
    document.getElementById(`${prefix}-gallery-images`).value = JSON.stringify(normalized);
    document.getElementById(`${prefix}-image-url`).value = normalized[0] || '';
    const preview = document.getElementById(previewId);
    if (!preview) return;
    preview.innerHTML = normalized.length ? `<div class="admin-slider-preview">${normalized.map((url, index) => `<div class="admin-slider-preview__item"><img src="${escapeHtml(url)}" alt="Foto slider ${index + 1}"><span>Foto ${index + 1}${index === 0 ? ' · Utama' : ''}</span><button type="button" data-remove-slider-image data-prefix="${escapeHtml(prefix)}" data-preview-id="${escapeHtml(previewId)}" data-index="${index}" aria-label="Hapus foto ${index + 1}">×</button></div>`).join('')}</div>` : '';
}

function setHeroImages(images) {
    const normalized = [...new Set(images.filter(Boolean))].slice(0, 10);
    document.getElementById('post-hero-images').value = JSON.stringify(normalized);
    document.getElementById('post-hero-image-url').value = normalized[0] || '';
    const preview = document.getElementById('post-hero-image-preview');
    if (!preview) return;
    preview.innerHTML = normalized.length ? `<div class="admin-slider-preview">${normalized.map((url, index) => `<div class="admin-slider-preview__item"><img src="${escapeHtml(url)}" alt="Background header ${index + 1}"><span>Background ${index + 1}${index === 0 ? ' · Utama' : ''}</span><button type="button" data-remove-hero-image data-index="${index}" aria-label="Hapus background ${index + 1}">×</button></div>`).join('')}</div>` : '';
}

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
    const content = serializeEditorContent(editor);
    const hidden = document.getElementById(`${prefix}-content`);
    if (hidden) hidden.value = content;
    const title = document.getElementById(`${prefix}-title`)?.value || 'Judul';
    const image = document.getElementById(`${prefix}-image-url`)?.value || '';
    const pTitle = document.getElementById('p-title');
    const pSub = document.getElementById('p-sub');
    const pImage = document.getElementById('p-img');
    const pBody = document.getElementById('p-body');
    const pHero = document.getElementById('p-hero');
    if (pTitle) pTitle.textContent = title;
    if (pSub) pSub.textContent = article ? `Diterbitkan pada ${new Date().toLocaleDateString('id-ID')}` : (document.getElementById('prog-hero-subtitle')?.value || 'Subjudul program');
    if (pImage) { pImage.src = managedImageVariant(image, 'thumb'); pImage.style.display = image ? 'block' : 'none'; }
    if (pHero) {
        const heroImage = article ? (parseGalleryImages(document.getElementById('post-hero-images')?.value, 10)[0] || '') : image;
        pHero.style.backgroundImage = heroImage ? `linear-gradient(rgba(10, 38, 71, .76), rgba(10, 38, 71, .76)), url("${heroImage.replace(/["\\]/g, '\\$&')}")` : '';
    }
    if (pBody) pBody.innerHTML = content || 'Mulai mengetik untuk melihat hasil...';
}

document.addEventListener('input', event => {
    if (event.target.closest('#post-form, #program-form')) updatePreview();
});

function setupDropZone(zoneId, inputId, urlId, previewId, prefix) {
    const zone = document.getElementById(zoneId);
    const input = document.getElementById(inputId);
    if (!zone || !input) return;
    zone.addEventListener('click', event => {
        if (!event.target.closest('[data-remove-slider-image]')) input.click();
    });
    input.addEventListener('change', () => uploadMainAndGalleryImages(Array.from(input.files || []), input, urlId, previewId, prefix));
    zone.addEventListener('dragover', event => event.preventDefault());
    zone.addEventListener('drop', event => {
        event.preventDefault();
        uploadMainAndGalleryImages(Array.from(event.dataTransfer?.files || []), input, urlId, previewId, prefix);
    });
}

function setupHeroImageDropZone(zoneId, inputId) {
    const zone = document.getElementById(zoneId);
    const input = document.getElementById(inputId);
    if (!zone || !input) return;
    const upload = files => uploadHeroImages(files, input);
    zone.addEventListener('click', event => {
        if (!event.target.closest('[data-remove-hero-image]')) input.click();
    });
    input.addEventListener('change', () => upload(Array.from(input.files || [])));
    zone.addEventListener('dragover', event => event.preventDefault());
    zone.addEventListener('drop', event => {
        event.preventDefault();
        upload(Array.from(event.dataTransfer?.files || []));
    });
}

function setSeoImage(prefix, url) {
    const input = document.getElementById(`${prefix}-social-image`);
    const preview = document.getElementById(`${prefix}-social-preview`);
    if (input) input.value = url || '';
    if (preview) {
        preview.innerHTML = url
            ? `<div class="admin-slider-preview__item"><img src="${escapeHtml(managedImageVariant(url, 'social'))}" alt="Preview gambar sosial"><button type="button" data-clear-seo-image="${escapeHtml(prefix)}" aria-label="Hapus gambar sosial">×</button></div>`
            : '';
    }
}

function setupSeoImageUpload(prefix) {
    const zone = document.getElementById(`${prefix}-social-drop-zone`);
    const input = document.getElementById(`${prefix}-social-file`);
    if (!zone || !input) return;
    const upload = async file => {
        if (!file) return;
        try {
            setSeoImage(prefix, await uploadImageFileWithRetry(file, 3, 'social'));
        } catch (error) {
            alert(error.message);
        } finally {
            input.value = '';
        }
    };
    zone.addEventListener('click', event => {
        if (!event.target.closest('[data-clear-seo-image]')) input.click();
    });
    input.addEventListener('change', () => upload(input.files?.[0]));
    zone.addEventListener('dragover', event => event.preventDefault());
    zone.addEventListener('drop', event => {
        event.preventDefault();
        upload(event.dataTransfer?.files?.[0]);
    });
}

async function uploadHeroImages(files, input) {
    if (!files.length) return;
    const existing = parseGalleryImages(document.getElementById('post-hero-images').value, 10);
    if (existing.length + files.length > 10) {
        alert(`Slider background maksimal 10 foto. Saat ini sudah ada ${existing.length} foto.`);
        input.value = '';
        return;
    }
    const uploaded = [];
    for (const file of files) {
        try {
            uploaded.push(await uploadImageFileWithRetry(file, 3, 'hero'));
        } catch (error) {
            alert(`${file.name} gagal diunggah: ${error.message}`);
        }
    }
    if (uploaded.length) setHeroImages([...existing, ...uploaded]);
    updatePreview();
    input.value = '';
}

async function uploadMainAndGalleryImages(files, input, urlId, previewId, prefix) {
    if (!files.length) return;
    const existing = parseGalleryImages(document.getElementById(`${prefix}-gallery-images`).value);
    if (existing.length + files.length > 3) {
        alert(`Slider utama maksimal 3 foto. Saat ini sudah ada ${existing.length} foto.`);
        input.value = '';
        return;
    }
    const uploaded = [];
    for (let index = 0; index < files.length; index += 1) {
        try {
            uploaded.push(await uploadImageFileWithRetry(files[index]));
        } catch (error) {
            alert(`${files[index].name} gagal diunggah: ${error.message}`);
        }
    }
    if (uploaded.length) {
        setSliderImages(prefix, previewId, [...existing, ...uploaded]);
        updatePreview();
    }
    input.value = '';
}

async function uploadImage(file, urlId, previewId) {
    if (!file) return;
    try {
        const url = await uploadImageFile(file);
        const prefix = urlId.startsWith('prog-') ? 'prog' : 'post';
        setSliderImages(prefix, previewId, [url]);
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

async function uploadImageFile(file, variant = 'card') {
    validateImageFile(file);
    const form = new FormData();
    form.append('image', file);
    const result = await api('upload', { method: 'POST', body: form });
    return result.variants?.[variant] || result.url;
}

const wait = milliseconds => new Promise(resolve => setTimeout(resolve, milliseconds));

async function uploadImageFileWithRetry(file, attempts = 3, variant = 'card') {
    let lastError = new Error('Upload gagal.');
    for (let attempt = 1; attempt <= attempts; attempt += 1) {
        try {
            return await uploadImageFile(file, variant);
        } catch (error) {
            lastError = error;
            if (attempt < attempts) await wait(attempt * 700);
        }
    }
    throw lastError;
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
    if (files.length > 20) {
        alert('Maksimal 20 foto dalam satu kali upload.');
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
            uploaded.push(await uploadImageFileWithRetry(files[index], 3, 'content'));
        } catch (error) {
            failed.push(`${files[index].name}: ${error.message || 'Upload gagal'}`);
        }
    }

    if (uploaded.length) {
        const title = document.getElementById(`${prefix}-title`)?.value.trim() || 'Dokumentasi Dompet Dana Umat';
        const figures = uploaded.map((url, index) => `<figure><img src="${escapeHtml(url)}" alt="${escapeHtml(title)} - foto ${index + 1}" loading="lazy"></figure>`).join('');
        editor.insertAdjacentHTML('beforeend', `<div class="content-photo-grid">${figures}</div><p><br></p>`);
        addGalleryRemoveButtons(editor);
        updatePreview();
    }

    const saveAction = prefix === 'prog' ? 'Simpan Program' : 'Publikasikan Artikel';
    if (status) status.textContent = uploaded.length ? `${uploaded.length} foto berhasil ditambahkan. Klik ${saveAction} agar foto tersimpan.` : '';
    if (failed.length) alert(`${failed.length} foto gagal diunggah:\n\n${failed.join('\n')}`);
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
    const title = document.getElementById(`${prefix}-title`).value.trim();
    const payload = {
        id: document.getElementById(`${prefix}-id`).value,
        title,
        slug: document.getElementById(`${prefix}-slug`).value.trim().toLowerCase() || slugify(title),
        image: document.getElementById(`${prefix}-image-url`).value,
        gallery_images: parseGalleryImages(document.getElementById(`${prefix}-gallery-images`).value),
        excerpt: document.getElementById(`${prefix}-excerpt`).value,
        content: document.getElementById(`${prefix}-content`).value,
        whatsapp_number: document.getElementById(`${prefix}-wa`).value,
        whatsapp_message: document.getElementById(`${prefix}-wa-message`).value,
        seo_title: document.getElementById(`${prefix}-seo-title`).value,
        seo_description: document.getElementById(`${prefix}-seo-description`).value,
        social_image: document.getElementById(`${prefix}-social-image`).value,
        image_alt: document.getElementById(`${prefix}-image-alt`).value
    };
    if (resource === 'programs') {
        payload.hero_title = document.getElementById('prog-hero-title').value;
        payload.hero_subtitle = document.getElementById('prog-hero-subtitle').value;
    } else {
        payload.hero_image = document.getElementById('post-hero-image-url').value;
        payload.hero_images = parseGalleryImages(document.getElementById('post-hero-images').value, 10);
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
    const removeSliderImage = event.target.closest('[data-remove-slider-image]');
    const removeHeroImage = event.target.closest('[data-remove-hero-image]');
    const removeContentPhoto = event.target.closest('.content-photo-remove');
    const clearSeoImage = event.target.closest('[data-clear-seo-image]');
    const edit = event.target.closest('[data-edit]');
    const remove = event.target.closest('[data-delete]');
    if (removeSliderImage) {
        const prefix = removeSliderImage.dataset.prefix;
        const previewId = removeSliderImage.dataset.previewId;
        const images = parseGalleryImages(document.getElementById(`${prefix}-gallery-images`).value);
        images.splice(Number(removeSliderImage.dataset.index), 1);
        setSliderImages(prefix, previewId, images);
        updatePreview();
    }
    if (removeHeroImage) {
        const images = parseGalleryImages(document.getElementById('post-hero-images').value, 10);
        images.splice(Number(removeHeroImage.dataset.index), 1);
        setHeroImages(images);
        updatePreview();
    }
    if (removeContentPhoto) {
        const editor = removeContentPhoto.closest('.visual-editor');
        const grid = removeContentPhoto.closest('.content-photo-grid');
        removeContentPhoto.closest('figure')?.remove();
        if (grid && !grid.querySelector('figure')) grid.remove();
        updatePreview();
        const prefix = editor?.id.startsWith('prog-') ? 'prog' : 'post';
        const status = document.getElementById(`${prefix}-content-upload-status`);
        if (status) status.textContent = 'Foto dihapus dari rancangan. Klik simpan agar perubahan diterapkan.';
    }
    if (clearSeoImage) {
        setSeoImage(clearSeoImage.dataset.clearSeoImage, '');
    }
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
        ['id', 'title', 'slug', 'excerpt', 'seo-title', 'seo-description', 'image-alt'].forEach(field => {
            const databaseField = field.replaceAll('-', '_');
            const el = document.getElementById(`${prefix}-${field}`);
            if (el) el.value = data[databaseField] || '';
        });
        setSeoImage(prefix, data.social_image || '');
        const previewId = resource === 'posts' ? 'image-preview' : 'prog-image-preview';
        const galleryImages = parseGalleryImages(data.gallery_images);
        if (!galleryImages.length && data.image) galleryImages.push(data.image);
        setSliderImages(prefix, previewId, galleryImages);
        const editor = document.getElementById(`${prefix}-content-editor`);
        editor.innerHTML = data.content || '';
        addGalleryRemoveButtons(editor);
        document.getElementById(`${prefix}-content`).value = data.content || '';
        document.getElementById(`${prefix}-wa`).value = data.whatsapp_number || '';
        document.getElementById(`${prefix}-wa-message`).value = data.whatsapp_message || '';
        if (resource === 'programs') {
            document.getElementById('prog-hero-title').value = data.hero_title || '';
            document.getElementById('prog-hero-subtitle').value = data.hero_subtitle || '';
        } else {
            const heroImages = parseGalleryImages(data.hero_images, 10);
            if (!heroImages.length && data.hero_image) heroImages.push(data.hero_image);
            setHeroImages(heroImages);
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
    setupAutomaticSlug('post');
    setupAutomaticSlug('prog');
    setupDropZone('article-drop-zone', 'post-image-file', 'post-image-url', 'image-preview', 'post');
    setupDropZone('prog-drop-zone', 'prog-image-file', 'prog-image-url', 'prog-image-preview', 'prog');
    setupHeroImageDropZone('post-hero-drop-zone', 'post-hero-image-file');
    setupSeoImageUpload('post');
    setupSeoImageUpload('prog');
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
