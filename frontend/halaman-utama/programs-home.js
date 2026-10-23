const PROGRAMS_PER_PAGE = 6;

const escapeHtml = (value = '') => String(value).replace(/[&<>'"]/g, char => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'
}[char]));

let homePrograms = [];
let currentProgramPage = 1;

function createProgramCard(program) {
    return `<a class="gallery-item program-card" href="/${encodeURIComponent(program.slug)}" target="_blank" rel="noopener noreferrer">
        <div class="program-card-media">
            <img src="${escapeHtml(program.image)}" alt="${escapeHtml(program.image_alt || program.title)}" width="800" height="520" loading="lazy" decoding="async">
        </div>
        <div class="program-card-body">
            <span class="content-category">${escapeHtml(program.category || 'Umum')}</span>
            <h3>${escapeHtml(program.title)}</h3>
            <p>${escapeHtml(program.excerpt || 'Klik untuk melihat detail program kebaikan ini.')}</p>
            <span class="program-card-action">Lihat Program <span aria-hidden="true">&#8599;</span></span>
        </div>
    </a>`;
}

function renderProgramPagination() {
    const pagination = document.getElementById('programPagination');
    if (!pagination) return;

    const totalPages = Math.ceil(homePrograms.length / PROGRAMS_PER_PAGE);
    pagination.replaceChildren();
    pagination.hidden = totalPages <= 1;
    if (totalPages <= 1) return;

    for (let page = 1; page <= totalPages; page += 1) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = `pagination-btn${page === currentProgramPage ? ' active' : ''}`;
        button.textContent = String(page);
        button.setAttribute('aria-label', `Tampilkan halaman program ${page}`);
        if (page === currentProgramPage) button.setAttribute('aria-current', 'page');
        button.addEventListener('click', () => {
            if (page === currentProgramPage) return;
            currentProgramPage = page;
            renderHomePrograms();
            document.getElementById('programs')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
        pagination.appendChild(button);
    }
}

function renderHomePrograms() {
    const grid = document.getElementById('program-list-home');
    if (!grid) return;

    const start = (currentProgramPage - 1) * PROGRAMS_PER_PAGE;
    const programs = homePrograms.slice(start, start + PROGRAMS_PER_PAGE);
    grid.innerHTML = programs.length
        ? programs.map(createProgramCard).join('')
        : '<p class="program-list-empty">Belum ada program yang tersedia.</p>';
    renderProgramPagination();
}

document.addEventListener('DOMContentLoaded', async () => {
    const grid = document.getElementById('program-list-home');
    if (!grid) return;

    try {
        const response = await fetch('../api/index.php?resource=programs&limit=50', { credentials: 'same-origin' });
        const result = await response.json();
        if (!response.ok) throw new Error(result.message);
        homePrograms = result.data || [];
        renderHomePrograms();
    } catch (error) {
        console.error('Gagal memuat program:', error);
        grid.innerHTML = '<p class="program-list-empty">Program belum dapat dimuat. Silakan coba kembali.</p>';
        const pagination = document.getElementById('programPagination');
        if (pagination) pagination.hidden = true;
    }
});
