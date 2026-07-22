<?php
declare(strict_types=1);

require_once __DIR__ . '/src/Config.php';
require_once __DIR__ . '/src/Http.php';
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/AppLogger.php';
require_once __DIR__ . '/src/HealthCheck.php';
require_once __DIR__ . '/src/LoginThrottle.php';
require_once __DIR__ . '/src/Auth.php';
require_once __DIR__ . '/src/Sanitizer.php';
require_once __DIR__ . '/src/ImageProcessor.php';

AppLogger::boot(__DIR__ . '/storage/logs');
Config::load(__DIR__ . '/config/.env');
AppLogger::configure(
    Config::get('APP_ENV', 'production') === 'production',
    (int) Config::get('LOG_MAX_SIZE_MB', '5'),
    (int) Config::get('LOG_RETENTION_FILES', '5'),
    [Config::get('DB_PASS', ''), Config::get('ADMIN_SETUP_KEY', '')]
);
Http::securityHeaders();
if (!defined('DDU_SKIP_SESSION') || DDU_SKIP_SESSION !== true) {
    Auth::start();
}
