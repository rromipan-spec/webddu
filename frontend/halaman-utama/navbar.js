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
const analyticsSession = (() => {
    if (!analyticsAllowed) return { id: '', landingPath: '/', utm: {} };
    const key = 'ddu_analytics_session_v2';
    const now = Date.now();
    const params = new URLSearchParams(location.search);
    try {
        const stored = JSON.parse(localStorage.getItem(key) || 'null');
        if (stored?.id && now - Number(stored.lastActivity || 0) < 30 * 60 * 1000) {
            stored.lastActivity = now;
            localStorage.setItem(key, JSON.stringify(stored));
            return stored;
        }
        const session = {
            id: globalThis.crypto?.randomUUID?.() || `${now.toString(36)}-${Math.random().toString(36).slice(2)}`,
            lastActivity: now,
            pageViews: 0,
            landingPath: location.pathname,
            utm: {
                source: params.get('utm_source') || '', medium: params.get('utm_medium') || '',
                campaign: params.get('utm_campaign') || '', content: params.get('utm_content') || ''
            }
        };
        localStorage.setItem(key, JSON.stringify(session));
        return session;
    } catch { return { id: anonymousId(sessionStorage, 'ddu_visit_session'), landingPath: location.pathname, utm: {} }; }
})();
if (analyticsAllowed) {
    analyticsSession.pageViews = Number(analyticsSession.pageViews || 0) + 1;
    try { localStorage.setItem('ddu_analytics_session_v2', JSON.stringify(analyticsSession)); } catch {}
}

const recordStat = (type, details = {}) => analyticsAllowed ? fetch('../api/index.php?resource=stats', {
    method: 'POST',
    credentials: 'same-origin',
    keepalive: true,
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        ...details,
        event_id: globalThis.crypto?.randomUUID?.() || '',
        type,
        page: window.location.pathname,
        referrer: document.referrer,
        screen_width: Math.round(window.screen?.width || window.innerWidth || 0),
        visitor_id: visitorId,
        session_id: analyticsSession.id,
        landing_path: analyticsSession.landingPath,
        utm_source: analyticsSession.utm?.source || '',
        utm_medium: analyticsSession.utm?.medium || '',
        utm_campaign: analyticsSession.utm?.campaign || '',
        utm_content: analyticsSession.utm?.content || ''
    })
}).catch(() => {}) : Promise.resolve();

window.dduAnalytics = { track: recordStat };

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

    recordStat('page_view');
    if (/^\/artikel\//.test(location.pathname) || (!location.pathname.includes('.') && location.pathname !== '/' && !location.pathname.startsWith('/admin'))) {
        recordStat('content_view');
    }
    document.addEventListener('click', event => {
        const whatsapp = event.target.closest('.whatsapp-popup, .btn-whatsapp-minimal, [href*="wa.me/"], [href*="whatsapp.com/"]');
        if (whatsapp) {
            recordStat('wa_click', { cta_id: whatsapp.dataset.analyticsCta || whatsapp.id || whatsapp.className || 'whatsapp' });
        }
        const heroCta = event.target.closest('.hero-cta, [data-hero-cta]');
        if (heroCta) recordStat('hero_cta_click', { cta_id: heroCta.dataset.analyticsCta || 'hero' });
    });
    document.addEventListener('submit', event => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (form.matches('.zakat-form, #zakat-form, [data-zakat-calculator]')) recordStat('calculator_submit', { cta_id: form.id || 'zakat-calculator' });
        if (form.matches('.contact-form, #contact-form')) recordStat('contact_submit', { cta_id: form.id || 'contact-form' });
    });
    window.addEventListener('error', event => {
        recordStat('client_error', { event_label: String(event.error?.name || 'JavaScriptError').slice(0, 120) });
    });

    let activeSince = Date.now();
    let activeMs = 0;
    let maxScroll = 0;
    let engagementSent = false;
    let lastSessionTouch = 0;
    const updateEngagement = (finalize = false) => {
        if (!document.hidden || finalize) {
            activeMs += Date.now() - activeSince;
            activeSince = Date.now();
        }
        const scrollable = Math.max(1, document.documentElement.scrollHeight - innerHeight);
        maxScroll = Math.max(maxScroll, Math.min(100, Math.round(scrollY / scrollable * 100)));
        if (Date.now() - lastSessionTouch > 10000) {
            lastSessionTouch = Date.now();
            analyticsSession.lastActivity = lastSessionTouch;
            try { localStorage.setItem('ddu_analytics_session_v2', JSON.stringify(analyticsSession)); } catch {}
        }
        if (!engagementSent && (activeMs >= 10000 || maxScroll >= 50 || analyticsSession.pageViews >= 2)) {
            engagementSent = true;
            recordStat('engaged_view', { engagement_ms: activeMs, scroll_depth: maxScroll });
        }
    };
    updateEngagement();
    window.addEventListener('scroll', updateEngagement, { passive: true });
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) updateEngagement(true); else activeSince = Date.now();
    });
    window.setInterval(updateEngagement, 5000);
    window.addEventListener('pagehide', () => {
        updateEngagement(true);
        recordStat('page_engagement', { engagement_ms: activeMs, scroll_depth: maxScroll });
    }, { once: true });

    // Sampel 20% cukup untuk tren Core Web Vitals tanpa membebani database.
    if (analyticsAllowed && visitorId.charCodeAt(0) % 5 === 0 && 'PerformanceObserver' in window) {
        const vitals = { LCP: 0, INP: 0, CLS: 0 };
        try { new PerformanceObserver(list => { for (const item of list.getEntries()) vitals.LCP = item.startTime; }).observe({ type: 'largest-contentful-paint', buffered: true }); } catch {}
        try { new PerformanceObserver(list => { for (const item of list.getEntries()) if (!item.hadRecentInput) vitals.CLS += item.value; }).observe({ type: 'layout-shift', buffered: true }); } catch {}
        try { new PerformanceObserver(list => { for (const item of list.getEntries()) vitals.INP = Math.max(vitals.INP, item.duration || 0); }).observe({ type: 'event', buffered: true, durationThreshold: 40 }); } catch {}
        window.addEventListener('pagehide', () => Object.entries(vitals).forEach(([metric_name, metric_value]) => {
            if (metric_value > 0) recordStat('web_vital', { metric_name, metric_value });
        }), { once: true });
    }

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
