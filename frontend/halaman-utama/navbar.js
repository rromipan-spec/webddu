const recordStat = type => fetch('../api/index.php?resource=stats', {
    method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ type })
}).catch(() => {});

document.addEventListener('DOMContentLoaded', () => {
    if (!sessionStorage.getItem('ddu_visit')) {
        recordStat('visit');
        sessionStorage.setItem('ddu_visit', '1');
    }
    document.addEventListener('click', event => {
        if (event.target.closest('.whatsapp-popup, .btn-whatsapp-minimal')) recordStat('wa_click');
    });

    const preloader = document.querySelector('.preloader');
    window.addEventListener('load', () => preloader?.classList.add('hidden'));
    const header = document.querySelector('.main-header');
    const backToTop = document.querySelector('.back-to-top');
    window.addEventListener('scroll', () => {
        header?.classList.toggle('scrolled', window.scrollY > 50);
        backToTop?.classList.toggle('visible', window.scrollY > 300);
    });
    backToTop?.addEventListener('click', event => { event.preventDefault(); window.scrollTo({ top: 0, behavior: 'smooth' }); });

    const toggle = document.querySelector('.menu-toggle');
    const links = document.querySelector('.nav-links');
    const close = () => { links?.classList.remove('active'); toggle?.classList.remove('active'); header?.classList.remove('menu-open'); };
    toggle?.addEventListener('click', () => { links?.classList.toggle('active'); toggle.classList.toggle('active'); header?.classList.toggle('menu-open'); });
    document.querySelector('.close-menu-btn')?.addEventListener('click', close);
    links?.querySelectorAll('a').forEach(link => link.addEventListener('click', close));

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
