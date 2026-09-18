(function () {
    'use strict';

    const hero = document.getElementById('home');
    const slider = document.getElementById('homepage-hero-slider');
    const mobileQuery = window.matchMedia('(max-width: 768px)');
    if (!hero || !slider) return;

    let rotationTimer = 0;
    let currentSettings = null;
    let activeDestinations = [];
    let activeButtonLabels = [];
    let activeShowButtons = [];
    let hasTextContent = false;
    let readyTimer = 0;

    function normalizeImages(value) {
        return Array.isArray(value)
            ? [...new Set(value.filter(item => typeof item === 'string' && item.trim()).map(item => item.trim()))].slice(0, 3)
            : [];
    }

    function normalizeLinks(value, count, fallback = '') {
        const links = Array.isArray(value) ? value : [];
        return Array.from({ length: count }, (_, index) => safeDestinationUrl(links[index] || fallback));
    }

    function normalizeButtonLabels(value, count, fallback = '') {
        const labels = Array.isArray(value) ? value : [];
        return Array.from({ length: count }, (_, index) => String(labels[index] ?? fallback).trim());
    }

    function normalizeButtonModes(value, count, fallback = true) {
        const modes = Array.isArray(value) ? value : [];
        return Array.from({ length: count }, (_, index) => {
            const mode = modes[index] ?? fallback;
            return mode !== false && mode !== '0';
        });
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

    function applyLinkAttributes(element, linkUrl) {
        element.href = linkUrl;
        if (isWhatsappUrl(linkUrl)) {
            element.target = '_blank';
            element.rel = 'noopener noreferrer';
        } else {
            element.removeAttribute('target');
            element.removeAttribute('rel');
        }
    }

    function createSlide(url, index, linkUrl, linkLabel, makePhotoLink) {
        const linked = Boolean(makePhotoLink && linkUrl);
        const slide = document.createElement(linked ? 'a' : 'div');
        slide.className = `slide${index === 0 ? ' is-active' : ''}${linked ? ' is-linked' : ''}`;
        slide.dataset.slideIndex = String(index);

        if (linked) {
            applyLinkAttributes(slide, linkUrl);
            slide.setAttribute('aria-label', linkLabel);
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

    function updateActiveAction(index) {
        const destination = activeDestinations[index] || '';
        const buttonLabel = activeButtonLabels[index] || '';
        const showButton = activeShowButtons[index] !== false;
        const button = document.getElementById('homepage-hero-button');
        const hasButton = Boolean(showButton && button && buttonLabel && destination);

        if (button) {
            button.textContent = buttonLabel;
            button.hidden = !hasButton;
            if (hasButton) applyLinkAttributes(button, destination);
            else {
                button.href = '#';
                button.removeAttribute('target');
                button.removeAttribute('rel');
            }
        }

        const content = hero.querySelector('.hero-content');
        if (content) content.hidden = !(hasTextContent || hasButton);
        hero.classList.toggle('has-photo-link', !showButton && Boolean(destination));
    }

    function startRotation() {
        stopRotation();
        const slides = Array.from(slider.querySelectorAll('.slide'));
        slides.forEach((slide, index) => slide.classList.toggle('is-active', index === 0));
        updateActiveAction(0);
        if (slides.length < 2 || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

        let activeIndex = 0;
        rotationTimer = window.setInterval(() => {
            const nextIndex = (activeIndex + 1) % slides.length;
            slides[nextIndex].classList.add('is-active');
            slides[activeIndex].classList.remove('is-active');
            activeIndex = nextIndex;
            updateActiveAction(activeIndex);
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
        const useMobile = mobileQuery.matches && mobileImages.length;
        const activeImages = useMobile ? mobileImages : desktopImages;
        const linksValue = useMobile ? settings.mobile_links : settings.desktop_links;
        const labelsValue = useMobile ? settings.mobile_button_labels : settings.desktop_button_labels;
        const modesValue = useMobile ? settings.mobile_show_buttons : settings.desktop_show_buttons;
        const hasPerImageLinks = Array.isArray(linksValue);
        activeDestinations = normalizeLinks(linksValue, activeImages.length, hasPerImageLinks ? '' : settings.button_url);
        activeButtonLabels = normalizeButtonLabels(labelsValue, activeImages.length, settings.button_label || '');
        activeShowButtons = normalizeButtonModes(
            modesValue,
            activeImages.length,
            settings.show_button !== false && settings.show_button !== '0'
        );

        stopRotation();
        slider.replaceChildren(...activeImages.map((url, index) => createSlide(
            url,
            index,
            activeDestinations[index],
            String(activeButtonLabels[index] || settings.title || 'Buka halaman tujuan').trim(),
            !activeShowButtons[index]
        )));
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
        hasTextContent = hasKicker || hasTitle || hasDescription;
        return renderSlides(settings);
    }

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
