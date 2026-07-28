const API = '../api/index.php';
let csrfToken = '';
let currentRole = '';
const contentListState = {
    posts: { prefix: 'post', page: 1, perPage: 10, searchTimer: null, requestId: 0 },
    programs: { prefix: 'program', page: 1, perPage: 10, searchTimer: null, requestId: 0 }
};
const escapeHtml = (value = '') => String(value).replace(/[&<>'"]/g, char => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'
}[char]));
const institutionFields = [
    'organization_name', 'parent_organization', 'legal_entity_name', 'deed_number',
    'ministry_number', 'tax_number', 'official_address', 'official_phone', 'official_email',
    'management_structure', 'donation_accounts', 'collection_reports',
    'beneficiary_documentation', 'official_disclaimer', 'privacy_contact'
];

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

function setHeroVideoPreview(prefix, url) {
    const input = document.getElementById(`${prefix}-hero-video-url`);
    const preview = document.getElementById(`${prefix}-video-preview`);
    if (input) input.value = url || '';
    if (!preview) return;
    preview.innerHTML = url
        ? `<video src="${escapeHtml(url)}" controls muted playsinline preload="metadata"></video><button type="button" data-clear-hero-video="${escapeHtml(prefix)}" aria-label="Hapus video">×</button>`
        : '';
}

function updateHeroMediaFields(prefix) {
    const type = document.getElementById(`${prefix}-hero-media-type`)?.value || 'images';
    document.getElementById(`${prefix}-local-video-fields`)?.classList.toggle('hidden', type !== 'video');
    document.getElementById(`${prefix}-external-video-fields`)?.classList.toggle('hidden', !['youtube', 'drive'].includes(type));
    const link = document.getElementById(`${prefix}-hero-video-link`);
    const help = document.getElementById(`${prefix}-video-link-help`);
    if (link) {
        link.placeholder = type === 'drive'
            ? 'https://drive.google.com/file/d/.../view'
            : 'https://www.youtube.com/watch?v=...';
    }
    if (help) {
        help.textContent = type === 'drive'
            ? 'Atur akses file Drive menjadi “Siapa saja yang memiliki link · Viewer”.'
            : 'Video YouTube akan diputar tanpa suara, otomatis, dan berulang.';
    }
    updatePreview();
}

function setupHeroMedia(prefix) {
    const type = document.getElementById(`${prefix}-hero-media-type`);
    const zone = document.getElementById(`${prefix}-video-drop-zone`);
    const input = document.getElementById(`${prefix}-video-file`);
    type?.addEventListener('change', () => updateHeroMediaFields(prefix));
    zone?.addEventListener('click', event => {
        if (!event.target.closest('[data-clear-hero-video]')) input?.click();
    });
    zone?.addEventListener('dragover', event => {
        event.preventDefault();
        zone.classList.add('dragover');
    });
    zone?.addEventListener('dragleave', () => zone.classList.remove('dragover'));
    zone?.addEventListener('drop', event => {
        event.preventDefault();
        zone.classList.remove('dragover');
        const file = event.dataTransfer?.files?.[0];
        if (file) uploadHeroVideo(prefix, file);
    });
    input?.addEventListener('change', () => {
        const file = input.files?.[0];
        if (file) uploadHeroVideo(prefix, file);
    });
    updateHeroMediaFields(prefix);
}

async function uploadHeroVideo(prefix, file) {
    const status = document.getElementById(`${prefix}-video-upload-status`);
    const input = document.getElementById(`${prefix}-video-file`);
    const allowedTypes = ['video/mp4', 'video/webm'];
    if (!allowedTypes.includes(file.type) && !/\.(mp4|webm)$/i.test(file.name)) {
        alert('Pilih video MP4 atau WebM.');
        return;
    }
    if (file.size > 50 * 1024 * 1024) {
        alert('Ukuran video maksimal 50 MB.');
        return;
    }
    if (status) status.textContent = 'Mengunggah video, jangan tutup halaman...';
    const form = new FormData();
    form.append('video', file);
    try {
        const result = await api('video_upload', { method: 'POST', body: form });
        setHeroVideoPreview(prefix, result.url);
        const contentLabel = prefix === 'post' ? 'Artikel' : 'Program';
        if (status) status.textContent = `Video tersimpan (${formatFileSize(result.size)}). Klik Simpan ${contentLabel} untuk menerapkannya.`;
    } catch (error) {
        if (status) status.textContent = '';
        alert(error.message);
    } finally {
        if (input) input.value = '';
    }
}

function formatFileSize(bytes) {
    const size = Number(bytes || 0);
    if (size < 1024 * 1024) return `${Math.max(1, Math.round(size / 1024))} KB`;
    return `${(size / (1024 * 1024)).toFixed(1)} MB`;
}

function setDonationQrImage(prefix, url) {
    const input = document.getElementById(`${prefix}-donation-qr-image`);
    const preview = document.getElementById(`${prefix}-qr-preview`);
    if (input) input.value = url || '';
    if (!preview) return;
    preview.innerHTML = url
        ? `<img src="${escapeHtml(url)}" alt="Preview QR/barcode donasi"><button type="button" data-clear-donation-qr="${escapeHtml(prefix)}" aria-label="Hapus QR/barcode">×</button>`
        : '';
}

