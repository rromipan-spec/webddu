import { siteFooterHtml } from './site-footer.js?v=20260923-7';
import { detailSliderHtml, initDetailSliders, recordSliderImages } from './detail-slider.js?v=20260721-4';

const escapeHtml = (value = '') => String(value).replace(/[&<>'"]/g, char => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'
}[char]));

document.addEventListener('DOMContentLoaded', async () => {
    const container = document.getElementById('program-container');
    const querySlug = new URLSearchParams(location.search).get('slug');
    const routeSlug = location.pathname.match(/^\/([a-z0-9]+(?:-[a-z0-9]+)*)\/?$/i)?.[1] || '';
    const slug = querySlug || routeSlug;
    if (!container || !slug || !/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(slug)) return showError(container);
    try {
        const preview = new URLSearchParams(location.search).get('preview') === '1' ? '&preview=1' : '';
        const response = await fetch(`../api/index.php?resource=programs&slug=${encodeURIComponent(slug)}${preview}`, { credentials: 'same-origin' });
        const result = await response.json();
        if (!response.ok || !result.data) throw new Error(result.message);
        renderProgram(container, result.data);
        setupPage();
    } catch (error) {
        console.error(error);
        showError(container);
    }
});

function renderProgram(container, program) {
    document.title = `${program.seo_title || program.title} - Dompet Dana Umat`;
    document.body.classList.add('program-section-page');
    const hasSectionBuilder = Array.isArray(program.sections) && program.sections.length > 0;
    const sections = Array.isArray(program.sections)
        ? program.sections.filter(section => section && section.visible !== false && section.data)
        : [];
    const primaryCta = sections.find(section => section.type === 'cta')?.data || {};
    const wa = primaryCta.whatsapp_number || program.whatsapp_number || '6285121277046';
    const message = primaryCta.whatsapp_message || program.whatsapp_message || `Assalamualaikum, saya ingin berkonsultasi mengenai ${program.title}.`;
    const whatsappUrl = whatsappLink(wa, message);
    const sliderImages = recordSliderImages(program);
    const content = hasSectionBuilder
        ? sections.map((section, index) => renderProgramSection(section, program, index)).join('')
        : renderLegacyProgram(program, sliderImages);

    container.innerHTML = `
    ${programNavbarHtml()}
    <main class="program-builder-page">${content}</main>
    ${siteFooterHtml()}
    <a href="#" class="back-to-top">↑</a><a href="${escapeHtml(whatsappUrl)}" class="whatsapp-popup" target="_blank" rel="noopener noreferrer" aria-label="Hubungi WhatsApp"><img src="/asset/whatsapp-phone.svg" alt="" width="24" height="24"></a>`;
    container.querySelectorAll('.mock-content').forEach(removeEmptyContentLines);
    initDetailSliders(container);
    initProgramHeroVideo(container);
}

