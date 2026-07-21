const API = '../api/index.php';
const escapeHtml = (value = '') => String(value).replace(/[&<>'"]/g, char => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'
}[char]));
const dateText = value => {
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? '' : date.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
};

document.addEventListener('DOMContentLoaded', async () => {
    const container = document.getElementById('post-container');
    const slug = new URLSearchParams(location.search).get('slug');
    if (!container || !slug || !/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(slug)) return showError(container);
    try {
        const [postResponse, relatedResponse] = await Promise.all([
            fetch(`${API}?resource=posts&slug=${encodeURIComponent(slug)}`, { credentials: 'same-origin' }),
            fetch(`${API}?resource=posts&exclude=${encodeURIComponent(slug)}&limit=3`, { credentials: 'same-origin' })
        ]);
        const postResult = await postResponse.json();
        const relatedResult = await relatedResponse.json();
        if (!postResponse.ok || !postResult.data) throw new Error(postResult.message || 'Artikel tidak ditemukan.');
        renderPost(container, postResult.data, relatedResult.data || []);
    } catch (error) {
        console.error(error);
        showError(container);
    }
});

function renderPost(container, post, related) {
    document.title = `${post.title} - Dompet Dana Umat`;
    const wa = post.whatsapp_number || '6285121277046';
    const message = post.whatsapp_message || `Assalamualaikum, saya ingin bertanya terkait artikel: ${post.title}`;
    const relatedHtml = related.length ? `<div class="related-section"><h3>Artikel Terkait</h3><div class="related-grid">${related.map(item => `
        <div class="related-card"><a href="post.html?slug=${encodeURIComponent(item.slug)}" class="related-img-wrapper"><img src="${escapeHtml(item.image)}" alt="${escapeHtml(item.title)}" loading="lazy"></a>
        <div class="related-content"><h4><a href="post.html?slug=${encodeURIComponent(item.slug)}">${escapeHtml(item.title)}</a></h4><span class="related-date">${escapeHtml(dateText(item.created_at))}</span></div></div>`).join('')}</div></div>` : '';
    container.innerHTML = `
        <section class="hero" style="background:#0a2b5e;padding:150px 0 80px"><div class="hero-overlay"></div><div class="container hero-container" style="text-align:center"><div class="hero-content" style="max-width:100%;margin:0 auto"><h5>ARTIKEL & BERITA</h5><h1 style="color:white">${escapeHtml(post.title)}</h1><p style="color:#e0e0e0;margin-bottom:0">Diterbitkan pada ${escapeHtml(dateText(post.created_at))}</p></div></div></section>
        <div class="container" style="padding:60px 20px"><article class="post-full" style="max-width:800px;margin:0 auto;background:white;padding:40px;border-radius:15px;box-shadow:0 10px 30px rgba(0,0,0,.05);position:relative;z-index:10">
        ${post.image ? `<img src="${escapeHtml(post.image)}" alt="${escapeHtml(post.title)}" class="post-full-image" style="width:100%;height:auto;border-radius:10px;margin-bottom:30px;object-fit:cover">` : ''}
        <div class="post-full-content" style="font-size:1.1rem;line-height:1.8;color:#2c3e5c">${post.content}</div></article>
        <section class="cta-minimal fade-in" style="margin-top:40px;border-radius:15px"><div class="container"><h5>LAYANAN KONSULTASI</h5><h2>Tertarik dengan topik ini?</h2><p>Dapatkan informasi lebih lanjut melalui layanan chat kami.</p><a href="https://wa.me/${encodeURIComponent(wa)}?text=${encodeURIComponent(message)}" class="btn-whatsapp-minimal" target="_blank" rel="noopener noreferrer">Hubungi Kami via WhatsApp →</a></div></section>${relatedHtml}</div>`;
}

function showError(container) {
    if (!container) return;
    container.innerHTML = '<div class="post-error"><h2>Artikel Tidak Ditemukan</h2><p>Artikel yang Anda cari tidak ada atau URL tidak valid.</p><a href="index.html#blog" class="btn btn-primary">Kembali ke Blog</a></div>';
}