function setupDonationQrUpload(prefix) {
    const zone = document.getElementById(`${prefix}-qr-drop-zone`);
    const input = document.getElementById(`${prefix}-qr-file`);
    if (!zone || !input) return;
    const upload = async file => {
        if (!file) return;
        if (file.type !== 'image/png' && !/\.png$/i.test(file.name)) {
            alert('QR/barcode harus berupa file PNG.');
            return;
        }
        if (file.size > 2 * 1024 * 1024) {
            alert('Ukuran QR/barcode maksimal 2 MB.');
            return;
        }
        const status = document.getElementById(`${prefix}-qr-upload-status`);
        if (status) status.textContent = 'Mengunggah QR/barcode...';
        const form = new FormData();
        form.append('qr', file);
        try {
            const result = await api('qr_upload', { method: 'POST', body: form });
            setDonationQrImage(prefix, result.url);
            if (status) status.textContent = 'QR/barcode tersimpan. Simpan konten untuk menerapkan perubahan.';
        } catch (error) {
            if (status) status.textContent = '';
            alert(error.message);
        } finally {
            input.value = '';
        }
    };
    zone.addEventListener('click', event => {
        if (!event.target.closest('[data-clear-donation-qr]')) input.click();
    });
    input.addEventListener('change', () => upload(input.files?.[0]));
    zone.addEventListener('dragover', event => event.preventDefault());
    zone.addEventListener('drop', event => {
        event.preventDefault();
        upload(event.dataTransfer?.files?.[0]);
    });
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

const adminPageHeadings = {
    dashboard: ['Ringkasan Website', 'Pantau aktivitas utama dan kondisi pengelolaan website.'],
    articles: ['Kelola Artikel', 'Tulis, jadwalkan, dan publikasikan informasi untuk pembaca.'],
    'programs-admin': ['Kelola Program', 'Susun program unggulan, media, dan kanal kontribusi resmi.'],
    institution: ['Kredibilitas Lembaga', 'Kelola informasi legalitas, transparansi, dan kanal resmi DDU.'],
    history: ['Riwayat Perubahan', 'Lihat aktivitas perubahan artikel dan program oleh pengelola.'],
    admins: ['Kelola Admin', 'Atur akun dan hak akses pengelola website.'],
    profile: ['Profil dan Keamanan', 'Perbarui identitas akun serta lindungi akses panel Anda.']
};

function updateAdminWorkspaceHeader(tab) {
    const [title, description] = adminPageHeadings[tab] || adminPageHeadings.dashboard;
    const titleElement = document.getElementById('admin-page-title');
    const descriptionElement = document.getElementById('admin-page-description');
    if (titleElement) titleElement.textContent = title;
    if (descriptionElement) descriptionElement.textContent = description;
}

const currentDateElement = document.getElementById('admin-current-date');
if (currentDateElement) {
    currentDateElement.textContent = new Intl.DateTimeFormat('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    }).format(new Date());
}

window.switchTab = tab => {
    updateAdminWorkspaceHeader(tab);
    document.getElementById('content-dashboard')?.classList.toggle('hidden', tab !== 'dashboard');
    document.getElementById('content-articles')?.classList.toggle('hidden', tab !== 'articles');
    document.getElementById('content-programs-admin')?.classList.toggle('hidden', tab !== 'programs-admin');
    document.getElementById('content-admins')?.classList.toggle('hidden', tab !== 'admins');
    document.getElementById('content-profile')?.classList.toggle('hidden', tab !== 'profile');
    document.getElementById('content-history')?.classList.toggle('hidden', tab !== 'history');
    document.getElementById('content-institution')?.classList.toggle('hidden', tab !== 'institution');
    document.querySelector('.preview-group')?.classList.toggle('hidden', tab === 'dashboard' || tab === 'admins' || tab === 'profile' || tab === 'history' || tab === 'institution');
    document.getElementById('tab-dashboard')?.classList.toggle('active', tab === 'dashboard');
    document.getElementById('tab-articles')?.classList.toggle('active', tab === 'articles');
    document.getElementById('tab-programs-admin')?.classList.toggle('active', tab === 'programs-admin');
    document.getElementById('tab-admins')?.classList.toggle('active', tab === 'admins');
    document.getElementById('tab-profile')?.classList.toggle('active', tab === 'profile');
    document.getElementById('tab-history')?.classList.toggle('active', tab === 'history');
    document.getElementById('tab-institution')?.classList.toggle('active', tab === 'institution');
    document.querySelectorAll('.sidebar-nav button').forEach(button => {
        if (button.classList.contains('active')) {
            button.setAttribute('aria-current', 'page');
        } else {
            button.removeAttribute('aria-current');
        }
    });
    if (tab === 'dashboard') fetchStats();
    if (tab === 'profile') loadProfile();
    if (tab === 'history') loadHistory();
    if (tab === 'institution') loadInstitutionProfile();
    updatePreview();
};

const editorSelectionRanges = { post: null, prog: null };
const blockFormatClasses = {
    align: ['text-align-left', 'text-align-center', 'text-align-right', 'text-align-justify'],
    spacing: ['text-spacing-1', 'text-spacing-115', 'text-spacing-15', 'text-spacing-2']
};

function activeEditorPrefix() {
    if (!document.getElementById('content-articles')?.classList.contains('hidden')) return 'post';
    if (!document.getElementById('content-programs-admin')?.classList.contains('hidden')) return 'prog';
    return '';
}

function restoreEditorSelection(prefix) {
    const range = editorSelectionRanges[prefix];
    const editor = document.getElementById(`${prefix}-content-editor`);
    if (!range || !editor) return editor;
    const selection = window.getSelection();
    selection.removeAllRanges();
    selection.addRange(range);
    return editor;
}

document.addEventListener('selectionchange', () => {
    const selection = window.getSelection();
    if (!selection?.rangeCount) return;
    ['post', 'prog'].forEach(prefix => {
        const editor = document.getElementById(`${prefix}-content-editor`);
        if (editor && editor.contains(selection.anchorNode)) {
            editorSelectionRanges[prefix] = selection.getRangeAt(0).cloneRange();
        }
    });
});

window.formatDoc = (command, value = null) => {
    const prefix = activeEditorPrefix();
    if (prefix) restoreEditorSelection(prefix)?.focus();
    document.execCommand(command, false, value);
    updatePreview();
};

window.applyBlockFormatting = (type, value) => {
    const prefix = activeEditorPrefix();
    const editor = restoreEditorSelection(prefix);
    if (!editor || !blockFormatClasses[type]) return;
    editor.focus();
    const selection = window.getSelection();
    if (!selection?.rangeCount) return;
    const range = selection.getRangeAt(0);
    const blockSelector = 'p,h1,h2,h3,h4,blockquote,li';
    let blocks = Array.from(editor.querySelectorAll(blockSelector)).filter(block => {
        try {
            return range.intersectsNode(block);
        } catch (error) {
            return false;
        }
    });
    if (!blocks.length) {
        const start = range.startContainer.nodeType === Node.ELEMENT_NODE
            ? range.startContainer
            : range.startContainer.parentElement;
        const current = start?.closest(blockSelector);
        if (current && editor.contains(current)) blocks = [current];
    }
    if (!blocks.length) {
        document.execCommand('formatBlock', false, 'p');
        const current = window.getSelection()?.anchorNode?.parentElement?.closest('p');
        if (current && editor.contains(current)) blocks = [current];
    }

    const className = type === 'align'
        ? `text-align-${value}`
        : ({
            '1': 'text-spacing-1',
            '1.15': 'text-spacing-115',
            '1.5': 'text-spacing-15',
            '2': 'text-spacing-2'
        })[value] || '';
    blocks.forEach(block => {
        block.classList.remove(...blockFormatClasses[type]);
        if (className) block.classList.add(className);
    });
    updatePreview();
};

const contentTemplates = {
    'program-wakaf': `
        <h2>Bangun Rumah Perjuangan Para Santri Penjaga Al-Qur’an</h2>
        <p>Setiap hari, para santri yatim dan dhuafa di Pesantren Daarul Uluum Bogor belajar, beribadah, serta menghafal Al-Qur’an dengan penuh kesungguhan. Di tengah semangat itu, mereka masih membutuhkan tempat tinggal yang lebih layak, aman, dan nyaman untuk mendukung proses pendidikan serta pembinaan.</p>
        <blockquote>Asrama bukan sekadar tempat beristirahat. Di sanalah para santri menata disiplin, memperkuat persaudaraan, menjaga hafalan, dan mempersiapkan diri menjadi generasi yang bermanfaat bagi umat.</blockquote>
        <h2>Mengapa Program Ini Penting?</h2>
        <ul>
            <li>Menyediakan ruang tinggal yang aman dan layak bagi para santri.</li>
            <li>Mendukung kegiatan belajar, ibadah, dan menghafal Al-Qur’an.</li>
            <li>Menciptakan lingkungan pembinaan yang tertib, sehat, dan nyaman.</li>
            <li>Menjadi sarana amal jariyah yang manfaatnya terus dirasakan.</li>
        </ul>
        <h2>Wakaf Anda Menjadi Manfaat Berkelanjutan</h2>
        <p>Melalui Program Wakaf Asrama Santri, Dompet Dana Umat Daarul Uluum mengajak Ayah, Bunda, Kakak, dan seluruh Sahabat Kebaikan untuk ikut membangun serta merenovasi rumah perjuangan para santri. Setiap dukungan akan diarahkan untuk kebutuhan pembangunan sesuai tahapan program dan dilaporkan melalui kanal resmi lembaga.</p>
        <h2>Mari Ambil Bagian</h2>
        <p>Tidak harus menunggu mampu memberi dalam jumlah besar. Setiap wakaf yang ditunaikan dengan ikhlas akan menyempurnakan ikhtiar bersama dalam menghadirkan tempat terbaik bagi para penjaga Al-Qur’an.</p>
        <p><strong>Klik “Wakaf Sekarang” untuk memperoleh informasi penyaluran melalui WhatsApp resmi Dompet Dana Umat.</strong></p>`,
    'program-sosial': `
        <h2>Latar Belakang Program</h2>
        <p>Jelaskan kondisi penerima manfaat, masalah yang dihadapi, serta alasan program ini perlu dilaksanakan.</p>
        <h2>Tujuan Program</h2>
        <ul><li>Tujuan utama program.</li><li>Perubahan yang ingin diwujudkan.</li><li>Manfaat jangka panjang.</li></ul>
        <h2>Sasaran Penerima Manfaat</h2>
        <p>Jelaskan siapa penerima manfaat, lokasi, dan kriteria penerima secara singkat.</p>
        <h2>Rencana Penyaluran</h2>
        <ol><li>Pengumpulan dukungan.</li><li>Verifikasi kebutuhan.</li><li>Pelaksanaan dan dokumentasi.</li><li>Pelaporan hasil program.</li></ol>
        <h2>Mari Berkontribusi</h2>
        <p>Tutup dengan ajakan yang jelas, jujur, dan sesuai tujuan program.</p>`,
    'program-umum': `
        <h2>Tentang Program</h2><p>Jelaskan latar belakang dan kebutuhan yang ingin dijawab melalui program ini.</p>
        <h2>Tujuan dan Manfaat</h2><ul><li>Tujuan pertama.</li><li>Manfaat bagi penerima.</li><li>Dampak yang diharapkan.</li></ul>
        <h2>Cara Program Dilaksanakan</h2><ol><li>Tahap persiapan.</li><li>Tahap pelaksanaan.</li><li>Dokumentasi dan pelaporan.</li></ol>
        <h2>Ambil Bagian dalam Kebaikan</h2><p>Tambahkan ajakan berkontribusi dan arahkan pengunjung ke kanal resmi.</p>`,
    'article-activity': `
        <h2>Rangkaian Kegiatan</h2><p>Ceritakan waktu, lokasi, peserta, dan jalannya kegiatan dengan urutan yang mudah diikuti.</p>
        <h2>Manfaat yang Dirasakan</h2><p>Jelaskan hasil kegiatan serta perubahan yang dirasakan penerima manfaat.</p>
        <blockquote>Tambahkan pernyataan singkat dari penerima manfaat, relawan, atau penanggung jawab kegiatan.</blockquote>
        <h2>Terima Kasih Sahabat Kebaikan</h2><p>Sampaikan apresiasi dan ajakan mengikuti program berikutnya.</p>`,
    'article-education': `
        <h2>Pengantar</h2><p>Kenalkan topik dan alasan pembaca perlu memahaminya.</p>
        <h2>Hal Penting yang Perlu Diketahui</h2><ul><li>Poin utama pertama.</li><li>Poin utama kedua.</li><li>Poin utama ketiga.</li></ul>
        <h2>Penjelasan dan Contoh</h2><p>Berikan uraian praktis, contoh, dan sumber yang dapat dipercaya.</p>
        <h2>Kesimpulan</h2><p>Ringkas pesan utama dan berikan langkah yang dapat dilakukan pembaca.</p>`,
    'article-distribution': `
        <h2>Ringkasan Penyaluran</h2><p>Jelaskan jenis bantuan, waktu, lokasi, dan tujuan penyaluran.</p>
        <h2>Penerima Manfaat</h2><ul><li>Jumlah penerima manfaat.</li><li>Kriteria atau wilayah penerima.</li><li>Bentuk bantuan yang diterima.</li></ul>
        <h2>Proses Pelaksanaan</h2><ol><li>Verifikasi penerima.</li><li>Persiapan bantuan.</li><li>Penyaluran dan dokumentasi.</li></ol>
        <h2>Dampak dan Tindak Lanjut</h2><p>Jelaskan manfaat yang dirasakan serta rencana tindak lanjut program.</p>`
};

window.applyContentTemplate = (prefix, templateKey) => {
    const editor = document.getElementById(`${prefix}-content-editor`);
    const template = contentTemplates[templateKey];
    if (!editor || !template) return;
    if (editor.textContent.trim() && !confirm('Isi detail saat ini akan diganti dengan template. Lanjutkan?')) return;
    editor.innerHTML = template.trim();
    updatePreview();
    editor.focus();
};

window.insertEditorLink = () => {
    const href = prompt('Masukkan tautan lengkap (https://...), alamat internal (/halaman), atau bagian halaman (#bagian):');
    if (href === null || href.trim() === '') return;
    const normalized = href.trim();
    if (!/^(https?:\/\/|\/|#)/i.test(normalized)) {
        alert('Tautan harus diawali https://, /, atau #.');
        return;
    }
    document.execCommand('createLink', false, normalized);
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
document.getElementById('profile-form')?.addEventListener('submit', saveOwnProfile);
document.getElementById('password-form')?.addEventListener('submit', changeOwnPassword);
document.getElementById('admin-password-reset-form')?.addEventListener('submit', submitAdminPasswordReset);
document.getElementById('institution-form')?.addEventListener('submit', saveInstitutionProfile);

function institutionElement(key) {
    return document.getElementById(`inst-${key.replaceAll('_', '-')}`);
}

async function loadInstitutionProfile() {
    try {
        const result = await api('institution');
        institutionFields.forEach(key => {
            const element = institutionElement(key);
            if (element) element.value = result.data?.[key] || '';
        });
    } catch (error) {
        alert(error.message);
    }
}

async function saveInstitutionProfile(event) {
    event.preventDefault();
    const button = event.currentTarget.querySelector('button[type="submit"]');
    const payload = {};
    institutionFields.forEach(key => { payload[key] = institutionElement(key)?.value || ''; });
    button.disabled = true;
    try {
        await api('institution', { method: 'POST', body: JSON.stringify(payload) });
        alert('Profil kredibilitas berhasil disimpan.');
    } catch (error) {
        alert(error.message);
    } finally {
        button.disabled = false;
    }
}

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
        donation_qr_image: document.getElementById(`${prefix}-donation-qr-image`).value,
        seo_title: document.getElementById(`${prefix}-seo-title`).value,
        seo_description: document.getElementById(`${prefix}-seo-description`).value,
        social_image: document.getElementById(`${prefix}-social-image`).value,
        image_alt: document.getElementById(`${prefix}-image-alt`).value,
        category: document.getElementById(`${prefix}-category`).value,
        status: document.getElementById(`${prefix}-status`).value,
        published_at: document.getElementById(`${prefix}-published-at`).value
    };
    if (resource === 'programs') {
        payload.hero_title = document.getElementById('prog-hero-title').value;
        payload.hero_subtitle = document.getElementById('prog-hero-subtitle').value;
        payload.hero_media_type = document.getElementById('prog-hero-media-type').value;
        payload.hero_video_url = payload.hero_media_type === 'video'
            ? document.getElementById('prog-hero-video-url').value
            : (['youtube', 'drive'].includes(payload.hero_media_type)
                ? document.getElementById('prog-hero-video-link').value.trim()
                : '');
        payload.featured_order = document.getElementById('prog-featured-order').value;
    } else {
        payload.author_name = document.getElementById('post-author-name').value.trim();
        payload.hero_image = document.getElementById('post-hero-image-url').value;
        payload.hero_images = parseGalleryImages(document.getElementById('post-hero-images').value, 10);
        payload.hero_media_type = document.getElementById('post-hero-media-type').value;
        payload.hero_video_url = payload.hero_media_type === 'video'
            ? document.getElementById('post-hero-video-url').value
            : (['youtube', 'drive'].includes(payload.hero_media_type)
                ? document.getElementById('post-hero-video-link').value.trim()
                : '');
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
    await loadProfile();
    await Promise.all([fetchStats(), loadLists()]);
}

async function loadLists() {
    try {
        const requests = [loadContentList('posts'), loadContentList('programs')];
        if (currentRole === 'super_admin') requests.push(loadAdminAccounts());
        await Promise.all(requests);
    } catch (error) { console.error(error); }
}

function setupContentListFilters(resource) {
    const state = contentListState[resource];
    const prefix = state.prefix;
    const search = document.getElementById(`${prefix}-list-search`);
    const status = document.getElementById(`${prefix}-list-status`);
    const category = document.getElementById(`${prefix}-list-category`);
    const sort = document.getElementById(`${prefix}-list-sort`);
    search?.addEventListener('input', () => {
        clearTimeout(state.searchTimer);
        state.searchTimer = setTimeout(() => {
            state.page = 1;
            loadContentList(resource);
        }, 350);
    });
    [status, category, sort].forEach(control => control?.addEventListener('change', () => {
        state.page = 1;
        loadContentList(resource);
    }));
}

async function loadContentList(resource) {
    const state = contentListState[resource];
    const requestId = ++state.requestId;
    const prefix = state.prefix;
    const summary = document.getElementById(`${prefix}-list-summary`);
    if (summary) summary.textContent = 'Memuat daftar...';
    try {
        const parameters = new URLSearchParams({
            admin: '1',
            page: String(state.page),
            per_page: String(state.perPage),
            search: document.getElementById(`${prefix}-list-search`)?.value.trim() || '',
            status: document.getElementById(`${prefix}-list-status`)?.value || 'all',
            category: document.getElementById(`${prefix}-list-category`)?.value || '',
            sort: document.getElementById(`${prefix}-list-sort`)?.value || 'created_desc'
        });
        const result = await api(`${resource}&${parameters.toString()}`);
        if (requestId !== state.requestId) return;
        const meta = result.meta || { page: 1, total: 0, total_pages: 1, categories: [] };
        state.page = Number(meta.page || 1);
        renderCategoryFilter(prefix, meta.categories || []);
        renderList(resource === 'posts' ? 'admin-post-list' : 'admin-program-list', result.data || [], resource);
        renderContentPagination(prefix, resource, meta);
        if (summary) {
            const label = resource === 'posts' ? 'artikel' : 'program';
            summary.textContent = Number(meta.total)
                ? `Menampilkan ${result.data.length} dari ${Number(meta.total)} ${label} · Halaman ${Number(meta.page)} dari ${Number(meta.total_pages)}`
                : `Tidak ada ${label} yang cocok dengan filter.`;
        }
    } catch (error) {
        if (requestId !== state.requestId) return;
        if (summary) summary.textContent = error.message;
        console.error(error);
    }
}

function renderCategoryFilter(prefix, categories) {
    const select = document.getElementById(`${prefix}-list-category`);
    if (!select) return;
    const selected = select.value;
    select.replaceChildren(new Option('Semua kategori', ''));
    categories.forEach(category => select.add(new Option(category, category)));
    if ([...select.options].some(option => option.value === selected)) select.value = selected;
}

function renderContentPagination(prefix, resource, meta) {
    const container = document.getElementById(`${prefix}-list-pagination`);
    if (!container) return;
    const current = Number(meta.page || 1);
    const totalPages = Number(meta.total_pages || 1);
    if (totalPages <= 1) {
        container.replaceChildren();
        return;
    }

    const pages = new Set([1, totalPages]);
    for (let page = Math.max(1, current - 2); page <= Math.min(totalPages, current + 2); page += 1) {
        pages.add(page);
    }
    const orderedPages = [...pages].sort((a, b) => a - b);
    const fragment = document.createDocumentFragment();
    fragment.append(createPaginationButton('Sebelumnya', current - 1, current === 1, false, resource));
    orderedPages.forEach((page, index) => {
        if (index > 0 && page - orderedPages[index - 1] > 1) {
            const separator = document.createElement('span');
            separator.textContent = '…';
            separator.className = 'pagination-separator';
            fragment.append(separator);
        }
        fragment.append(createPaginationButton(String(page), page, false, page === current, resource));
    });
    fragment.append(createPaginationButton('Berikutnya', current + 1, current === totalPages, false, resource));
    container.replaceChildren(fragment);
}

function createPaginationButton(label, page, disabled, current, resource) {
    const button = document.createElement('button');
    button.type = 'button';
    button.textContent = label;
    button.disabled = disabled;
    if (current) button.setAttribute('aria-current', 'page');
    button.addEventListener('click', () => {
        contentListState[resource].page = page;
        loadContentList(resource);
        document.getElementById(`${contentListState[resource].prefix}-list-summary`)?.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });
    });
    return button;
}

async function loadProfile() {
    try {
        const result = await api('profile');
        const profile = result.data || {};
        const name = document.getElementById('profile-name');
        const email = document.getElementById('profile-email');
        if (name && document.activeElement !== name) name.value = profile.display_name || '';
        if (email && document.activeElement !== email) email.value = profile.email || '';
        document.getElementById('profile-role').textContent = profile.role === 'super_admin' ? 'Super Admin' : 'Admin';
        document.getElementById('profile-last-login').textContent = formatAccountDate(profile.last_login_at);
        document.getElementById('profile-created-at').textContent = formatAccountDate(profile.created_at);
        document.getElementById('reset-admin-id').dataset.currentAdminId = String(profile.id || '');
        currentRole = profile.role || currentRole;
        document.getElementById('tab-admins')?.classList.toggle('hidden', currentRole !== 'super_admin');
        updateSecurityAlerts(result.security || {});
    } catch (error) {
        console.error(error);
    }
}

function formatAccountDate(value) {
    const date = utcDate(value);
    return date ? date.toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) : 'Belum tersedia';
}

