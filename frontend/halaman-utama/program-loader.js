import { siteFooterHtml } from './site-footer.js';
import { detailSliderHtml, initDetailSliders, recordSliderImages } from './detail-slider.js?v=20260721-2';

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
        const response = await fetch(`../api/index.php?resource=programs&slug=${encodeURIComponent(slug)}`, { credentials: 'same-origin' });
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
    document.title = `${program.title} - Dompet Dana Umat`;
    const wa = program.whatsapp_number || '6285121277046';
    const message = program.whatsapp_message || `Assalamualaikum, saya ingin berkonsultasi mengenai ${program.title}.`;
    const sliderImages = recordSliderImages(program);
    const isWakaf = program.slug === 'wakaf-asrama-santri' || /\bwakaf\b/i.test(program.title);
    const actionLabel = isWakaf ? 'Wakaf Sekarang →' : 'Hubungi Admin via WhatsApp →';
    const actionText = isWakaf
        ? 'Salurkan wakaf terbaik Anda dan jadilah bagian dari perjuangan para santri.'
        : 'Konsultasikan donasi Anda secara amanah bersama tim layanan kami.';
    container.innerHTML = `
    <header class="main-header"><div class="container"><nav class="navbar"><div class="logo"><img src="https://lh3.googleusercontent.com/d/1G2xVlrBuc4IU6ynaGf8Xg_c9Y7jSg3Wm" alt="Logo DDU"><span>Dompet Dana Umat</span></div><div class="menu-toggle"><span class="bar"></span><span class="bar"></span><span class="bar"></span></div><div class="nav-links"><div class="close-menu-btn">&times;</div><a href="index.html">Home</a><a href="about.html">About</a><a href="index.html#programs" class="active">Program</a><a href="index.html#blog">Artikel</a><a href="index.html#contact">Contact</a></div></nav></div></header>
    <section class="hero detail-program-hero" style="padding:220px 0 120px">${detailSliderHtml(sliderImages, program.title, 'detail-hero-slider')}<div class="hero-overlay" style="background:rgba(10,38,71,.8)"></div><div class="container hero-container" style="text-align:center"><div class="hero-content" style="max-width:100%;margin:0 auto"><h5 style="color:#64b5f6;letter-spacing:2px">LAYANAN DDU</h5><h1 style="color:white;margin-top:10px">${escapeHtml(program.hero_title || program.title)}</h1><p style="color:#cbd5e0">${escapeHtml(program.hero_subtitle)}</p></div></div></section>
    <section class="container fade-in" style="padding:80px 20px"><div style="max-width:850px;margin:0 auto;line-height:1.8" class="mock-content">${program.content || '<p>Konten belum tersedia.</p>'}</div></section>
    <section class="cta-minimal fade-in"><div class="container"><h5>LANGKAH KEBAIKAN</h5><h2>Ingin Berkontribusi untuk ${escapeHtml(program.title)}?</h2><p>${escapeHtml(actionText)}</p><a href="https://wa.me/${encodeURIComponent(wa)}?text=${encodeURIComponent(message)}" class="btn-whatsapp-minimal" target="_blank" rel="noopener noreferrer">${escapeHtml(actionLabel)}</a></div></section>
    ${siteFooterHtml()}
    <a href="#" class="back-to-top">↑</a><a href="https://wa.me/${encodeURIComponent(wa)}" class="whatsapp-popup" target="_blank" rel="noopener noreferrer"><img src="https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg" alt="WhatsApp"></a>`;
    initDetailSliders(container);
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
