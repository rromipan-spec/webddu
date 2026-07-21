import { detailSliderHtml, initDetailSliders, recordSliderImages } from './detail-slider.js?v=20260721-2';

const API = '../api/index.php';
const SITE_NAME = 'Dompet Dana Umat';
const DEFAULT_IMAGE = 'https://lh3.googleusercontent.com/d/1G2xVlrBuc4IU6ynaGf8Xg_c9Y7jSg3Wm';
const escapeHtml = (value = '') => String(value).replace(/[&<>'"]/g, character => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'
}[character]));
const dateText = value => {
    const date = new Date(String(value || '').replace(' ', 'T'));
    return Number.isNaN(date.getTime()) ? '' : date.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
};

document.addEventListener('DOMContentLoaded', async () => {
    const container = document.getElementById('post-container');
    const querySlug = new URLSearchParams(location.search).get('slug');
    const routeSlug = location.pathname.match(/^\/artikel\/([a-z0-9]+(?:-[a-z0-9]+)*)\/?$/i)?.[1] || '';
    const slug = querySlug || routeSlug;
    if (!container || !slug || !/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(slug)) return showError(container);
    try {
        const [postResponse, relatedResponse] = await Promise.all([
            fetch(`${API}?resource=posts&slug=${encodeURIComponent(slug)}`, { credentials: 'same-origin' }),
            fetch(`${API}?resource=posts&exclude=${encodeURIComponent(slug)}&limit=3`, { credentials: 'same-origin' })
        ]);
        const postResult = await postResponse.json();
        const relatedResult = await relatedResponse.json();
        if (!postResponse.ok || !postResult.data) throw new Error(postResult.message || 'Artikel tidak ditemukan.');
        renderPost(container, postResult.data, relatedResult.data || []);
    } catch (error) {
        console.error(error);
        showError(container);
    }
});

function plainText(html = '') {
    const temporary = document.createElement('div');
    temporary.innerHTML = html;
    return (temporary.textContent || '').replace(/\s+/g, ' ').trim();
}

function readingTime(content) {
    const words = plainText(content).split(/\s+/).filter(Boolean).length;
    return Math.max(1, Math.ceil(words / 200));
}

function articleSummary(post) {
    const source = String(post.excerpt || '').trim() || plainText(post.content).slice(0, 500);
    const sentences = source.match(/[^.!?]+[.!?]?/g) || [source];
    return sentences.map(sentence => sentence.trim()).filter(Boolean).slice(0, 3);
}

function articleHeroImages(post) {
    let images = [];
    if (Array.isArray(post.hero_images)) {
        images = post.hero_images;
    } else {
        try {
            const parsed = JSON.parse(post.hero_images || '[]');
            if (Array.isArray(parsed)) images = parsed;
        } catch (error) {
            images = [];
        }
    }
    images = images.map(value => String(value || '').trim()).filter(Boolean);
    if (post.hero_image && !images.includes(post.hero_image)) images.unshift(post.hero_image);
    return [...new Set(images)].slice(0, 10);
}

function relatedArticlesHtml(related) {
    if (!related.length) return '';
    return `<section class="related-section" aria-labelledby="related-title">
        <div class="post-section-heading"><span>LANJUT MEMBACA</span><h2 id="related-title">Artikel Terkait</h2></div>
        <div class="related-grid">${related.map(item => `
            <article class="related-card">
                <a href="/artikel/${encodeURIComponent(item.slug)}" class="related-img-wrapper"><img src="${escapeHtml(item.image || DEFAULT_IMAGE)}" alt="${escapeHtml(item.title)}" loading="lazy"></a>
                <div class="related-content">
                    <time class="related-date" datetime="${escapeHtml(String(item.created_at || '').slice(0, 10))}">${escapeHtml(dateText(item.created_at))}</time>
                    <h3><a href="/artikel/${encodeURIComponent(item.slug)}">${escapeHtml(item.title)}</a></h3>
                    <p>${escapeHtml(item.excerpt || 'Baca informasi dan kabar kebaikan selengkapnya.')}</p>
                    <a class="related-read-more" href="/artikel/${encodeURIComponent(item.slug)}">Baca selengkapnya <span aria-hidden="true">→</span></a>
                </div>
            </article>`).join('')}</div>
    </section>`;
}

function shareButtonsHtml(previewUrl, title, canonicalUrl = previewUrl) {
    const text = `${title} - ${SITE_NAME}`;
    return `<aside class="post-share" aria-label="Bagikan artikel">
        <span>Bagikan</span>
        <div class="post-share-buttons">
            <a href="https://wa.me/?text=${encodeURIComponent(`${text} ${previewUrl}`)}" target="_blank" rel="noopener noreferrer" aria-label="Bagikan ke WhatsApp">WA</a>
            <a href="https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(previewUrl)}" target="_blank" rel="noopener noreferrer" aria-label="Bagikan ke Facebook">f</a>
            <button type="button" data-copy-article-url="${escapeHtml(canonicalUrl)}" aria-label="Salin tautan artikel">🔗</button>
        </div>
        <span class="post-share-status" aria-live="polite"></span>
    </aside>`;
}