function renderLegacyProgram(program, sliderImages) {
    const wa = program.whatsapp_number || '6285121277046';
    const message = program.whatsapp_message || `Assalamualaikum, saya ingin berkonsultasi mengenai ${program.title}.`;
    const isWakaf = program.slug === 'wakaf-asrama-santri' || /\bwakaf\b/i.test(program.title);
    const actionLabel = isWakaf ? 'Wakaf Sekarang →' : 'Hubungi Admin via WhatsApp →';
    const actionText = isWakaf
        ? 'Salurkan wakaf terbaik Anda dan jadilah bagian dari perjuangan para santri.'
        : 'Konsultasikan donasi Anda secara amanah bersama tim layanan kami.';
    const whatsappUrl = whatsappLink(wa, message);
    const qrImage = donationQrUrl(program.donation_qr_image);
    const heroMedia = programHeroMediaHtml(program, sliderImages);
    return `
    <section class="hero detail-program-hero" style="padding:220px 0 120px">${heroMedia}<div class="hero-overlay" style="background:rgba(6, 59, 158, .8)"></div><div class="container hero-container" style="text-align:center"><div class="hero-content" style="max-width:100%;margin:0 auto"><span class="section-kicker">${escapeHtml(program.category || 'LAYANAN DDU')}</span><h1 style="color:#FAFAF7;margin-top:10px">${escapeHtml(program.hero_title || program.title)}</h1><p style="color:#EAF2FF">${escapeHtml(program.hero_subtitle)}</p></div></div></section>
    <section class="container fade-in" style="padding:80px 20px"><div style="max-width:850px;margin:0 auto;line-height:1.8" class="mock-content">${program.content || '<p>Konten belum tersedia.</p>'}</div></section>
    <section class="cta-minimal program-donation-cta fade-in"><div class="container"><span class="section-kicker">Langkah Kebaikan</span><h2>Ingin Berkontribusi untuk ${escapeHtml(program.title)}?</h2><p>${escapeHtml(actionText)}</p><div class="donation-methods${qrImage ? ' has-qr' : ''}">
        ${qrImage ? `<div class="donation-method donation-method--qr"><span class="donation-method-label">Scan Donasi</span><h3>QR/Barcode Resmi</h3><a href="${escapeHtml(qrImage)}" target="_blank" rel="noopener noreferrer" aria-label="Buka QR/barcode donasi ukuran penuh"><img src="${escapeHtml(qrImage)}" alt="QR atau barcode donasi ${escapeHtml(program.title)}" loading="lazy"></a><small>Ketuk gambar untuk memperbesar. Pastikan tujuan pembayaran sesuai informasi resmi DDU.</small></div>` : ''}
        <div class="donation-method donation-method--whatsapp"><span class="donation-method-label">WhatsApp Resmi</span><h3>Konsultasi Donasi</h3><p>Hubungi admin untuk memperoleh panduan penyaluran dan konfirmasi donasi.</p><strong class="donation-whatsapp-number">${escapeHtml(whatsappDisplay(wa))}</strong><a href="${whatsappUrl}" class="btn-whatsapp-minimal" target="_blank" rel="noopener noreferrer">${escapeHtml(actionLabel)}</a></div>
    </div></div></section>`;
}

function renderProgramSection(section, program, index) {
    const data = section.data || {};
    const type = String(section.type || 'content');
    const classes = [
        'program-builder-section',
        `program-builder-section--${type}`,
        `program-builder-section--theme-${safeChoice(data.theme, ['light', 'pale', 'blue', 'deep', 'warm'], 'light')}`,
        `program-builder-section--width-${safeChoice(data.width, ['narrow', 'boxed', 'full'], 'boxed')}`,
        `program-builder-section--align-${safeChoice(data.alignment, ['left', 'center', 'right'], 'left')}`,
        `program-builder-section--space-${safeChoice(data.spacing, ['compact', 'normal', 'spacious'], 'normal')}`,
        'fade-in'
    ].join(' ');
    const content = {
        hero: renderSectionHero,
        content: renderSectionContent,
        progress: renderSectionProgress,
        gallery: renderSectionGallery,
        impact: renderSectionImpact,
        cta: renderSectionCta,
        faq: renderSectionFaq
    }[type]?.(data, program) || '';
    if (!content) return '';
    return `<section class="${classes}" data-program-section="${escapeHtml(section.key || `${type}-${index}`)}">${content}</section>`;
}

function renderSectionHeading(data, defaultTitle = '') {
    const title = data.title || defaultTitle;
    const body = multilineHtml(data.body || '');
    return `<div class="program-section-heading">
        ${data.eyebrow ? `<span class="program-section-eyebrow">${escapeHtml(data.eyebrow)}</span>` : ''}
        ${title ? `<h2>${escapeHtml(title)}</h2>` : ''}
        ${data.subtitle ? `<p class="program-section-subtitle">${escapeHtml(data.subtitle)}</p>` : ''}
        ${body ? `<div class="program-section-copy">${body}</div>` : ''}
    </div>`;
}

