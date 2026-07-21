<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';

header('Content-Type: text/html; charset=utf-8');

$slug = strtolower(trim((string) ($_GET['slug'] ?? '')));
$preview = (string) ($_GET['preview'] ?? '') === '1' && Auth::check();
header('Cache-Control: ' . ($preview ? 'private, no-store, max-age=0' : 'public, max-age=300, stale-while-revalidate=600'));
$program = null;
if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
    $publicationFilter = $preview ? '' : " AND status = 'published' AND (published_at IS NULL OR published_at <= UTC_TIMESTAMP())";
    $statement = Database::connection()->prepare("SELECT * FROM programs WHERE slug = :slug{$publicationFilter} LIMIT 1");
    $statement->execute(['slug' => $slug]);
    $program = $statement->fetch() ?: null;
}

if (!$program) {
    http_response_code(404);
    $notFound = file_get_contents(dirname(__DIR__) . '/404.html');
    echo $notFound !== false ? $notFound : 'Halaman tidak ditemukan.';
    exit;
}

$template = file_get_contents(__DIR__ . '/program.html');
if ($template === false) throw new RuntimeException('Template program tidak ditemukan.');

$escape = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$siteName = 'Dompet Dana Umat';
$appUrl = rtrim(Config::get('APP_URL', 'https://dompetdanaumat.com'), '/');
$canonicalUrl = $appUrl . '/' . rawurlencode((string) $program['slug']);
$metaTitle = trim((string) ($program['seo_title'] ?? '')) ?: (string) $program['title'];
$description = trim((string) ($program['seo_description'] ?? ''));
if ($description === '') $description = trim((string) ($program['excerpt'] ?? ''));
if ($description === '') $description = trim(preg_replace('/\s+/', ' ', strip_tags((string) ($program['content'] ?? ''))) ?? '');
$description = mb_substr($description, 0, 160);
$imageAlt = trim((string) ($program['image_alt'] ?? '')) ?: (string) $program['title'];

$socialImage = trim((string) ($program['social_image'] ?? ''));
if ($socialImage === '') $socialImage = trim((string) ($program['image'] ?? ''));
if ($socialImage === '') {
    $gallery = json_decode((string) ($program['gallery_images'] ?? '[]'), true);
    if (is_array($gallery)) $socialImage = trim((string) ($gallery[0] ?? ''));
}
if ($socialImage === '') $socialImage = '/asset/logo-dompet-dana-umat.png';

if (str_starts_with($socialImage, '/uploads/')) {
    $variant = preg_replace('#(/uploads/[a-f0-9]{32})/(?:thumb|card|content|hero|social)\.(?:webp|jpg)$#i', '$1/social.jpg', $socialImage);
    if (is_string($variant) && is_file(dirname(__DIR__) . str_replace('/', DIRECTORY_SEPARATOR, $variant))) $socialImage = $variant;
}

$imageWidth = $imageHeight = null;
$imageMime = null;
if (str_starts_with($socialImage, '/')) {
    $localPath = dirname(__DIR__) . str_replace('/', DIRECTORY_SEPARATOR, $socialImage);
    if (is_file($localPath) && is_array($info = @getimagesize($localPath))) {
        $imageWidth = (int) ($info[0] ?? 0) ?: null;
        $imageHeight = (int) ($info[1] ?? 0) ?: null;
        $imageMime = (string) ($info['mime'] ?? '') ?: null;
    }
    $socialImage = $appUrl . $socialImage;
}
$version = strtotime((string) ($program['updated_at'] ?? $program['created_at'] ?? '')) ?: time();
$socialImage .= (str_contains($socialImage, '?') ? '&' : '?') . 'v=' . $version;

$structuredData = [
    '@context' => 'https://schema.org',
    '@type' => 'WebPage',
    'name' => (string) $program['title'],
    'description' => $description,
    'url' => $canonicalUrl,
    'image' => $socialImage,
    'isPartOf' => ['@type' => 'WebSite', 'name' => $siteName, 'url' => $appUrl],
    'publisher' => ['@type' => 'Organization', 'name' => $siteName, 'url' => $appUrl],
];

$meta = '<title>' . $escape($metaTitle . ' - ' . $siteName) . '</title>' . "\n"
    . '    <meta name="description" content="' . $escape($description) . '">' . "\n"
    . '    <link rel="canonical" href="' . $escape($canonicalUrl) . '">' . "\n"
    . '    <meta property="og:type" content="website">' . "\n"
    . '    <meta property="og:site_name" content="' . $escape($siteName) . '">' . "\n"
    . '    <meta property="og:locale" content="id_ID">' . "\n"
    . '    <meta property="og:title" content="' . $escape($metaTitle) . '">' . "\n"
    . '    <meta property="og:description" content="' . $escape($description) . '">' . "\n"
    . '    <meta property="og:url" content="' . $escape($canonicalUrl) . '">' . "\n"
    . '    <meta property="og:image" content="' . $escape($socialImage) . '">' . "\n"
    . '    <meta property="og:image:alt" content="' . $escape($imageAlt) . '">' . "\n"
    . ($imageWidth ? '    <meta property="og:image:width" content="' . $imageWidth . '">' . "\n" : '')
    . ($imageHeight ? '    <meta property="og:image:height" content="' . $imageHeight . '">' . "\n" : '')
    . ($imageMime ? '    <meta property="og:image:type" content="' . $escape($imageMime) . '">' . "\n" : '')
    . '    <meta name="twitter:card" content="summary_large_image">' . "\n"
    . '    <meta name="twitter:title" content="' . $escape($metaTitle) . '">' . "\n"
    . '    <meta name="twitter:description" content="' . $escape($description) . '">' . "\n"
    . '    <meta name="twitter:image" content="' . $escape($socialImage) . '">' . "\n"
    . '    <script type="application/ld+json">' . json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) . '</script>';

$template = str_replace('    <meta name="description" content="Detail program pemberdayaan dan sosial Dompet Dana Umat.">' . "\n", '', $template);
$template = str_replace('<title>Program - Dompet Dana Umat</title>', $meta, $template);
if ($preview) {
    $template = str_replace('content="index, follow, max-image-preview:large"', 'content="noindex, nofollow"', $template);
}
echo $template;
