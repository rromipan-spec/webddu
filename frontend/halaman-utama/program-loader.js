import { siteFooterHtml } from './site-footer.js?v=20260722-1';
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
    } finally {
        document.querySelector('.preloader')?.classList.add('hidden');
    }
});

function renderProgram(container, program) {
    document.title = `${program.seo_title || program.title} - Dompet Dana Umat`;
    const wa = program.whatsapp_number || '6285121277046';
    const message = program.whatsapp_message || `Assalamualaikum, saya ingin berkonsultasi mengenai ${program.title}.`;
    const sliderImages = recordSliderImages(program);
    const isWakaf = program.slug === 'wakaf-asrama-santri' || /\bwakaf\b/i.test(program.title);
    const actionLabel = isWakaf ? 'Wakaf Sekarang →' : 'Hubungi Admin via WhatsApp →';
    const actionText = isWakaf
        ? 'Salurkan wakaf terbaik Anda dan jadilah bagian dari perjuangan para santri.'
        : 'Konsultasikan donasi Anda secara amanah bersama tim layanan kami.';
    const heroMedia = programHeroMediaHtml(program, sliderImages);
    container.innerHTML = `
    <header class="main-header"><div class="container"><nav class="navbar"><div class="logo"><img src="/asset/logo-dompet-dana-umat-256.png" alt="Logo DDU" width="256" height="258"><span>Dompet Dana Umat</span></div><div class="menu-toggle"><span class="bar"></span><span class="bar"></span><span class="bar"></span></div><div class="nav-links"><div class="close-menu-btn">&times;</div><a href="index.html">Home</a><a href="about.html">About</a><a href="index.html#programs" class="active">Program</a><a href="index.html#blog">Artikel</a><a href="index.html#contact">Contact</a></div></nav></div></header>
    <section class="hero detail-program-hero" style="padding:220px 0 120px">${heroMedia}<div class="hero-overlay" style="background:rgba(10,38,71,.8)"></div><div class="container hero-container" style="text-align:center"><div class="hero-content" style="max-width:100%;margin:0 auto"><span class="section-kicker">${escapeHtml(program.category || 'LAYANAN DDU')}</span><h1 style="color:white;margin-top:10px">${escapeHtml(program.hero_title || program.title)}</h1><p style="color:#cbd5e0">${escapeHtml(program.hero_subtitle)}</p></div></div></section>
    <section class="container fade-in" style="padding:80px 20px"><div style="max-width:850px;margin:0 auto;line-height:1.8" class="mock-content">${program.content || '<p>Konten belum tersedia.</p>'}</div></section>
    <section class="cta-minimal fade-in"><div class="container"><span class="section-kicker">Langkah Kebaikan</span><h2>Ingin Berkontribusi untuk ${escapeHtml(program.title)}?</h2><p>${escapeHtml(actionText)}</p><a href="https://wa.me/${encodeURIComponent(wa)}?text=${encodeURIComponent(message)}" class="btn-whatsapp-minimal" target="_blank" rel="noopener noreferrer">${escapeHtml(actionLabel)}</a></div></section>
    ${siteFooterHtml()}
    <a href="#" class="back-to-top">↑</a><a href="https://wa.me/${encodeURIComponent(wa)}" class="whatsapp-popup" target="_blank" rel="noopener noreferrer" aria-label="Hubungi WhatsApp"><img src="/asset/whatsapp-phone.svg" alt="" width="24" height="24"></a>`;
    initDetailSliders(container);
    initProgramHeroVideo(container);
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
    menu?.addEventListener('click', () => { links?.classList.toggle('active'); menu.classList.toggle('active'); header?.classList.toggle('menu-open'); });
    window.addEventListener('scroll', () => { header?.classList.toggle('scrolled', scrollY > 50); top?.classList.toggle('visible', scrollY > 300); });
    top?.addEventListener('click', event => { event.preventDefault(); scrollTo({ top: 0, behavior: 'smooth' }); });
    const observer = new IntersectionObserver(entries => entries.forEach(entry => entry.isIntersecting && entry.target.classList.add('visible')), { threshold: .1 });
    document.querySelectorAll('.fade-in').forEach(element => observer.observe(element));
}

function showError(container) {
    document.querySelector('.preloader')?.classList.add('hidden');
    if (container) container.innerHTML = '<div style="padding:200px 20px;text-align:center"><h2>Program tidak ditemukan</h2><a href="index.html">Kembali</a></div>';
}
