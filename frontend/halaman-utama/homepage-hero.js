(function () {
    'use strict';

    const hero = document.getElementById('home');
    const slider = document.getElementById('homepage-hero-slider');
    const mobileQuery = window.matchMedia('(max-width: 768px)');
    if (!hero || !slider) return;

    let rotationTimer = 0;
    let currentSettings = null;
    let destinationUrl = '';
    let readyTimer = 0;

    function normalizeImages(value) {
        return Array.isArray(value)
            ? [...new Set(value.filter(item => typeof item === 'string' && item.trim()).map(item => item.trim()))].slice(0, 3)
            : [];
    }

    function safeDestinationUrl(value) {
        if (typeof value !== 'string' || !value.trim()) return '';
        try {
            const resolved = new URL(value.trim(), window.location.href);
            return ['http:', 'https:'].includes(resolved.protocol) ? resolved.href : '';
        } catch (error) {
            return '';
        }
    }

    function isWhatsappUrl(value) {
        try {
            const host = new URL(value, window.location.href).hostname.toLowerCase();
            return host === 'wa.me' || host === 'api.whatsapp.com' || host === 'www.whatsapp.com' || host === 'whatsapp.com';
        } catch (error) {
            return false;
        }
    }

    function createSlide(url, index, linkUrl, linkLabel) {
        const slide = document.createElement(linkUrl ? 'a' : 'div');
        slide.className = `slide${index === 0 ? ' is-active' : ''}${linkUrl ? ' is-linked' : ''}`;

        if (linkUrl) {
            slide.href = linkUrl;
            slide.setAttribute('aria-label', linkLabel);
            if (isWhatsappUrl(linkUrl)) {
                slide.target = '_blank';
                slide.rel = 'noopener noreferrer';
            }
        }

        const picture = document.createElement('picture');
        const image = document.createElement('img');
        image.src = url;
        image.alt = '';
        image.decoding = 'async';
        image.loading = index === 0 ? 'eager' : 'lazy';
        if (index === 0) image.fetchPriority = 'high';
        picture.appendChild(image);

        slide.append(picture);
        return slide;
    }

    function stopRotation() {
        window.clearInterval(rotationTimer);
        rotationTimer = 0;
    }

    function startRotation() {
        stopRotation();
        const slides = Array.from(slider.querySelectorAll('.slide'));
        slides.forEach((slide, index) => slide.classList.toggle('is-active', index === 0));
        if (slides.length < 2 || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

        let activeIndex = 0;
        rotationTimer = window.setInterval(() => {
            const nextIndex = (activeIndex + 1) % slides.length;
            slides[nextIndex].classList.add('is-active');
            slides[activeIndex].classList.remove('is-active');
            activeIndex = nextIndex;
        }, 6000);
    }

    function setOptionalContent(id, value) {
        const element = document.getElementById(id);
        if (!element) return false;
        const text = typeof value === 'string' ? value.trim() : '';
        element.textContent = text;
        element.hidden = text === '';
        return text !== '';
    }

    function renderSlides(settings) {
        const desktopImages = normalizeImages(settings.desktop_images);
        const mobileImages = normalizeImages(settings.mobile_images);
        const activeImages = mobileQuery.matches && mobileImages.length ? mobileImages : desktopImages;
        const label = String(settings.button_label || settings.title || 'Buka halaman tujuan').trim();

        stopRotation();
        slider.replaceChildren(...activeImages.map((url, index) => createSlide(url, index, destinationUrl, label)));
        startRotation();
        return slider.querySelector('.slide.is-active img');
    }

    function markHeroReady(image) {
        window.clearTimeout(readyTimer);
        let finished = false;
        const finish = () => {
            if (finished) return;
            finished = true;
            window.clearTimeout(readyTimer);
            hero.classList.remove('is-settings-pending');
            hero.removeAttribute('aria-busy');
        };

        if (!image || image.complete) {
            window.requestAnimationFrame(finish);
            return;
        }

        image.addEventListener('load', finish, { once: true });
        image.addEventListener('error', finish, { once: true });
        readyTimer = window.setTimeout(finish, 3000);
    }

    function applySettings(settings) {
        currentSettings = settings;
        const hasKicker = setOptionalContent('homepage-hero-kicker', settings.kicker);
        const hasTitle = setOptionalContent('homepage-hero-title', settings.title);
        const hasDescription = setOptionalContent('homepage-hero-description', settings.description);
        const button = document.getElementById('homepage-hero-button');
        const buttonLabel = typeof settings.button_label === 'string' ? settings.button_label.trim() : '';
        destinationUrl = safeDestinationUrl(settings.button_url);
        const hasButton = Boolean(button && buttonLabel && destinationUrl);

        if (button) {
            button.textContent = buttonLabel;
            button.href = hasButton ? destinationUrl : '#';
            button.hidden = !hasButton;
            if (hasButton && isWhatsappUrl(destinationUrl)) {
                button.target = '_blank';
                button.rel = 'noopener noreferrer';
            } else {
                button.removeAttribute('target');
                button.removeAttribute('rel');
            }
        }

        const content = hero.querySelector('.hero-content');
        if (content) content.hidden = !(hasKicker || hasTitle || hasDescription || hasButton);
        hero.classList.toggle('has-photo-link', hasButton);
        return renderSlides(settings);
    }

    hero.addEventListener('click', event => {
        if (!destinationUrl || event.target.closest('.hero-content')) return;
        if (event.target.closest('.slide.is-linked')) return;
        if (event.defaultPrevented || event.button > 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        window.location.assign(destinationUrl);
    });

    mobileQuery.addEventListener?.('change', () => {
        if (!currentSettings) return;
        hero.classList.add('is-settings-pending');
        hero.setAttribute('aria-busy', 'true');
        markHeroReady(renderSlides(currentSettings));
    });

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) stopRotation();
        else if (currentSettings) startRotation();
    });

    fetch('../api/index.php?resource=homepage', { credentials: 'same-origin', cache: 'no-store' })
        .then(response => response.ok ? response.json() : Promise.reject(new Error('Pengaturan hero tidak tersedia.')))
        .then(result => {
            if (!result?.ok || !result.data) throw new Error('Pengaturan hero tidak valid.');
            markHeroReady(applySettings(result.data));
        })
        .catch(() => {
            // Jangan munculkan konfigurasi lama ketika API tidak dapat dijangkau.
            markHeroReady(null);
        });
})();
