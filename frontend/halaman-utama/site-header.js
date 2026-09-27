const socialLinks = `
    <div class="mobile-socials">
        <span class="mobile-socials__label">Ikuti Kami</span>
        <a href="https://www.facebook.com/dompetdanaumat" class="mobile-social-link mobile-social-link--facebook" aria-label="Facebook" target="_blank" rel="noopener noreferrer"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M9 8H6v4h3v12h5V12h3.642L18 8h-4V6.333C14 5.378 14.192 5 15.115 5H18V0h-3.808C10.596 0 9 1.583 9 4.615V8z"/></svg></a>
        <a href="https://www.instagram.com/dompetdanaumat?utm_source=ig_web_button_share_sheet&amp;igsh=ZDNlZDc0MzIxNw==" class="mobile-social-link mobile-social-link--instagram" aria-label="Instagram" target="_blank" rel="noopener noreferrer"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0 3.675A6.162 6.162 0 1 0 12 18.162 6.162 6.162 0 0 0 12 5.838zm0 10.162a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 1 0 2.881 1.44 1.44 0 0 1 0-2.881z"/></svg></a>
        <a href="https://www.tiktok.com/@istana.keberkahan" class="mobile-social-link mobile-social-link--tiktok" aria-label="TikTok" target="_blank" rel="noopener noreferrer"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 1 1-2.01-2.77V9.4a6.84 6.84 0 1 0 5.5 6.27v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1.04-.1z"/></svg></a>
        <a href="https://www.youtube.com/@ZakatInfaqSedekahWakaf" class="mobile-social-link mobile-social-link--youtube" aria-label="YouTube" target="_blank" rel="noopener noreferrer"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.017 3.017 0 0 0 2.121 2.136c1.872.505 9.377.505 9.377.505s7.505 0 9.376-.505a3.016 3.016 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg></a>
    </div>`;

function activeNavigation() {
    const path = window.location.pathname.toLowerCase();
    if (document.body.classList.contains('article-page') || path.startsWith('/artikel/')) return 'article';
    if (document.body.classList.contains('about-page') || path.endsWith('/about.html')) return 'about';
    if (document.body.classList.contains('program-page') || document.body.classList.contains('program-section-page')) return 'program';
    return path === '/' || path.endsWith('/index.html') ? 'home' : '';
}

function navigationLink(href, label, key, active) {
    const current = key === active;
    return `<a href="${href}"${current ? ' class="active" aria-current="page"' : ''}>${label}</a>`;
}

export function siteHeaderHtml() {
    const active = activeNavigation();
    const solid = document.body.classList.contains('home-page') ? '' : ' main-header--solid';
    return `<header class="main-header${solid}" data-site-header>
        <div class="utility-bar" aria-label="Layanan donasi cepat">
            <div class="utility-bar__inner">
                <span class="utility-bar__message">Kanal resmi kebaikan Dompet Dana Umat</span>
                <nav class="utility-bar__links" aria-label="Tautan layanan donasi">
                    <a href="/transparansi.html#kanal-resmi" class="utility-bar__link">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10h18M5 10v8m4-8v8m6-8v8m4-8v8M3 18h18M12 3l9 5H3l9-5Z"/></svg>
                        <span>Rekening Donasi</span>
                    </a>
                    <a href="https://wa.me/6285121277046?text=Assalamualaikum%2C%20saya%20ingin%20mengonfirmasi%20donasi%20kepada%20Dompet%20Dana%20Umat." class="utility-bar__link" data-official-whatsapp data-whatsapp-message="Assalamualaikum, saya ingin mengonfirmasi donasi kepada Dompet Dana Umat." data-analytics-cta="konfirmasi-donasi-topbar" target="_blank" rel="noopener noreferrer">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.4 8.4 0 0 1-9 8.5 9.5 9.5 0 0 1-4-.9L3 21l1.8-4.8A8.5 8.5 0 1 1 21 11.5Z"/><path d="M8.3 8.1c.3 2.4 2.1 4.3 4.5 4.8m.2 0 1.4-1.1 2 1.1-.3 2c-.1.6-.7 1-1.3.9-4.5-.8-7.9-4.2-8.7-8.7-.1-.6.3-1.2.9-1.3l2-.3 1.1 2-1.1 1.4"/></svg>
                        <span>Konfirmasi Donasi</span>
                    </a>
                </nav>
            </div>
        </div>
        <div class="container">
            <nav class="navbar" aria-label="Navigasi utama">
                <a class="logo" href="/" aria-label="Dompet Dana Umat - Beranda">
                    <img src="/asset/logo-dompet-dana-umat-256.png" alt="Logo Dompet Dana Umat Daarul Uluum" width="256" height="258">
                    <span>Dompet Dana Umat</span>
                </a>
                <button class="menu-toggle" type="button" aria-label="Buka menu navigasi" aria-controls="primary-navigation" aria-expanded="false"><span class="bar"></span><span class="bar"></span><span class="bar"></span></button>
                <div class="nav-links" id="primary-navigation">
                    <button class="close-menu-btn" type="button" aria-label="Tutup menu navigasi">&times;</button>
                    ${navigationLink('/', 'Home', 'home', active)}
                    ${navigationLink('/#blog', 'Artikel', 'article', active)}
                    ${navigationLink('/about.html', 'About', 'about', active)}
                    ${navigationLink('/#programs', 'Program', 'program', active)}
                    ${navigationLink('/#calculator', 'Kalkulator', 'calculator', active)}
                    ${navigationLink('/#contact', 'Contact', 'contact', active)}
                    ${socialLinks}
                </div>
            </nav>
        </div>
    </header>`;
}

export function mountSiteHeader() {
    const template = document.createElement('template');
    template.innerHTML = siteHeaderHtml().trim();
    const header = template.content.firstElementChild;
    const current = document.querySelector('.main-header, [data-site-header]');
    if (current) current.replaceWith(header);
    else document.body.prepend(header);
    return header;
}
