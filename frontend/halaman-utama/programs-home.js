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
            <a class="gallery-item" href="program.html?slug=${encodeURIComponent(program.slug)}">
                <img src="${escapeHtml(program.image)}" alt="${escapeHtml(program.title)}" loading="lazy">
                <div class="program-card-title">${escapeHtml(program.title)}</div>
                <div class="gallery-overlay"><div class="gallery-text"><h4>${escapeHtml(program.title)}</h4><p>${escapeHtml(program.excerpt || 'Klik untuk melihat detail program kebaikan ini.')}</p><span>Selengkapnya →</span></div></div>
            </a>`).join('') : '<p style="text-align:center;grid-column:1/-1">Belum ada program yang tersedia.</p>';
    } catch (error) {
        console.error('Gagal memuat program:', error);
    }
});
