<?php
declare(strict_types=1);

require_once __DIR__ . '/src/Config.php';
require_once __DIR__ . '/src/Http.php';
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/LoginThrottle.php';
require_once __DIR__ . '/src/Auth.php';
require_once __DIR__ . '/src/Sanitizer.php';
require_once __DIR__ . '/src/ImageProcessor.php';

set_exception_handler(static function (Throwable $error): void {
    $log = sprintf("[%s] %s in %s:%d\n", date('c'), $error->getMessage(), $error->getFile(), $error->getLine());
    error_log($log, 3, __DIR__ . '/storage/logs/app.log');
    try {
        $production = Config::get('APP_ENV', 'production') === 'production';
    } catch (Throwable) {
        $production = true;
    }
    $message = $production ? 'Konfigurasi server belum lengkap atau terjadi kesalahan internal.' : $error->getMessage();
    Http::json(['ok' => false, 'message' => $message], 500);
});

Config::load(__DIR__ . '/config/.env');
Http::securityHeaders();
Auth::start();
