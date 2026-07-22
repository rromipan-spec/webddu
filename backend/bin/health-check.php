<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/src/Config.php';
require_once dirname(__DIR__) . '/src/Database.php';
require_once dirname(__DIR__) . '/src/AppLogger.php';
require_once dirname(__DIR__) . '/src/HealthCheck.php';

AppLogger::boot(dirname(__DIR__) . '/storage/logs');

try {
    Config::load(dirname(__DIR__) . '/config/.env');
    AppLogger::configure(
        Config::get('APP_ENV', 'production') === 'production',
        (int) Config::get('LOG_MAX_SIZE_MB', '5'),
        (int) Config::get('LOG_RETENTION_FILES', '5'),
        [Config::get('DB_PASS', ''), Config::get('ADMIN_SETUP_KEY', '')]
    );
    $result = HealthCheck::run();
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit($result['ok'] ? 0 : 1);
} catch (Throwable $error) {
    $eventId = AppLogger::exception($error);
    fwrite(STDERR, '[HEALTH CHECK GAGAL] Kode kejadian: ' . $eventId . PHP_EOL);
    exit(1);
}
