import { siteFooterHtml } from './site-footer.js?v=20260721-2';

const API = '/api/index.php?resource=institution';

function profileText(root, key, value) {
    root.querySelectorAll(`[data-profile="${key}"]`).forEach(element => {
        element.textContent = value || element.dataset.fallback || 'Belum dipublikasikan';
    });
    root.querySelectorAll(`[data-requires="${key}"]`).forEach(element => {
        element.hidden = !String(value || '').trim();
    });
}

function renderLines(root, key, value, fallback) {
    const container = root.querySelector(`[data-profile-lines="${key}"]`);
    if (!container) return;
    const lines = String(value || '').split(/\r?\n/).map(line => line.trim()).filter(Boolean);
    container.replaceChildren();
    if (!lines.length) {
        const paragraph = document.createElement('p');
        paragraph.className = 'empty-information';
        paragraph.textContent = fallback;
        container.appendChild(paragraph);
        return;
    }
    const list = document.createElement('ul');
    list.className = 'credibility-list';
    lines.forEach(line => {
        const item = document.createElement('li');
        const url = line.match(/https?:\/\/[^\s]+/i)?.[0];
        if (!url) {
            item.textContent = line;
        } else {
            const before = line.slice(0, line.indexOf(url));
            item.append(document.createTextNode(before));
            const link = document.createElement('a');
            link.href = url;
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
            link.textContent = 'Buka dokumen';
            item.appendChild(link);
        }
        list.appendChild(item);
    });
    container.appendChild(list);
}

async function loadProfile() {
    try {
        const response = await fetch(API, { credentials: 'same-origin' });
        const result = await response.json();
        if (!response.ok) throw new Error(result.message || 'Profil lembaga gagal dimuat.');
        const profile = result.data || {};
        Object.entries(profile).forEach(([key, value]) => profileText(document, key, value));
        document.querySelectorAll('[data-profile="privacy_contact"]').forEach(link => {
            if (link instanceof HTMLAnchorElement && profile.privacy_contact) link.href = `mailto:${profile.privacy_contact}`;
        });
        document.querySelectorAll('[data-official-email]').forEach(link => {
            if (profile.official_email) link.href = `mailto:${profile.official_email}`;
        });
        renderLines(document, 'management_structure', profile.management_structure, 'Struktur pengurus belum dipublikasikan. Silakan meminta informasi resmi melalui kontak lembaga.');
        renderLines(document, 'donation_accounts', profile.donation_accounts, 'Rekening resmi belum dipublikasikan pada halaman ini. Jangan melakukan transfer sebelum memperoleh konfirmasi dari WhatsApp resmi.');
        renderLines(document, 'collection_reports', profile.collection_reports, 'Laporan publik belum tersedia pada halaman ini.');
        renderLines(document, 'beneficiary_documentation', profile.beneficiary_documentation, 'Dokumentasi penerima manfaat akan ditampilkan setelah melalui pemeriksaan privasi dan persetujuan publikasi.');
        const phone = String(profile.official_phone || '').replace(/\D+/g, '');
        document.querySelectorAll('[data-official-whatsapp]').forEach(link => {
            if (phone) link.href = `https://wa.me/${phone.replace(/^0/, '62')}`;
        });
        const updated = result.updated_at ? new Date(String(result.updated_at).replace(' ', 'T') + 'Z') : null;
        document.querySelectorAll('[data-profile-updated]').forEach(element => {
            element.textContent = updated && !Number.isNaN(updated.getTime()) ? updated.toLocaleDateString('id-ID') : 'belum tersedia';
        });
    } catch (error) {
        console.error(error);
        document.querySelector('[data-profile-status]')?.classList.add('visible');
    }
}

document.querySelectorAll('[data-site-footer]').forEach(footer => { footer.outerHTML = siteFooterHtml(); });
loadProfile();
