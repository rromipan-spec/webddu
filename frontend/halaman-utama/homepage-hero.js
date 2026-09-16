(function () {
    'use strict';

    const slider = document.getElementById('homepage-hero-slider');
    if (!slider) return;

    let rotationTimer = 0;

    function normalizeImages(value) {
        return Array.isArray(value) ? [...new Set(value.filter(item => typeof item === 'string' && item.trim()))].slice(0, 3) : [];
    }

    function safeButtonUrl(value) {
        try {
            const resolved = new URL(String(value || ''), window.location.href);
            return ['http:', 'https:'].includes(resolved.protocol) ? resolved.href : 'about.html';
        } catch (error) {
            return 'about.html';
        }
    }

    function cssImageUrl(url) {
        return `url("${String(url).replace(/["\\\n\r]/g, '\\$&')}")`;
    }

    function createSlide(desktopUrl, mobileUrl, index) {
        const slide = document.createElement('div');
        slide.className = `slide${mobileUrl ? ' has-mobile-image' : ''}${index === 0 ? ' is-active' : ''}`;

        const backdrop = document.createElement('div');
        backdrop.className = 'slide-backdrop';
        backdrop.style.setProperty('--hero-desktop-backdrop', cssImageUrl(desktopUrl));
        backdrop.style.setProperty('--hero-mobile-backdrop', cssImageUrl(mobileUrl || desktopUrl));

        const picture = document.createElement('picture');
        if (mobileUrl) {
            const source = document.createElement('source');
            source.media = '(max-width: 768px)';
            source.srcset = mobileUrl;
            picture.appendChild(source);
        }
        const image = document.createElement('img');
        image.src = desktopUrl;
        image.alt = '';
        image.decoding = 'async';
        image.loading = index === 0 ? 'eager' : 'lazy';
        if (index === 0) image.fetchPriority = 'high';
        picture.appendChild(image);

        slide.append(backdrop, picture);
        return slide;
    }

    function startRotation() {
        window.clearInterval(rotationTimer);
        slider.classList.add('is-managed');
        const slides = Array.from(slider.querySelectorAll('.slide'));
        slides.forEach((slide, index) => slide.classList.toggle('is-active', index === 0));
        if (slides.length < 2 || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

        let activeIndex = 0;
        rotationTimer = window.setInterval(() => {
            slides[activeIndex].classList.remove('is-active');
            activeIndex = (activeIndex + 1) % slides.length;
            slides[activeIndex].classList.add('is-active');
        }, 6000);
    }

    function applySettings(settings) {
        const desktopImages = normalizeImages(settings.desktop_images);
        const mobileImages = normalizeImages(settings.mobile_images);
        if (desktopImages.length) {
            slider.replaceChildren(...desktopImages.map((url, index) => createSlide(url, mobileImages[index] || '', index)));
        }

        const textValues = {
            'homepage-hero-kicker': settings.kicker,
            'homepage-hero-title': settings.title,
            'homepage-hero-description': settings.description,
            'homepage-hero-button': settings.button_label
        };
        Object.entries(textValues).forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element && typeof value === 'string' && value.trim()) element.textContent = value.trim();
        });
        const button = document.getElementById('homepage-hero-button');
        if (button) button.href = safeButtonUrl(settings.button_url);
        startRotation();
    }

    startRotation();
    fetch('../api/index.php?resource=homepage', { credentials: 'same-origin' })
        .then(response => response.ok ? response.json() : Promise.reject(new Error('Pengaturan hero tidak tersedia.')))
        .then(result => {
            if (result?.ok && result.data) applySettings(result.data);
        })
        .catch(() => {
            // Hero bawaan di HTML tetap digunakan jika koneksi API terganggu.
        });
})();
