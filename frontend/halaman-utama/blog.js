const API = '../api/index.php';
let allPosts = [];
let currentPosts = [];
let currentPage = 1;
const itemsPerPage = 3;

const escapeHtml = (value = '') => String(value).replace(/[&<>'"]/g, char => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'
}[char]));

function createBlogCard(post) {
    const slug = encodeURIComponent(post.slug);
    return `<article class="blog-card">
        <a href="post.html?slug=${slug}" class="blog-card-link">
            <div class="blog-img"><img src="${escapeHtml(post.image)}" alt="${escapeHtml(post.title)}" loading="lazy"></div>
            <div class="blog-content">
                <h3>${escapeHtml(post.title)}</h3>
                <p>${escapeHtml(post.excerpt)}</p>
                <time class="blog-card-date" datetime="${escapeHtml(formatIsoDate(post.created_at))}">${escapeHtml(formatDate(post.created_at))}</time>
                <span class="read-more">Baca Selengkapnya <span aria-hidden="true">→</span></span>
            </div>
        </a>
    </article>`;
}

function formatDate(value) {
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? '' : date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}

function formatIsoDate(value) {
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? '' : date.toISOString().slice(0, 10);
}

function renderBlogPosts(posts = allPosts) {
    currentPosts = posts;
    const grid = document.querySelector('#blog .blog-grid');
    const empty = document.getElementById('noResultsMessage');
    if (!grid) return;
    const start = (currentPage - 1) * itemsPerPage;
    grid.innerHTML = posts.slice(start, start + itemsPerPage).map(createBlogCard).join('');
    if (empty) empty.style.display = posts.length ? 'none' : 'block';
    renderPagination();
}

function renderPagination() {
    const container = document.getElementById('blogPagination');
    if (!container) return;
    container.replaceChildren();
    const pages = Math.ceil(currentPosts.length / itemsPerPage);
    if (pages <= 1) return;
    for (let page = 1; page <= pages; page += 1) {
        const button = document.createElement('button');
        button.type = 'button';
        button.textContent = page;
        button.className = `pagination-btn ${page === currentPage ? 'active' : ''}`;
        button.addEventListener('click', () => { currentPage = page; renderBlogPosts(currentPosts); });
        container.appendChild(button);
    }
}

async function fetchPosts() {
    try {
        const response = await fetch(`${API}?resource=posts&limit=50`, { credentials: 'same-origin' });
        const result = await response.json();
        if (!response.ok) throw new Error(result.message);
        allPosts = result.data || [];
        renderBlogPosts();
    } catch (error) {
        console.error('Gagal memuat artikel:', error);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    fetchPosts();
    document.getElementById('blogSearchInput')?.addEventListener('input', event => {
        const term = event.target.value.toLowerCase().trim();
        currentPage = 1;
        renderBlogPosts(allPosts.filter(post => post.title.toLowerCase().includes(term)));
    });
});
