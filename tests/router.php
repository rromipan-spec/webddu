<?php
declare(strict_types=1);

// Router khusus PHP development server. Ini meniru aturan URL publik di
// frontend/.htaccess karena server bawaan PHP tidak membaca konfigurasi Apache.
$frontendRoot = dirname(__DIR__) . '/frontend';
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$requestPath = is_string($requestPath) ? rawurldecode($requestPath) : '/';

$serveNotFound = static function () use ($frontendRoot): void {
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    $page = file_get_contents($frontendRoot . '/404.html');
    echo $page !== false ? $page : 'Halaman tidak ditemukan.';
};

// Tolak path yang mencoba keluar dari document root.
if (str_contains($requestPath, "\0") || preg_match('#(?:^|/)\.\.(?:/|$)#', $requestPath)) {
    $serveNotFound();
    return true;
}

// Biarkan server PHP melayani file publik yang memang tersedia.
$directFile = $frontendRoot . str_replace('/', DIRECTORY_SEPARATOR, $requestPath);
if ($requestPath !== '/' && is_file($directFile)) {
    return false;
}

if ($requestPath === '/') {
    require $frontendRoot . '/index.php';
    return true;
}

if ($requestPath === '/sitemap.xml') {
    require $frontendRoot . '/sitemap.php';
    return true;
}

// File halaman utama dipublikasikan dari root domain oleh .htaccess.
$publicPage = $frontendRoot . '/halaman-utama' . str_replace('/', DIRECTORY_SEPARATOR, $requestPath);
if (is_file($publicPage)) {
    require $publicPage;
    return true;
}

if (preg_match('#^/artikel/([a-z0-9]+(?:-[a-z0-9]+)*)/?$#', $requestPath, $matches)) {
    $_GET['slug'] = $matches[1];
    require $frontendRoot . '/halaman-utama/article.php';
    return true;
}

if (preg_match('#^/([a-z0-9]+(?:-[a-z0-9]+)*)/?$#', $requestPath, $matches)) {
    $_GET['slug'] = $matches[1];
    require $frontendRoot . '/halaman-utama/program.php';
    return true;
}

$serveNotFound();
return true;
