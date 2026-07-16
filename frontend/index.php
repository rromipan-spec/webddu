<?php
declare(strict_types=1);

// Fallback untuk hosting yang belum menerapkan aturan rewrite.
$homeFile = __DIR__ . '/halaman-utama/index.html';
if (!is_file($homeFile)) {
    http_response_code(500);
    exit('File halaman utama tidak ditemukan.');
}

$html = file_get_contents($homeFile);
if ($html === false) {
    http_response_code(500);
    exit('Halaman utama tidak dapat dibaca.');
}

$html = preg_replace(
    '/<head([^>]*)>/i',
    '<head$1><base href="/halaman-utama/">',
    $html,
    1
) ?? $html;

header('Content-Type: text/html; charset=utf-8');
echo $html;