function updateSecurityAlerts(security) {
    const alerts = [
        document.getElementById('dashboard-security-alert'),
        document.getElementById('profile-security-alert')
    ].filter(Boolean);
    let message = '';
    if (!security.enabled) {
        message = '<strong>Pencatatan keamanan belum aktif</strong>Jalankan file database/add_admin_security.sql melalui phpMyAdmin.';
    } else if (security.warning) {
        const failed = Number(security.failed_attempts_24h || 0);
        const blocked = Number(security.blocked_attempts_24h || 0);
        const last = security.last_suspicious_at ? ` Terakhir: ${escapeHtml(formatAccountDate(security.last_suspicious_at))}.` : '';
        message = `<strong>Peringatan aktivitas login</strong>Terdapat ${failed} percobaan gagal dan ${blocked} pemblokiran dalam 24 jam terakhir.${last} Ganti kata sandi jika aktivitas ini bukan milik Anda.`;
    }
    alerts.forEach(alert => {
        alert.innerHTML = message;
        alert.classList.toggle('hidden', !message);
    });
}

async function saveOwnProfile(event) {
    event.preventDefault();
    const button = event.currentTarget.querySelector('button[type="submit"]');
    button.disabled = true;
    try {
        const result = await api('profile', {
            method: 'POST',
            body: JSON.stringify({
                action: 'update_profile',
                display_name: document.getElementById('profile-name').value,
                email: document.getElementById('profile-email').value,
                current_password: document.getElementById('profile-current-password').value
            })
        });
        if (result.csrf) csrfToken = result.csrf;
        document.getElementById('profile-current-password').value = '';
        await loadProfile();
        alert(result.message);
    } catch (error) {
        alert(error.message);
    } finally {
        button.disabled = false;
    }
}