function renderPost(container, post, related) {
    const wa = post.whatsapp_number || '6285121277046';
    const message = post.whatsapp_message || `Assalamualaikum, saya ingin berdonasi setelah membaca artikel: ${post.title}.`;
    const whatsappUrl = `https://wa.me/${encodeURIComponent(wa)}?text=${encodeURIComponent(message)}`;
    const sliderImages = recordSliderImages(post);
    const heroImages = articleHeroImages(post);
    const minutes = readingTime(post.content);
    const canonicalUrl = `${location.origin}/artikel/${encodeURIComponent(post.slug)}`;
    const updatedAt = new Date(String(post.updated_at || post.created_at || '').replace(' ', 'T')).getTime();
    const previewUrl = `${canonicalUrl}?v=${Number.isNaN(updatedAt) ? Date.now() : updatedAt}`;
    const summary = articleSummary(post);
    const summaryHtml = summary.length ? `<section class="post-summary" aria-labelledby="summary-title"><span>RINGKASAN ARTIKEL</span><h2 id="summary-title">Yang perlu Anda ketahui</h2><ul>${summary.map(point => `<li>${escapeHtml(point)}</li>`).join('')}</ul></section>` : '';

    updateSeo(post, post.image || sliderImages[0] || heroImages[0] || DEFAULT_IMAGE, canonicalUrl, minutes);
    container.innerHTML = `
        <section class="post-hero">
            ${heroImages.map((image, index) => `<img class="post-hero-background${index === 0 ? ' active' : ''}" src="${escapeHtml(image)}" alt="" aria-hidden="true"${index > 0 ? ' loading="lazy"' : ''}>`).join('')}
            <div class="post-hero-overlay" aria-hidden="true"></div>
            <div class="container post-hero-inner">
                <nav class="post-breadcrumb" aria-label="Breadcrumb"><a href="/">Beranda</a><span aria-hidden="true">›</span><a href="/#blog">Artikel</a><span aria-hidden="true">›</span><span aria-current="page">${escapeHtml(post.title)}</span></nav>
                <span class="post-category">Artikel DDU</span>
                <h1>${escapeHtml(post.title)}</h1>
                <div class="post-hero-meta">
                    <span>Oleh <strong>${SITE_NAME}</strong></span><span aria-hidden="true">•</span>
                    <time datetime="${escapeHtml(String(post.created_at || '').slice(0, 10))}">${escapeHtml(dateText(post.created_at))}</time><span aria-hidden="true">•</span>
                    <span>${minutes} menit baca</span>
                </div>
            </div>
            ${heroImages.length > 1 ? `<div class="post-hero-slider-dots" aria-label="Pilih background header">${heroImages.map((image, index) => `<button type="button" class="${index === 0 ? 'active' : ''}" data-hero-slide="${index}" aria-label="Tampilkan background ${index + 1}"></button>`).join('')}</div>` : ''}
        </section>
        <div class="container post-page-layout">
            <div class="post-reading-column">
                <article class="post-full">
                    ${detailSliderHtml(sliderImages, post.title)}
                    ${summaryHtml}
                    <div class="post-full-content">${post.content}</div>
                    ${shareButtonsHtml(previewUrl, post.title, canonicalUrl)}
                </article>
                <section class="cta-minimal post-donation-cta" aria-labelledby="donation-title">
                    <div class="post-donation-icon" aria-hidden="true">♥</div>
                    <span>LANGKAH KEBAIKAN</span>
                    <h2 id="donation-title">Mari Lanjutkan Kebaikan dengan Berdonasi</h2>
                    <p>Salurkan donasi terbaik Anda melalui layanan resmi Dompet Dana Umat. Tim kami siap membantu proses donasi melalui WhatsApp.</p>
                    <div class="post-donation-trust"><span>✓ Amanah</span><span>✓ Mudah</span><span>✓ Terarah</span></div>
                    <a href="${whatsappUrl}" class="btn-whatsapp-minimal" target="_blank" rel="noopener noreferrer">Berdonasi via WhatsApp <span aria-hidden="true">→</span></a>
                </section>
                ${relatedArticlesHtml(related)}
            </div>
        </div>
        <a href="${whatsappUrl}" class="whatsapp-popup" target="_blank" rel="noopener noreferrer" aria-label="Berdonasi melalui WhatsApp"><img src="https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg" alt="WhatsApp"></a>`;

    initDetailSliders(container);
    initPostHeroSlider(container);
    setupShareButtons(container, previewUrl, post.title, canonicalUrl);
}