function renderSectionHero(data, program) {
    if (!data.media_url && !data.title && !data.subtitle && !data.body && !data.eyebrow && !data.button_label) return '';
    const media = sectionMediaHtml(data.media_type, data.media_url, data.media_alt || program.title, {
        mobileUrl: data.mobile_media_url,
        poster: data.poster_url,
        background: true
    });
    const buttonUrl = safeSectionHref(data.button_url);
    const wholeUrl = safeSectionHref(data.whole_link);
    const button = data.button_label && buttonUrl
        ? `<a class="program-section-button" href="${escapeHtml(buttonUrl)}"${externalLinkAttrs(buttonUrl)}>${escapeHtml(data.button_label)} <span aria-hidden="true">→</span></a>`
        : '';
    const height = safeChoice(data.height, ['compact', 'medium', 'screen'], 'screen');
    const overlay = Math.max(0, Math.min(80, Number(data.overlay) || 0)) / 100;
    const hero = `<div class="program-section-hero program-section-hero--${height}" style="--program-hero-overlay:${overlay}">
        <div class="program-section-hero__media">${media}</div>
        <div class="program-section-hero__overlay"></div>
        <div class="program-section-inner"><div class="program-section-hero__content">
            ${data.eyebrow ? `<span class="program-section-eyebrow">${escapeHtml(data.eyebrow)}</span>` : ''}
            ${data.title ? `<h1>${escapeHtml(data.title)}</h1>` : ''}
            ${data.subtitle ? `<p class="program-section-subtitle">${escapeHtml(data.subtitle)}</p>` : ''}
            ${data.body ? `<div class="program-section-copy">${multilineHtml(data.body)}</div>` : ''}
            ${button}
        </div></div>
        ${wholeUrl && !button ? `<a class="program-section-hero__click-layer" href="${escapeHtml(wholeUrl)}"${externalLinkAttrs(wholeUrl)} aria-label="${escapeHtml(data.title || program.title)}"></a>` : ''}
    </div>`;
    return hero;
}

function renderSectionContent(data, program) {
    if (!data.media_url && !data.title && !data.subtitle && !data.body && !data.eyebrow) return '';
    const position = safeChoice(data.media_position, ['top', 'bottom', 'left', 'right', 'background'], 'top');
    const ratio = safeChoice(data.media_ratio, ['natural', 'landscape', 'square', 'portrait'], 'landscape');
    const mediaHtml = data.media_type !== 'none' && data.media_url
        ? `<figure class="program-section-media program-section-media--${ratio}">${linkedMedia(sectionMediaHtml(data.media_type, data.media_url, data.media_alt || data.title || program.title), data.media_link)}${data.caption ? `<figcaption>${escapeHtml(data.caption)}</figcaption>` : ''}</figure>`
        : '';
    const text = `<div class="program-section-content__text">${renderSectionHeading(data)}</div>`;
    if (position === 'background' && mediaHtml) {
        return `<div class="program-section-content program-section-content--background"><div class="program-section-content__background">${sectionMediaHtml(data.media_type, data.media_url, data.media_alt || '', { background: true })}</div><div class="program-section-content__shade"></div><div class="program-section-inner">${text}</div></div>`;
    }
    return `<div class="program-section-inner"><div class="program-section-content program-section-content--${position}">${position === 'bottom' || position === 'right' ? `${text}${mediaHtml}` : `${mediaHtml}${text}`}</div></div>`;
}

