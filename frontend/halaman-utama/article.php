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
    $notFound = file_get_contents(dirname(__DIR__) . '/404.html');
    echo $notFound !== false ? $notFound : 'Halaman tidak ditemukan.';
    exit;
}

$escape = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$siteName = 'Dompet Dana Umat';
$appUrl = rtrim(Config::get('APP_URL', 'https://dompetdanaumat.com'), '/');
$canonicalUrl = $appUrl . '/artikel/' . rawurlencode((string) $post['slug']);
$metaTitle = trim((string) ($post['seo_title'] ?? '')) ?: (string) $post['title'];
$description = trim((string) ($post['seo_description'] ?? ''));
if ($description === '') $description = trim((string) ($post['excerpt'] ?? ''));
if ($description === '') {
    $description = trim(preg_replace('/\s+/', ' ', strip_tags((string) ($post['content'] ?? ''))) ?? '');
}
$description = mb_substr($description, 0, 160);

$socialImage = trim((string) ($post['social_image'] ?? ''));
if ($socialImage === '') $socialImage = trim((string) ($post['image'] ?? ''));
if ($socialImage === '') {
    $gallery = json_decode((string) ($post['gallery_images'] ?? '[]'), true);
    if (is_array($gallery)) $socialImage = trim((string) ($gallery[0] ?? ''));
}
if ($socialImage === '') $socialImage = trim((string) ($post['hero_image'] ?? ''));
if ($socialImage === '') $socialImage = $appUrl . '/asset/logo-dompet-dana-umat.png';

// Upload baru memiliki varian JPEG 1200x630 khusus pratinjau WhatsApp/Facebook.
if (str_starts_with($socialImage, '/uploads/')) {
    $socialVariant = preg_replace(
        '#(/uploads/[a-f0-9]{32})/(?:thumb|card|content|hero|social)\.(?:webp|jpg)$#i',
        '$1/social.jpg',
        $socialImage
    );
    if (is_string($socialVariant) && $socialVariant !== $socialImage) {
        $socialVariantPath = dirname(__DIR__) . str_replace('/', DIRECTORY_SEPARATOR, $socialVariant);
        if (is_file($socialVariantPath)) {
            $socialImage = $socialVariant;
        }
    }
}

$imageWidth = null;
$imageHeight = null;
$imageMime = null;
if (str_starts_with($socialImage, '/')) {
    $localImagePath = dirname(__DIR__) . str_replace('/', DIRECTORY_SEPARATOR, $socialImage);
    if (is_file($localImagePath)) {
        $imageInfo = @getimagesize($localImagePath);
        if (is_array($imageInfo)) {
            $imageWidth = (int) ($imageInfo[0] ?? 0) ?: null;
            $imageHeight = (int) ($imageInfo[1] ?? 0) ?: null;
            $imageMime = (string) ($imageInfo['mime'] ?? '') ?: null;
        }
    }
    $socialImage = $appUrl . $socialImage;
}

// Versi URL berubah ketika artikel diperbarui sehingga cache thumbnail platform sosial ikut diperbarui.
$imageVersion = strtotime((string) ($post['updated_at'] ?? $post['created_at'] ?? '')) ?: time();
$socialImageMeta = $socialImage . (str_contains($socialImage, '?') ? '&' : '?') . 'v=' . $imageVersion;

$publishedTime = date(DATE_ATOM, strtotime((string) ($post['created_at'] ?? 'now')) ?: time());
$modifiedTime = date(DATE_ATOM, strtotime((string) ($post['updated_at'] ?? $post['created_at'] ?? 'now')) ?: time());
$imageAlt = trim((string) ($post['image_alt'] ?? '')) ?: (string) $post['title'];
$pageTitle = $metaTitle . ' - ' . $siteName;
$structuredData = [
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => (string) $post['title'],
    'description' => $description,
    'image' => [$socialImageMeta],
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
    . '    <meta property="og:title" content="' . $escape($metaTitle) . '">' . "\n"
    . '    <meta property="og:description" content="' . $escape($description) . '">' . "\n"
    . '    <meta property="og:url" content="' . $escape($canonicalUrl) . '">' . "\n"
    . '    <meta property="og:image" content="' . $escape($socialImageMeta) . '">' . "\n"
    . '    <meta property="og:image:secure_url" content="' . $escape($socialImageMeta) . '">' . "\n"
    . '    <meta property="og:image:alt" content="' . $escape($imageAlt) . '">' . "\n"
    . ($imageWidth ? '    <meta property="og:image:width" content="' . $imageWidth . '">' . "\n" : '')
    . ($imageHeight ? '    <meta property="og:image:height" content="' . $imageHeight . '">' . "\n" : '')
    . ($imageMime ? '    <meta property="og:image:type" content="' . $escape($imageMime) . '">' . "\n" : '')
    . '    <meta property="article:published_time" content="' . $escape($publishedTime) . '">' . "\n"
    . '    <meta property="article:modified_time" content="' . $escape($modifiedTime) . '">' . "\n"
    . '    <meta name="twitter:card" content="summary_large_image">' . "\n"
    . '    <meta name="twitter:title" content="' . $escape($metaTitle) . '">' . "\n"
    . '    <meta name="twitter:description" content="' . $escape($description) . '">' . "\n"
    . '    <meta name="twitter:image" content="' . $escape($socialImageMeta) . '">' . "\n"
    . '    <script id="article-structured-data" type="application/ld+json">'
    . json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP)
    . '</script>';

$template = str_replace('<title>Artikel - Dompet Dana Umat</title>', $socialMeta, $template);
echo $template;
