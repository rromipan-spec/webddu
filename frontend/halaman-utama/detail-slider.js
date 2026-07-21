const escapeHtml = (value = '') => String(value).replace(/[&<>'"]/g, character => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'
}[character]));

export function recordSliderImages(record = {}) {
    let images = [];
    if (Array.isArray(record.gallery_images)) {
        images = record.gallery_images;
    } else {
        try {
            const parsed = JSON.parse(record.gallery_images || '[]');
            if (Array.isArray(parsed)) images = parsed;
        } catch (error) {
            images = [];
        }
    }
    images = images.map(value => String(value || '').trim()).filter(Boolean);
    const mainImage = String(record.image || '').trim();
    if (mainImage && !images.includes(mainImage)) images.unshift(mainImage);
    return [...new Set(images)].slice(0, 3);
}

export function detailSliderHtml(images, title, extraClass = '') {
    if (!images.length) return '';
    const controls = images.length > 1 ? `
        <button type="button" class="detail-slider-button detail-slider-prev" aria-label="Foto sebelumnya">&#10094;</button>
        <button type="button" class="detail-slider-button detail-slider-next" aria-label="Foto berikutnya">&#10095;</button>
        <div class="detail-slider-dots" aria-label="Pilih foto">
            ${images.map((image, index) => `<button type="button" class="detail-slider-dot${index === 0 ? ' active' : ''}" data-slide-index="${index}" aria-label="Tampilkan foto ${index + 1}"></button>`).join('')}
        </div>` : '';
    return `<div class="detail-image-slider ${escapeHtml(extraClass)}" data-detail-slider>
        ${images.map((image, index) => `<div class="detail-image-slide${index === 0 ? ' active' : ''}" aria-hidden="${index === 0 ? 'false' : 'true'}"><img src="${escapeHtml(image)}" alt="${escapeHtml(title)} - foto ${index + 1}"${index > 0 ? ' loading="lazy"' : ''}></div>`).join('')}
        ${controls}
    </div>`;
}

export function initDetailSliders(root = document) {
    root.querySelectorAll('[data-detail-slider]').forEach(slider => {
        const slides = Array.from(slider.querySelectorAll('.detail-image-slide'));
        const dots = Array.from(slider.querySelectorAll('.detail-slider-dot'));
        if (slides.length < 2) return;
        let current = 0;
        let timer = null;
        const show = index => {
            current = (index + slides.length) % slides.length;
            slides.forEach((slide, slideIndex) => {
                const active = slideIndex === current;
                slide.classList.toggle('active', active);
                slide.setAttribute('aria-hidden', String(!active));
            });
            dots.forEach((dot, dotIndex) => dot.classList.toggle('active', dotIndex === current));
        };
        const stop = () => {
            if (timer) window.clearInterval(timer);
            timer = null;
        };
        const start = () => {
            stop();
            if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                timer = window.setInterval(() => show(current + 1), 4500);
            }
        };
        slider.querySelector('.detail-slider-prev')?.addEventListener('click', () => { show(current - 1); start(); });
        slider.querySelector('.detail-slider-next')?.addEventListener('click', () => { show(current + 1); start(); });
        dots.forEach((dot, index) => dot.addEventListener('click', () => { show(index); start(); }));
        slider.addEventListener('mouseenter', stop);
        slider.addEventListener('mouseleave', start);
        slider.addEventListener('focusin', stop);
        slider.addEventListener('focusout', start);
        start();
    });
}