function renderSectionProgress(data, program) {
    if (!data.title && !data.subtitle && !data.body && !data.eyebrow && !Number(data.target) && !Number(data.collected) && !Number(data.donors) && !data.deadline && !data.button_label) return '';
    const target = Math.max(0, Number(data.target) || 0);
    const collected = Math.max(0, Number(data.collected) || 0);
    const remaining = Math.max(0, target - collected);
    const percentage = target > 0 ? Math.round((collected / target) * 1000) / 10 : 0;
    const visualPercentage = Math.max(0, Math.min(100, percentage));
    const href = safeSectionHref(data.button_url);
    return `<div class="program-section-inner"><div class="program-progress-card">
        ${renderSectionHeading(data, 'Perjalanan Kebaikan Kita')}
        ${data.show_percentage !== false ? `<div class="program-progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="${visualPercentage}"><span style="width:${visualPercentage}%"></span></div><strong class="program-progress-percent">${formatNumber(percentage)}%</strong>` : ''}
        ${data.show_amounts !== false ? `<div class="program-progress-metrics"><div><span>Terkumpul</span><strong>${formatCurrency(collected)}</strong></div><div><span>Target</span><strong>${formatCurrency(target)}</strong></div><div><span>Masih dibutuhkan</span><strong>${formatCurrency(remaining)}</strong></div>${Number(data.donors) > 0 ? `<div><span>Donatur</span><strong>${formatNumber(data.donors)}</strong></div>` : ''}${data.deadline ? `<div><span>Batas waktu</span><strong>${formatDate(data.deadline)}</strong></div>` : ''}</div>` : ''}
        ${data.button_label && href ? `<a class="program-section-button" href="${escapeHtml(href)}"${externalLinkAttrs(href)}>${escapeHtml(data.button_label)} <span aria-hidden="true">→</span></a>` : ''}
    </div></div>`;
}

function renderSectionGallery(data, program) {
    const items = Array.isArray(data.items) ? data.items.filter(item => item?.url) : [];
    if (!items.length && !data.title && !data.body && !data.subtitle) return '';
    const layout = safeChoice(data.layout, ['single', 'grid-2', 'grid-3', 'featured', 'mosaic', 'carousel'], 'grid-2');
    const figures = items.map((item, index) => {
        const figure = `<figure class="program-gallery-item">${sectionMediaHtml(item.type, item.url, item.alt || `${program.title} ${index + 1}`)}${item.caption ? `<figcaption>${escapeHtml(item.caption)}</figcaption>` : ''}</figure>`;
        return linkedMedia(figure, item.link);
    }).join('');
    return `<div class="program-section-inner">${renderSectionHeading(data)}<div class="program-gallery program-gallery--${layout}">${figures}</div></div>`;
}

function renderSectionImpact(data) {
    const items = Array.isArray(data.items) ? data.items : [];
    if (!items.length && !data.title && !data.subtitle && !data.body && !data.eyebrow) return '';
    const columns = Math.max(2, Math.min(4, Number(data.columns) || 3));
    return `<div class="program-section-inner">${renderSectionHeading(data)}${items.length ? `<div class="program-impact-grid" style="--impact-columns:${columns}">${items.map(item => `<article class="program-impact-card">${item.value ? `<strong>${escapeHtml(item.value)}</strong>` : ''}${item.label ? `<h3>${escapeHtml(item.label)}</h3>` : ''}${item.note ? `<p>${escapeHtml(item.note)}</p>` : ''}</article>`).join('')}</div>` : ''}</div>`;
}

function renderSectionCta(data, program) {
    const wa = data.whatsapp_number || program.whatsapp_number || '6285121277046';
    const message = data.whatsapp_message || program.whatsapp_message || `Assalamualaikum, saya ingin berkonsultasi mengenai ${program.title}.`;
    const customUrl = safeSectionHref(data.button_url);
    const actionUrl = customUrl || whatsappLink(wa, message);
    const qrImage = safeMediaHref(data.qr_image);
    const showQr = data.show_qr !== false && qrImage;
    const showWa = data.show_whatsapp !== false;
    return `<div class="program-section-inner"><div class="program-cta-card">
        ${renderSectionHeading(data, `Ingin Berkontribusi untuk ${program.title}?`)}
        <div class="program-cta-methods${showQr ? ' has-qr' : ''}">
            ${showQr ? `<div class="program-cta-qr"><span class="program-section-eyebrow">Scan Donasi</span><h3>QR/Barcode Resmi</h3><a href="${escapeHtml(qrImage)}" target="_blank" rel="noopener noreferrer"><span class="program-cta-qr__glass"><img src="${escapeHtml(qrImage)}" alt="QR atau barcode donasi ${escapeHtml(program.title)}" loading="lazy"></span></a><small>Ketuk gambar untuk memperbesar.</small></div>` : ''}
            ${showWa ? `<div class="program-cta-contact"><span class="program-section-eyebrow">WhatsApp Resmi</span><h3>Konsultasi Donasi</h3><p>Hubungi admin untuk panduan penyaluran dan konfirmasi donasi.</p><strong>${escapeHtml(whatsappDisplay(wa))}</strong>${data.button_label ? `<a class="program-section-button" href="${escapeHtml(actionUrl)}"${externalLinkAttrs(actionUrl)}>${escapeHtml(data.button_label)} <span aria-hidden="true">→</span></a>` : ''}</div>` : ''}
        </div>
    </div></div>`;
}

function renderSectionFaq(data) {
    const items = Array.isArray(data.items) ? data.items : [];
    if (!items.length && !data.title && !data.subtitle && !data.body && !data.eyebrow) return '';
    return `<div class="program-section-inner program-faq">${renderSectionHeading(data, 'Pertanyaan yang Sering Ditanyakan')}<div class="program-faq-list">${items.map((item, index) => `<details${index === 0 ? ' open' : ''}><summary>${escapeHtml(item.question || 'Pertanyaan')}</summary><div>${multilineHtml(item.answer || '')}</div></details>`).join('')}</div></div>`;
}

function sectionMediaHtml(type, url, alt = '', options = {}) {
    const safeUrl = safeMediaHref(url);
    if (!safeUrl) return '';
    const mediaType = safeChoice(type, ['image', 'video', 'youtube', 'drive'], 'image');
    if (mediaType === 'video' || mediaType === 'drive') {
        const source = mediaType === 'drive' ? driveDirectUrl(safeUrl) : safeUrl;
        if (!source) return '';
        const shouldAutoplay = options.background && !matchMedia('(prefers-reduced-motion: reduce)').matches;
        const backgroundAttrs = options.background ? ` ${shouldAutoplay ? 'autoplay ' : ''}muted loop playsinline tabindex="-1" aria-hidden="true"` : ' controls playsinline';
        return `<video src="${escapeHtml(source)}"${options.poster ? ` poster="${escapeHtml(safeMediaHref(options.poster))}"` : ''}${backgroundAttrs} preload="metadata"></video>`;
    }
    if (mediaType === 'youtube') {
        const id = youtubeVideoId(safeUrl);
        if (!id) return '';
        const autoplay = options.background && !matchMedia('(prefers-reduced-motion: reduce)').matches ? 1 : 0;
        const query = options.background ? `autoplay=${autoplay}&mute=1&loop=1&playlist=${id}&controls=0&rel=0&playsinline=1` : 'rel=0&playsinline=1';
        return `<iframe src="https://www.youtube-nocookie.com/embed/${id}?${query}" title="${escapeHtml(alt || 'Video program')}" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen loading="lazy"></iframe>`;
    }
    const mobile = safeMediaHref(options.mobileUrl);
    return `<picture>${mobile ? `<source media="(max-width: 767px)" srcset="${escapeHtml(mobile)}">` : ''}<img src="${escapeHtml(safeUrl)}" alt="${escapeHtml(alt)}" loading="${options.background ? 'eager' : 'lazy'}"></picture>`;
}

function linkedMedia(html, url) {
    const href = safeSectionHref(url);
    return href ? `<a href="${escapeHtml(href)}"${externalLinkAttrs(href)}>${html}</a>` : html;
}

function multilineHtml(value) {
    return String(value || '').split(/\n{2,}/).map(part => part.trim()).filter(Boolean).map(part => `<p>${escapeHtml(part).replace(/\n/g, '<br>')}</p>`).join('');
}

function safeChoice(value, allowed, fallback) {
    return allowed.includes(String(value || '')) ? String(value) : fallback;
}

function safeSectionHref(value) {
    const url = String(value || '').trim();
    return /^(https:\/\/|\/|#)/i.test(url) ? url : '';
}

function safeMediaHref(value) {
    const url = String(value || '').trim();
    return /^(https:\/\/|\/uploads\/)/i.test(url) ? url : '';
}

function externalLinkAttrs(url) {
    return /^https:\/\//i.test(url) ? ' target="_blank" rel="noopener noreferrer"' : '';
}

function whatsappLink(number, message) {
    const digits = String(number || '').replace(/\D+/g, '') || '6285121277046';
    return `https://wa.me/${encodeURIComponent(digits)}?text=${encodeURIComponent(message || '')}`;
}

