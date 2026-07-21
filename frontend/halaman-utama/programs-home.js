const escapeHtml = (value = '') => String(value).replace(/[&<>'"]/g, char => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'
}[char]));

document.addEventListener('DOMContentLoaded', async () => {
    const grid = document.getElementById('program-list-home');
    if (!grid) return;
    try {
        const response = await fetch('../api/index.php?resource=programs&limit=6', { credentials: 'same-origin' });
        const result = await response.json();
        if (!response.ok) throw new Error(result.message);
        const programs = result.data || [];
        grid.innerHTML = programs.length ? programs.map(program => `
            <a class="gallery-item program-card" href="/${encodeURIComponent(program.slug)}">
                <div class="program-card-media">
                    <img src="${escapeHtml(program.image)}" alt="${escapeHtml(program.title)}" width="800" height="520" loading="lazy" decoding="async">
                </div>
                <div class="program-card-body">
                    <h3>${escapeHtml(program.title)}</h3>
                    <p>${escapeHtml(program.excerpt || 'Klik untuk melihat detail program kebaikan ini.')}</p>
                    <span class="program-card-action">Lihat Program <span aria-hidden="true">→</span></span>
                </div>
            </a>`).join('') : '<p style="text-align:center;grid-column:1/-1">Belum ada program yang tersedia.</p>';
    } catch (error) {
        console.error('Gagal memuat program:', error);
    }
});
