const anonymousId = (storage, key) => {
    try {
        let value = storage.getItem(key);
        if (!value) {
            value = globalThis.crypto?.randomUUID?.()
                || `${Date.now().toString(36)}-${Math.random().toString(36).slice(2)}-${Math.random().toString(36).slice(2)}`;
            storage.setItem(key, value);
        }
        return value;
    } catch {
        return '';
    }
};

const analyticsAllowed = navigator.doNotTrack !== '1' && navigator.globalPrivacyControl !== true;
const visitorId = analyticsAllowed ? anonymousId(localStorage, 'ddu_anonymous_visitor') : '';
const visitSessionId = analyticsAllowed ? anonymousId(sessionStorage, 'ddu_visit_session') : '';

const recordStat = type => analyticsAllowed ? fetch('../api/index.php?resource=stats', {
    method: 'POST',
    credentials: 'same-origin',
    keepalive: true,
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        type,
        page: window.location.pathname,
        referrer: document.referrer,
        screen_width: Math.round(window.screen?.width || window.innerWidth || 0),
        visitor_id: visitorId,
        session_id: visitSessionId
    })
}).catch(() => {}) : Promise.resolve();

const normalizeOfficialWhatsapp = value => {
    let digits = String(value || '').replace(/\D+/g, '');
    if (digits.startsWith('00')) digits = digits.slice(2);
    if (digits.startsWith('0')) digits = `62${digits.slice(1)}`;
    else if (digits.startsWith('8')) digits = `62${digits}`;
    return /^\d{10,15}$/.test(digits) ? digits : '';
};

const loadOfficialWhatsapp = async () => {
    try {
        const response = await fetch('/api/index.php?resource=institution', {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' }
        });
        if (!response.ok) return;
        const result = await response.json();
        const number = normalizeOfficialWhatsapp(result?.data?.official_phone);
        if (!number) return;

        document.querySelectorAll('[data-official-whatsapp]').forEach(link => {
            const message = link.dataset.whatsappMessage || 'Assalamualaikum, saya ingin memperoleh informasi dari Dompet Dana Umat.';
            link.href = `https://wa.me/${number}?text=${encodeURIComponent(message)}`;
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
        });

        const contactForm = document.querySelector('.contact-form');
        if (contactForm) contactForm.dataset.whatsapp = number;
    } catch (error) {
        console.error('Nomor WhatsApp resmi belum dapat dimuat.', error);
    }
};

document.addEventListener('DOMContentLoaded', () => {
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let pageNavigationStarted = false;
    loadOfficialWhatsapp();

    window.addEventListener('pageshow', () => {
        pageNavigationStarted = false;
        document.body.classList.remove('page-leaving');
    });

    document.addEventListener('click', event => {
        const link = event.target.closest('a[href]');
        if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        if (link.hasAttribute('download') || (link.target && link.target.toLowerCase() !== '_self')) return;

        const destination = new URL(link.href, window.location.href);
        if (!/^https?:$/.test(destination.protocol) || destination.origin !== window.location.origin) return;
        if (destination.pathname === window.location.pathname && destination.search === window.location.search && destination.hash) return;
        if (reducedMotion || pageNavigationStarted) return;

        event.preventDefault();
        pageNavigationStarted = true;
        document.body.classList.add('page-leaving');
        window.setTimeout(() => window.location.assign(destination.href), 360);
    });

    const isPublicHome = /^\/(?:halaman-utama\/)?index\.html$/i.test(window.location.pathname);
    if (window.location.protocol.startsWith('http') && (isPublicHome || window.location.hash === '#')) {
        const cleanPath = isPublicHome ? '/' : window.location.pathname;
        window.history.replaceState(null, '', `${cleanPath}${window.location.search}`);
    }

    const pageVisitKey = `ddu_visit_${window.location.pathname}`;
    if (analyticsAllowed && !sessionStorage.getItem(pageVisitKey)) {
        recordStat('visit');
        sessionStorage.setItem(pageVisitKey, '1');
    }
    document.addEventListener('click', event => {
        if (event.target.closest('.whatsapp-popup, .btn-whatsapp-minimal, [href*="wa.me/"], [href*="whatsapp.com/"]')) {
            recordStat('wa_click');
        }
    });

    const header = document.querySelector('.main-header');
    const backToTop = document.querySelector('.back-to-top');
    window.addEventListener('scroll', () => {
        header?.classList.toggle('scrolled', window.scrollY > 50);
        backToTop?.classList.toggle('visible', window.scrollY > 300);
    });
    backToTop?.addEventListener('click', event => { event.preventDefault(); window.scrollTo({ top: 0, behavior: 'smooth' }); });

    const toggle = document.querySelector('.menu-toggle');
    const links = document.querySelector('.nav-links');
    const close = () => {
        links?.classList.remove('active');
        toggle?.classList.remove('active');
        header?.classList.remove('menu-open');
        document.body.classList.remove('mobile-menu-open');
        toggle?.setAttribute('aria-expanded', 'false');
    };
    toggle?.addEventListener('click', () => {
        const opening = !links?.classList.contains('active');
        links?.classList.toggle('active', opening);
        toggle.classList.toggle('active', opening);
        header?.classList.toggle('menu-open', opening);
        document.body.classList.toggle('mobile-menu-open', opening);
        toggle.setAttribute('aria-expanded', String(opening));
    });
    document.querySelector('.close-menu-btn')?.addEventListener('click', close);
    links?.querySelectorAll('a').forEach(link => link.addEventListener('click', close));

    document.querySelectorAll('a[href^="#"]').forEach(link => {
        link.addEventListener('click', event => {
            event.preventDefault();
            const targetId = link.getAttribute('href')?.slice(1);
            if (targetId) document.getElementById(targetId)?.scrollIntoView({ behavior: 'smooth' });
            if (window.location.protocol.startsWith('http')) {
                window.history.replaceState(null, '', `${window.location.pathname === '/index.html' ? '/' : window.location.pathname}${window.location.search}`);
            }
        });
    });

    const contactForm = document.querySelector('.contact-form');
    contactForm?.addEventListener('submit', event => {
        event.preventDefault();
        const whatsappNumber = contactForm.dataset.whatsapp || '';
        if (!/^\d{10,15}$/.test(whatsappNumber)) {
            console.error('Nomor WhatsApp pada form Hubungi Kami tidak valid.');
            return;
        }
        const name = document.getElementById('contact-name')?.value || '';
        const email = document.getElementById('contact-email')?.value || '';
        const message = document.getElementById('contact-message')?.value || '';
        const text = `Halo Admin Dompet Dana Umat,\n\nNama: ${name}\nEmail: ${email}\n\nPesan:\n${message}`;
        window.open(`https://wa.me/${whatsappNumber}?text=${encodeURIComponent(text)}`, '_blank', 'noopener');
    });
});