function driveDirectUrl(url) {
    const id = driveVideoId(url);
    return id ? `https://drive.google.com/uc?export=download&id=${encodeURIComponent(id)}` : '';
}

function formatCurrency(value) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value) || 0);
}

function formatNumber(value) {
    return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 1 }).format(Number(value) || 0);
}

function formatDate(value) {
    const date = new Date(`${value}T00:00:00`);
    return Number.isNaN(date.getTime()) ? escapeHtml(value) : new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }).format(date);
}

function removeEmptyContentLines(content) {
    if (!content) return;

    content.querySelectorAll('p, div').forEach(block => {
        const text = (block.textContent || '').replace(/\u00a0/g, '').trim();
        const hasVisibleContent = block.querySelector('img, video, iframe, svg, hr, table');
        if (!text && !hasVisibleContent) block.remove();
    });
}

function programNavbarHtml() {
    return `
    <header class="main-header">
        <div class="container">
            <nav class="navbar" aria-label="Navigasi utama">
                <a class="logo" href="/" aria-label="Dompet Dana Umat - Beranda">
                    <img src="/asset/logo-dompet-dana-umat-256.png" alt="Logo Dompet Dana Umat Daarul Uluum" width="256" height="258">
                    <span>Dompet Dana Umat</span>
                </a>
                <button class="menu-toggle" type="button" aria-label="Buka menu" aria-expanded="false">
                    <span class="bar"></span><span class="bar"></span><span class="bar"></span>
                </button>
                <div class="nav-links">
                    <button class="close-menu-btn" type="button" aria-label="Tutup menu">&times;</button>
                    <a href="/">Home</a>
                    <a href="/#blog">Artikel</a>
                    <a href="/#about">About</a>
                    <a href="/#programs" class="active" aria-current="page">Program</a>
                    <a href="/#calculator">Kalkulator</a>
                    <a href="/#contact">Contact</a>
                    <div class="mobile-socials">
                        <span class="mobile-socials__label">Ikuti Kami</span>
                        <a href="https://www.facebook.com/dompetdanaumat" class="mobile-social-link mobile-social-link--facebook" aria-label="Facebook" target="_blank" rel="noopener noreferrer"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M9 8H6v4h3v12h5V12h3.642L18 8h-4V6.333C14 5.378 14.192 5 15.115 5H18V0h-3.808C10.596 0 9 1.583 9 4.615V8z"/></svg></a>
                        <a href="https://www.instagram.com/dompetdanaumat?utm_source=ig_web_button_share_sheet&amp;igsh=ZDNlZDc0MzIxNw==" class="mobile-social-link mobile-social-link--instagram" aria-label="Instagram" target="_blank" rel="noopener noreferrer"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0 3.675A6.162 6.162 0 1 0 12 18.162 6.162 6.162 0 0 0 12 5.838zm0 10.162a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 1 0 2.881 1.44 1.44 0 0 1 0-2.881z"/></svg></a>
                        <a href="https://www.tiktok.com/@istana.keberkahan" class="mobile-social-link mobile-social-link--tiktok" aria-label="TikTok" target="_blank" rel="noopener noreferrer"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 1 1-2.01-2.77V9.4a6.84 6.84 0 1 0 5.5 6.27v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1.04-.1z"/></svg></a>
                        <a href="https://www.youtube.com/@ZakatInfaqSedekahWakaf" class="mobile-social-link mobile-social-link--youtube" aria-label="YouTube" target="_blank" rel="noopener noreferrer"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.017 3.017 0 0 0 2.121 2.136c1.872.505 9.377.505 9.377.505s7.505 0 9.376-.505a3.016 3.016 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg></a>
                    </div>
                </div>
            </nav>
        </div>
    </header>`;
}

