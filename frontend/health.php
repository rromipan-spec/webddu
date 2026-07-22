<?php
declare(strict_types=1);

define('DDU_SKIP_SESSION', true);
require_once dirname(__DIR__) . '/backend/bootstrap.php';

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
header('X-Robots-Tag: noindex, nofollow, noarchive');
if (!in_array($method, ['GET', 'HEAD'], true)) {
    header('Allow: GET, HEAD');
    Http::json(['ok' => false, 'message' => 'Metode tidak diizinkan.'], 405);
}

$result = HealthCheck::run();
Http::json(HealthCheck::publicResult($result), $result['ok'] ? 200 : 503);
