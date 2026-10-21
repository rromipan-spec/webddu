import { initManagedVideoAudio } from './video-audio.js?v=20261021-1';

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

const youtubeVideoId = value => {
    try {
        const url = new URL(String(value || '').trim());
        const host = url.hostname.toLowerCase().replace(/^www\./, '');
        if (host === 'youtu.be') return url.pathname.split('/').filter(Boolean)[0] || '';
        if (!['youtube.com', 'm.youtube.com', 'youtube-nocookie.com'].includes(host)) return '';
        if (url.pathname === '/watch') return url.searchParams.get('v') || '';
        return url.pathname.match(/^\/(?:embed|shorts|live)\/([A-Za-z0-9_-]{11})/)?.[1] || '';
    } catch {
        return '';
    }
};

const driveVideoId = value => {
    try {
        const url = new URL(String(value || '').trim());
        if (!['drive.google.com', 'www.drive.google.com'].includes(url.hostname.toLowerCase())) return '';
        return url.pathname.match(/\/file\/d\/([A-Za-z0-9_-]{10,})/)?.[1] || url.searchParams.get('id') || '';
    } catch {
        return '';
    }
};

const createHomepagePromoMedia = settings => {
    const type = String(settings?.promo_media_type || 'youtube');
    const url = String(settings?.promo_media_url || '').trim();
    const label = String(settings?.promo_media_alt || 'Media promo Dompet Dana Umat').trim();
    if (!url || !['image', 'video', 'youtube', 'drive'].includes(type)) return null;

    let media;
    if (type === 'image') {
        media = document.createElement('img');
        media.src = url;
        media.alt = label;
        media.loading = 'lazy';
        media.className = 'homepage-promo-image';
    } else if (type === 'video') {
        media = document.createElement('video');
        media.dataset.src = url;
        media.autoplay = true;
        media.defaultMuted = true;
        media.muted = true;
        media.loop = true;
        media.playsInline = true;
        media.preload = 'metadata';
        media.setAttribute('data-managed-background-video', '');
        media.setAttribute('aria-label', label);
    } else if (type === 'youtube') {
        const id = youtubeVideoId(url);
        if (!/^[A-Za-z0-9_-]{11}$/.test(id)) return null;
        media = document.createElement('iframe');
        media.dataset.src = `https://www.youtube-nocookie.com/embed/${id}?autoplay=1&mute=1&loop=1&playlist=${id}&controls=0&rel=0&playsinline=1&enablejsapi=1`;
        media.title = label;
        media.loading = 'lazy';
        media.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';
        media.allowFullscreen = true;
        media.setAttribute('data-managed-background-video', '');
    } else {
        const id = driveVideoId(url);
        if (!id) return null;
        media = document.createElement('iframe');
        media.dataset.src = `https://drive.google.com/file/d/${id}/preview`;
        media.title = label;
        media.loading = 'lazy';
        media.allow = 'autoplay';
        media.className = 'homepage-promo-drive';
    }
    media.setAttribute('data-homepage-promo-media', '');
    return media;
};

const setupDeferredVideo = async () => {
    const wrapper = document.querySelector('.video-wrapper');
    if (!wrapper) return;
    let media = wrapper.querySelector('[data-homepage-promo-media]');
    if (!media) return;

    try {
        const response = await fetch('../api/index.php?resource=homepage', {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' }
        });
        const result = await response.json();
        if (response.ok && result?.ok) {
            const replacement = createHomepagePromoMedia(result.data);
            if (replacement) {
                wrapper.querySelector(':scope > [data-media-sound-toggle]')?.remove();
                media.replaceWith(replacement);
                media = replacement;
            }
        }
    } catch (error) {
        console.warn('Media promo homepage memakai video bawaan.', error);
    }

    if (media instanceof HTMLImageElement) return;
    const source = media.dataset.src;
    const load = () => {
        if (!source || media.hasAttribute('src')) return;
        media.src = source;
        media.removeAttribute('data-src');
        if (media instanceof HTMLVideoElement) {
            media.load();
            media.play().catch(() => {});
        }
        if (media.hasAttribute('data-managed-background-video')) initManagedVideoAudio(document);
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

    observer.observe(media);
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