function programHeroMediaHtml(program, sliderImages) {
    const type = ['images', 'video', 'youtube', 'drive'].includes(program.hero_media_type)
        ? program.hero_media_type
        : 'images';
    if (type === 'images' || !program.hero_video_url) {
        return detailSliderHtml(sliderImages, program.title, 'detail-hero-slider', program.image_alt || program.title);
    }

    const fallbackUrl = sliderImages[0] || program.image || '';
    const fallback = fallbackUrl
        ? `<div class="program-hero-media-fallback"><img src="${escapeHtml(fallbackUrl)}" alt="" aria-hidden="true"></div>`
        : '';
    const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (type === 'video') {
        const source = localVideoUrl(program.hero_video_url);
        if (!source) return fallback;
        return `${fallback}<video class="program-hero-video" src="${escapeHtml(source)}" ${reducedMotion ? '' : 'autoplay'} muted loop playsinline preload="metadata" poster="${escapeHtml(fallbackUrl)}" aria-hidden="true"></video>`;
    }
    if (type === 'youtube') {
        const id = youtubeVideoId(program.hero_video_url);
        if (!id) return fallback;
        const autoplay = reducedMotion ? '0' : '1';
        const source = `https://www.youtube-nocookie.com/embed/${id}?autoplay=${autoplay}&mute=1&loop=1&playlist=${id}&controls=0&rel=0&playsinline=1&disablekb=1`;
        return `${fallback}<iframe class="program-hero-video program-hero-video-embed" src="${source}" title="Video latar ${escapeHtml(program.title)}" allow="autoplay; encrypted-media; picture-in-picture" referrerpolicy="strict-origin-when-cross-origin" tabindex="-1" aria-hidden="true"></iframe>`;
    }
    const driveId = driveVideoId(program.hero_video_url);
    if (!driveId) return fallback;
    const source = `https://drive.google.com/uc?export=download&id=${encodeURIComponent(driveId)}`;
    return `${fallback}<video class="program-hero-video program-hero-drive-video" src="${source}" ${reducedMotion ? '' : 'autoplay'} muted loop playsinline preload="metadata" poster="${escapeHtml(fallbackUrl)}" aria-hidden="true"></video>`;
}