async function changeOwnPassword(event) {
    event.preventDefault();
    const button = event.currentTarget.querySelector('button[type="submit"]');
    button.disabled = true;
    try {
        const result = await api('profile', {
            method: 'POST',
            body: JSON.stringify({
                action: 'change_password',
                current_password: document.getElementById('password-current').value,
                new_password: document.getElementById('password-new').value,
                new_password_confirmation: document.getElementById('password-confirmation').value
            })
        });
        if (result.csrf) csrfToken = result.csrf;
        event.currentTarget.reset();
        await loadProfile();
        alert(result.message);
    } catch (error) {
        alert(error.message);
    } finally {
        button.disabled = false;
    }
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
        alert('Admin berhasil ditambahkan.');
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
    const admins = result.data || [];
    const currentAdminId = Number(document.getElementById('reset-admin-id')?.dataset.currentAdminId || 0);
    container.innerHTML = admins.length ? admins.map(admin => `<div class="post-list-item">
        <span><strong>${escapeHtml(admin.display_name || admin.email)}</strong><small class="admin-account-meta">${escapeHtml(admin.email)} · ${escapeHtml(admin.role)} · ${Number(admin.is_active) ? 'Aktif' : 'Nonaktif'}<br>Login terakhir: ${escapeHtml(formatAccountDate(admin.last_login_at))}</small></span>
        <div class="post-list-actions">
            ${Number(admin.id) !== currentAdminId ? `<button type="button" class="btn-secondary" data-reset-admin="${Number(admin.id)}" data-admin-email="${escapeHtml(admin.email)}">Reset Password</button>` : ''}
            ${Number(admin.is_active) && Number(admin.id) !== currentAdminId ? `<button type="button" class="btn-delete" data-disable-admin="${Number(admin.id)}">Nonaktifkan</button>` : ''}
        </div>
    </div>`).join('') : '<p class="admin-empty-state">Belum ada akun admin untuk ditampilkan.</p>';
}