function initPostHeroSlider(container) {
    const hero = container.querySelector('.post-hero');
    const slides = Array.from(hero?.querySelectorAll('.post-hero-background') || []);
    const dots = Array.from(hero?.querySelectorAll('[data-hero-slide]') || []);
    if (slides.length < 2) return;
    let current = 0;
    let timer = null;
    const show = index => {
        current = (index + slides.length) % slides.length;
        slides.forEach((slide, slideIndex) => slide.classList.toggle('active', slideIndex === current));
        dots.forEach((dot, dotIndex) => dot.classList.toggle('active', dotIndex === current));
    };
    const start = () => {
        if (timer) window.clearInterval(timer);
        if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            timer = window.setInterval(() => show(current + 1), 5000);
        }
    };
    dots.forEach((dot, index) => dot.addEventListener('click', () => { show(index); start(); }));
    hero.addEventListener('mouseenter', () => { if (timer) window.clearInterval(timer); });
    hero.addEventListener('mouseleave', start);
    start();
}

function setupShareButtons(container, previewUrl, title, canonicalUrl) {
    container.querySelector('[data-copy-article-url]')?.addEventListener('click', async event => {
        const status = container.querySelector('.post-share-status');
        try {
            await navigator.clipboard.writeText(canonicalUrl);
            if (status) status.textContent = 'Tautan tersalin.';
        } catch (error) {
            window.prompt('Salin tautan artikel:', canonicalUrl);
        }
    });

    if (navigator.share) {
        const share = document.createElement('button');
        share.type = 'button';
        share.className = 'post-native-share';
        share.textContent = 'Bagikan';
        share.addEventListener('click', () => navigator.share({ title, text: `${title} - ${SITE_NAME}`, url: previewUrl }).catch(() => {}));
        container.querySelector('.post-share-buttons')?.appendChild(share);
    }
}

function setMeta(selector, attribute, value) {
    let element = document.head.querySelector(selector);
    if (!element) {
        element = document.createElement('meta');
        const property = selector.match(/meta\[(name|property)="([^"]+)"\]/);
        if (property) element.setAttribute(property[1], property[2]);
        document.head.appendChild(element);
    }
    element.setAttribute(attribute, value);
}

function updateSeo(post, image, canonicalUrl, minutes) {
    const description = String(post.excerpt || plainText(post.content).slice(0, 160)).trim().slice(0, 160);
    const absoluteImage = new URL(image, location.origin).href;
    document.title = `${post.title} - ${SITE_NAME}`;
    setMeta('meta[name="description"]', 'content', description);
    setMeta('meta[name="author"]', 'content', SITE_NAME);
    setMeta('meta[property="og:type"]', 'content', 'article');
    setMeta('meta[property="og:site_name"]', 'content', SITE_NAME);
    setMeta('meta[property="og:locale"]', 'content', 'id_ID');
    setMeta('meta[property="og:title"]', 'content', post.title);
    setMeta('meta[property="og:description"]', 'content', description);
    setMeta('meta[property="og:url"]', 'content', canonicalUrl);
    setMeta('meta[property="og:image"]', 'content', absoluteImage);
    setMeta('meta[property="article:published_time"]', 'content', post.created_at || '');
    setMeta('meta[property="article:modified_time"]', 'content', post.updated_at || post.created_at || '');
    setMeta('meta[property="article:author"]', 'content', SITE_NAME);
    setMeta('meta[name="twitter:card"]', 'content', 'summary_large_image');
    setMeta('meta[name="twitter:title"]', 'content', post.title);
    setMeta('meta[name="twitter:description"]', 'content', description);
    setMeta('meta[name="twitter:image"]', 'content', absoluteImage);

    let canonical = document.head.querySelector('link[rel="canonical"]');
    if (!canonical) {
        canonical = document.createElement('link');
        canonical.rel = 'canonical';
        document.head.appendChild(canonical);
    }
    canonical.href = canonicalUrl;

    document.getElementById('article-structured-data')?.remove();
    const structuredData = document.createElement('script');
    structuredData.id = 'article-structured-data';
    structuredData.type = 'application/ld+json';
    structuredData.textContent = JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'Article',
        headline: post.title,
        description,
        image: [absoluteImage],
        datePublished: post.created_at,
        dateModified: post.updated_at || post.created_at,
        mainEntityOfPage: canonicalUrl,
        timeRequired: `PT${minutes}M`,
        author: { '@type': 'Organization', name: SITE_NAME, url: location.origin },
        publisher: { '@type': 'Organization', name: SITE_NAME, logo: { '@type': 'ImageObject', url: DEFAULT_IMAGE } }
    });
    document.head.appendChild(structuredData);
}

function showError(container) {
    if (!container) return;
    document.title = `Artikel Tidak Ditemukan - ${SITE_NAME}`;
    container.innerHTML = '<div class="post-error"><h1>Artikel Tidak Ditemukan</h1><p>Artikel yang Anda cari tidak ada atau alamatnya tidak valid.</p><a href="/#blog" class="btn btn-primary">Kembali ke Artikel</a></div>';
}
