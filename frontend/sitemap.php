<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/backend/bootstrap.php';

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=900, stale-while-revalidate=3600');

$baseUrl = rtrim(Config::get('APP_URL', 'https://dompetdanaumat.com'), '/');
$escape = static fn(string $value): string => htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
$urls = [
    ['loc' => $baseUrl . '/', 'lastmod' => null],
    ['loc' => $baseUrl . '/about.html', 'lastmod' => null],
];

foreach (Database::connection()->query("SELECT slug, updated_at FROM posts WHERE status = 'published' AND (published_at IS NULL OR published_at <= UTC_TIMESTAMP()) ORDER BY updated_at DESC")->fetchAll() as $post) {
    $urls[] = ['loc' => $baseUrl . '/artikel/' . rawurlencode((string) $post['slug']), 'lastmod' => $post['updated_at'] ?? null];
}
foreach (Database::connection()->query("SELECT slug, updated_at FROM programs WHERE status = 'published' AND (published_at IS NULL OR published_at <= UTC_TIMESTAMP()) ORDER BY updated_at DESC")->fetchAll() as $program) {
    $urls[] = ['loc' => $baseUrl . '/' . rawurlencode((string) $program['slug']), 'lastmod' => $program['updated_at'] ?? null];
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $url) {
    echo "  <url>\n    <loc>" . $escape($url['loc']) . "</loc>\n";
    if (!empty($url['lastmod']) && ($timestamp = strtotime((string) $url['lastmod']))) {
        echo '    <lastmod>' . $escape(date(DATE_ATOM, $timestamp)) . "</lastmod>\n";
    }
    echo "  </url>\n";
}
echo '</urlset>';