function renderList(containerId, items, resource) {
    const container = document.getElementById(containerId);
    if (!container) return;
    if (!items.length) {
        const label = resource === 'posts' ? 'artikel' : 'program';
        container.innerHTML = `<p class="admin-empty-state">Belum ada ${label}. Data baru yang disimpan akan tampil di sini.</p>`;
        return;
    }
    container.innerHTML = items.map(item => {
        const publication = publicationLabel(item);
        const dateLabel = item.published_at
            ? `Tanggal tayang: ${formatAccountDate(item.published_at)}`
            : `Dibuat: ${formatAccountDate(item.created_at)}`;
        const previewUrl = resource === 'posts'
            ? `/artikel/${encodeURIComponent(item.slug)}?preview=1`
            : `/${encodeURIComponent(item.slug)}?preview=1`;
        return `<div class="post-list-item"><span><strong>${escapeHtml(item.title)}</strong><small class="admin-account-meta">${escapeHtml(item.category || 'Umum')} · ${escapeHtml(dateLabel)}</small><span class="status-badge ${publication.className}">${publication.label}</span></span><div class="post-list-actions">
        <button type="button" class="btn-secondary" data-preview-url="${escapeHtml(previewUrl)}">Preview</button>
        <button type="button" class="btn-secondary" data-edit="${resource}" data-id="${Number(item.id)}">Edit</button>
        <button type="button" class="btn-delete" data-delete="${resource}" data-id="${Number(item.id)}">Hapus</button>
    </div></div>`;
    }).join('');
}

