const mobileQuery = window.matchMedia('(max-width: 768px)');
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

const setupProgress = () => {
    const root = document.documentElement;
    let scheduled = false;

    const update = () => {
        const distance = root.scrollHeight - window.innerHeight;
        const progress = distance > 0 ? Math.min(1, Math.max(0, window.scrollY / distance)) : 0;
        root.style.setProperty('--page-progress', progress.toFixed(4));
        scheduled = false;
    };

    const requestUpdate = () => {
        if (scheduled) return;
        scheduled = true;
        window.requestAnimationFrame(update);
    };

    update();
    window.addEventListener('scroll', requestUpdate, { passive: true });
    window.addEventListener('resize', requestUpdate, { passive: true });
};

const setupDeferredVideo = () => {
    const frame = document.querySelector('.video-wrapper iframe[data-src]');
    if (!frame) return;

    const source = frame.dataset.src;
    const load = () => {
        if (!source || frame.hasAttribute('src')) return;
        frame.src = source;
        frame.removeAttribute('data-src');
    };

    if (navigator.connection?.saveData) return;
    if (!('IntersectionObserver' in window)) {
        load();
        return;
    }

    const observer = new IntersectionObserver(entries => {
        if (!entries.some(entry => entry.isIntersecting)) return;
        load();
        observer.disconnect();
    }, { rootMargin: '350px 0px' });

    observer.observe(frame);
};

const setupNavigationState = () => {
    const toggle = document.querySelector('.menu-toggle');
    const links = [...document.querySelectorAll('.nav-links > a[href^="#"]')];
    const sections = links
        .map(link => document.querySelector(link.getAttribute('href')))
        .filter(Boolean);

    if (toggle) {
        const syncLabel = () => {
            const open = toggle.getAttribute('aria-expanded') === 'true';
            toggle.setAttribute('aria-label', open ? 'Tutup menu navigasi' : 'Buka menu navigasi');
        };
        new MutationObserver(syncLabel).observe(toggle, { attributes: true, attributeFilter: ['aria-expanded'] });
        syncLabel();
    }

    if (!('IntersectionObserver' in window) || !sections.length) return;
    const observer = new IntersectionObserver(entries => {
        const visible = entries
            .filter(entry => entry.isIntersecting)
            .sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];
        if (!visible) return;
        links.forEach(link => {
            const active = link.getAttribute('href') === `#${visible.target.id}`;
            link.classList.toggle('active', active);
            if (active) link.setAttribute('aria-current', 'location');
            else link.removeAttribute('aria-current');
        });
    }, { rootMargin: '-25% 0px -62%', threshold: [0, .2, .5] });

    sections.forEach(section => observer.observe(section));
};

const setupSwipeHints = () => {
    document.querySelectorAll('.mobile-swipe-hint').forEach(hint => {
        const grid = hint.nextElementSibling;
        if (!grid) return;
        const sync = () => {
            const hasMultipleCards = grid.children.length > 1;
            hint.classList.toggle('is-hidden', !hasMultipleCards);
        };
        new MutationObserver(sync).observe(grid, { childList: true });
        sync();
    });
};

document.addEventListener('DOMContentLoaded', () => {
    setupDeferredVideo();
    setupNavigationState();
    setupSwipeHints();
    if (mobileQuery.matches) setupProgress();

    if (!reducedMotion) {
        document.querySelectorAll('.gallery-grid, .blog-grid').forEach(grid => {
            grid.style.scrollBehavior = 'smooth';
        });
    }
});