function localVideoUrl(url) {
    const value = String(url || '');
    return /^\/uploads\/videos\/[a-f0-9]{32}\.(?:mp4|webm)$/i.test(value) ? value : '';
}

function youtubeVideoId(url) {
    try {
        const parsed = new URL(url);
        const host = parsed.hostname.toLowerCase().replace(/^www\./, '');
        let id = '';
        if (host === 'youtu.be') {
            id = parsed.pathname.split('/').filter(Boolean)[0] || '';
        } else if (['youtube.com', 'm.youtube.com', 'youtube-nocookie.com'].includes(host)) {
            if (parsed.pathname === '/watch') id = parsed.searchParams.get('v') || '';
            else id = parsed.pathname.match(/^\/(?:embed|shorts|live)\/([A-Za-z0-9_-]{11})/)?.[1] || '';
        }
        return /^[A-Za-z0-9_-]{11}$/.test(id) ? id : '';
    } catch (error) {
        return '';
    }
}

function driveVideoId(url) {
    try {
        const parsed = new URL(url);
        if (parsed.hostname.toLowerCase().replace(/^www\./, '') !== 'drive.google.com') return '';
        const id = parsed.pathname.match(/\/file\/d\/([A-Za-z0-9_-]{10,})/)?.[1]
            || parsed.searchParams.get('id')
            || '';
        return /^[A-Za-z0-9_-]{10,}$/.test(id) ? id : '';
    } catch (error) {
        return '';
    }
}

function donationQrUrl(value) {
    const url = String(value || '');
    return /^\/uploads\/qrcodes\/[a-f0-9]{32}\.png$/i.test(url) ? url : '';
}

function whatsappDisplay(value) {
    const number = String(value || '').replace(/\D+/g, '');
    return number ? `+${number}` : '';
}

function initProgramHeroVideo(container) {
    container.querySelectorAll('video.program-hero-video').forEach(video => {
        video.addEventListener('playing', () => video.classList.add('is-playing'));
        video.addEventListener('error', () => video.classList.add('media-failed'));
        if (!matchMedia('(prefers-reduced-motion: reduce)').matches) {
            video.play().catch(() => video.classList.add('media-failed'));
        }
    });
}

function setupPage() {
    const header = document.querySelector('.main-header');
    const menu = document.querySelector('.menu-toggle');
    const links = document.querySelector('.nav-links');
    const top = document.querySelector('.back-to-top');
    const closeMenu = () => {
        links?.classList.remove('active');
        menu?.classList.remove('active');
        header?.classList.remove('menu-open');
        document.body.classList.remove('mobile-menu-open');
        menu?.setAttribute('aria-expanded', 'false');
    };
    menu?.addEventListener('click', () => {
        const opening = !links?.classList.contains('active');
        links?.classList.toggle('active', opening);
        menu.classList.toggle('active', opening);
        header?.classList.toggle('menu-open', opening);
        document.body.classList.toggle('mobile-menu-open', opening);
        menu.setAttribute('aria-expanded', String(opening));
    });
    links?.querySelector('.close-menu-btn')?.addEventListener('click', closeMenu);
    links?.querySelectorAll('a').forEach(link => link.addEventListener('click', closeMenu));
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') closeMenu();
    });
    window.addEventListener('scroll', () => { header?.classList.toggle('scrolled', scrollY > 50); top?.classList.toggle('visible', scrollY > 300); });
    top?.addEventListener('click', event => { event.preventDefault(); scrollTo({ top: 0, behavior: 'smooth' }); });
    const observer = new IntersectionObserver(entries => entries.forEach(entry => entry.isIntersecting && entry.target.classList.add('visible')), { threshold: .1 });
    document.querySelectorAll('.fade-in').forEach(element => observer.observe(element));
}

function showError(container) {
    if (container) container.innerHTML = '<div style="padding:200px 20px;text-align:center"><h2>Program tidak ditemukan</h2><a href="index.html">Kembali</a></div>';
}
