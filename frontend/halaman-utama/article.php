<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=300, stale-while-revalidate=600');

$slug = strtolower(trim((string) ($_GET['slug'] ?? '')));
$post = null;
if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
    $statement = Database::connection()->prepare('SELECT * FROM posts WHERE slug = :slug LIMIT 1');
    $statement->execute(['slug' => $slug]);
    $post = $statement->fetch() ?: null;
}

$template = file_get_contents(__DIR__ . '/post.html');
if ($template === false) {
    throw new RuntimeException('Template artikel tidak ditemukan.');
}

if (!$post) {
    http_response_code(404);
    echo $template;
    exit;
}

$escape = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$siteName = 'Dompet Dana Umat';
$appUrl = rtrim(Config::get('APP_URL', 'https://dompetdanaumat.com'), '/');
$canonicalUrl = $appUrl . '/artikel/' . rawurlencode((string) $post['slug']);
$description = trim((string) ($post['excerpt'] ?? ''));
if ($description === '') {
    $description = trim(preg_replace('/\s+/', ' ', strip_tags((string) ($post['content'] ?? ''))) ?? '');
}
$description = mb_substr($description, 0, 160);

$socialImage = trim((string) ($post['image'] ?? ''));
if ($socialImage === '') {
    $gallery = json_decode((string) ($post['gallery_images'] ?? '[]'), true);
    if (is_array($gallery)) $socialImage = trim((string) ($gallery[0] ?? ''));
}
if ($socialImage === '') $socialImage = trim((string) ($post['hero_image'] ?? ''));
if ($socialImage === '') $socialImage = $appUrl . '/asset/logo-dompet-dana-umat.png';
if (str_starts_with($socialImage, '/')) $socialImage = $appUrl . $socialImage;

$publishedTime = date(DATE_ATOM, strtotime((string) ($post['created_at'] ?? 'now')) ?: time());
$modifiedTime = date(DATE_ATOM, strtotime((string) ($post['updated_at'] ?? $post['created_at'] ?? 'now')) ?: time());
$pageTitle = (string) $post['title'] . ' - ' . $siteName;
$structuredData = [
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => (string) $post['title'],
    'description' => $description,
    'image' => [$socialImage],
    'datePublished' => $publishedTime,
    'dateModified' => $modifiedTime,
    'mainEntityOfPage' => $canonicalUrl,
    'author' => ['@type' => 'Organization', 'name' => $siteName, 'url' => $appUrl],
    'publisher' => [
        '@type' => 'Organization',
        'name' => $siteName,
        'logo' => ['@type' => 'ImageObject', 'url' => $appUrl . '/asset/logo-dompet-dana-umat.png'],
    ],
];

$socialMeta = '<title>' . $escape($pageTitle) . '</title>' . "\n"
    . '    <meta name="description" content="' . $escape($description) . '">' . "\n"
    . '    <meta name="author" content="' . $escape($siteName) . '">' . "\n"
    . '    <link rel="canonical" href="' . $escape($canonicalUrl) . '">' . "\n"
    . '    <meta property="og:type" content="article">' . "\n"
    . '    <meta property="og:site_name" content="' . $escape($siteName) . '">' . "\n"
    . '    <meta property="og:locale" content="id_ID">' . "\n"
    . '    <meta property="og:title" content="' . $escape((string) $post['title']) . '">' . "\n"
    . '    <meta property="og:description" content="' . $escape($description) . '">' . "\n"
    . '    <meta property="og:url" content="' . $escape($canonicalUrl) . '">' . "\n"
    . '    <meta property="og:image" content="' . $escape($socialImage) . '">' . "\n"
    . '    <meta property="og:image:secure_url" content="' . $escape($socialImage) . '">' . "\n"
    . '    <meta property="og:image:alt" content="' . $escape((string) $post['title']) . '">' . "\n"
    . '    <meta property="article:published_time" content="' . $escape($publishedTime) . '">' . "\n"
    . '    <meta property="article:modified_time" content="' . $escape($modifiedTime) . '">' . "\n"
    . '    <meta name="twitter:card" content="summary_large_image">' . "\n"
    . '    <meta name="twitter:title" content="' . $escape((string) $post['title']) . '">' . "\n"
    . '    <meta name="twitter:description" content="' . $escape($description) . '">' . "\n"
    . '    <meta name="twitter:image" content="' . $escape($socialImage) . '">' . "\n"
    . '    <script id="article-structured-data" type="application/ld+json">'
    . json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP)
    . '</script>';

$template = str_replace('<title>Artikel - Dompet Dana Umat</title>', $socialMeta, $template);
echo $template;