function publicationLabel(item) {
    if (item.status !== 'published') return { label: 'Draft', className: 'status-draft' };
    const publishedAt = utcDate(item.published_at);
    if (publishedAt && publishedAt.getTime() > Date.now()) {
        return { label: `Terjadwal ${publishedAt.toLocaleString('id-ID')}`, className: 'status-scheduled' };
    }
    return { label: 'Dipublikasikan', className: 'status-published' };
}

function utcDate(value) {
    if (!value) return null;
    const date = new Date(String(value).replace(' ', 'T') + (String(value).includes('Z') ? '' : 'Z'));
    return Number.isNaN(date.getTime()) ? null : date;
}

function utcToLocalInput(value) {
    const date = utcDate(value);
    if (!date) return '';
    const local = new Date(date.getTime() - date.getTimezoneOffset() * 60000);
    return local.toISOString().slice(0, 16);
}

async function loadHistory() {
    const container = document.getElementById('admin-history-list');
    if (!container) return;
    container.innerHTML = '<p>Memuat riwayat...</p>';
    try {
        const result = await api('history&limit=100');
        container.innerHTML = (result.data || []).length
            ? result.data.map(item => `<div class="history-item"><p>${escapeHtml(item.summary)}</p><small>${escapeHtml(item.admin_email || `Admin #${item.admin_id}`)} · ${escapeHtml(utcDate(item.created_at)?.toLocaleString('id-ID') || item.created_at)}</small></div>`).join('')
            : '<p>Belum ada riwayat perubahan.</p>';
    } catch (error) {
        container.innerHTML = `<p>${escapeHtml(error.message)}</p>`;
    }
}

document.addEventListener('click', event => {
    const removeSliderImage = event.target.closest('[data-remove-slider-image]');
    const removeHeroImage = event.target.closest('[data-remove-hero-image]');
    const removeContentPhoto = event.target.closest('.content-photo-remove');
    const clearSeoImage = event.target.closest('[data-clear-seo-image]');
    const clearHeroVideo = event.target.closest('[data-clear-hero-video]');
    const clearDonationQr = event.target.closest('[data-clear-donation-qr]');
    const previewButton = event.target.closest('[data-preview-url]');
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
    if (clearHeroVideo) {
        event.stopPropagation();
        const prefix = clearHeroVideo.dataset.clearHeroVideo;
        setHeroVideoPreview(prefix, '');
        const status = document.getElementById(`${prefix}-video-upload-status`);
        const contentLabel = prefix === 'post' ? 'artikel' : 'program';
        if (status) status.textContent = `Video dihapus dari rancangan. Simpan ${contentLabel} untuk menerapkan perubahan.`;
    }
    if (clearDonationQr) {
        event.stopPropagation();
        const prefix = clearDonationQr.dataset.clearDonationQr;
        setDonationQrImage(prefix, '');
        const status = document.getElementById(`${prefix}-qr-upload-status`);
        if (status) status.textContent = 'QR/barcode dihapus dari rancangan. Simpan konten untuk menerapkan perubahan.';
    }
    if (previewButton) window.open(previewButton.dataset.previewUrl, '_blank', 'noopener,noreferrer');
    if (edit) editItem(edit.dataset.edit, edit.dataset.id);
    if (remove) deleteItem(remove.dataset.delete, remove.dataset.id);
    const disableAdmin = event.target.closest('[data-disable-admin]');
    if (disableAdmin) deactivateAdminAccount(disableAdmin.dataset.disableAdmin);
    const resetAdmin = event.target.closest('[data-reset-admin]');
    if (resetAdmin) openAdminPasswordDialog(resetAdmin.dataset.resetAdmin, resetAdmin.dataset.adminEmail);
    if (event.target.closest('[data-close-password-dialog]')) closeAdminPasswordDialog();
});

function openAdminPasswordDialog(id, email) {
    const dialog = document.getElementById('admin-password-dialog');
    document.getElementById('reset-admin-id').value = id;
    document.getElementById('reset-admin-description').textContent = `Buat kata sandi baru untuk ${email}.`;
    document.getElementById('admin-password-reset-form').reset();
    document.getElementById('reset-admin-id').value = id;
    dialog?.showModal();
}

function closeAdminPasswordDialog() {
    const dialog = document.getElementById('admin-password-dialog');
    if (dialog?.open) dialog.close();
}

async function submitAdminPasswordReset(event) {
    event.preventDefault();
    const button = event.currentTarget.querySelector('button[type="submit"]');
    button.disabled = true;
    try {
        const result = await api('admin_password_reset', {
            method: 'POST',
            body: JSON.stringify({
                admin_id: document.getElementById('reset-admin-id').value,
                new_password: document.getElementById('reset-admin-password').value,
                new_password_confirmation: document.getElementById('reset-admin-password-confirmation').value
            })
        });
        closeAdminPasswordDialog();
        event.currentTarget.reset();
        alert(result.message);
    } catch (error) {
        alert(error.message);
    } finally {
        button.disabled = false;
    }
}

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
        ['id', 'title', 'slug', 'excerpt', 'seo-title', 'seo-description', 'image-alt', 'category', 'status'].forEach(field => {
            const databaseField = field.replaceAll('-', '_');
            const el = document.getElementById(`${prefix}-${field}`);
            if (el) el.value = data[databaseField] || '';
        });
        setSeoImage(prefix, data.social_image || '');
        document.getElementById(`${prefix}-published-at`).value = utcToLocalInput(data.published_at);
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
        setDonationQrImage(prefix, data.donation_qr_image || '');
        if (resource === 'programs') {
            document.getElementById('prog-hero-title').value = data.hero_title || '';
            document.getElementById('prog-hero-subtitle').value = data.hero_subtitle || '';
            const mediaType = ['images', 'video', 'youtube', 'drive'].includes(data.hero_media_type)
                ? data.hero_media_type
                : 'images';
            document.getElementById('prog-hero-media-type').value = mediaType;
            setHeroVideoPreview('prog', mediaType === 'video' ? (data.hero_video_url || '') : '');
            document.getElementById('prog-hero-video-link').value = ['youtube', 'drive'].includes(mediaType)
                ? (data.hero_video_url || '')
                : '';
            updateHeroMediaFields('prog');
            document.getElementById('prog-featured-order').value = data.featured_order ?? '';
        } else {
            document.getElementById('post-author-name').value = data.author_name || '';
            const heroImages = parseGalleryImages(data.hero_images, 10);
            if (!heroImages.length && data.hero_image) heroImages.push(data.hero_image);
            setHeroImages(heroImages);
            const mediaType = ['images', 'video', 'youtube', 'drive'].includes(data.hero_media_type)
                ? data.hero_media_type
                : 'images';
            document.getElementById('post-hero-media-type').value = mediaType;
            setHeroVideoPreview('post', mediaType === 'video' ? (data.hero_video_url || '') : '');
            document.getElementById('post-hero-video-link').value = ['youtube', 'drive'].includes(mediaType)
                ? (data.hero_video_url || '')
                : '';
            updateHeroMediaFields('post');
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
    setupContentListFilters('posts');
    setupContentListFilters('programs');
    setupAutomaticSlug('post');
    setupAutomaticSlug('prog');
    setupDropZone('article-drop-zone', 'post-image-file', 'post-image-url', 'image-preview', 'post');
    setupDropZone('prog-drop-zone', 'prog-image-file', 'prog-image-url', 'prog-image-preview', 'prog');
    setupHeroImageDropZone('post-hero-drop-zone', 'post-hero-image-file');
    setupHeroMedia('post');
    setupHeroMedia('prog');
    setupDonationQrUpload('post');
    setupDonationQrUpload('prog');
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
