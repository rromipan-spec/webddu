<?php
declare(strict_types=1);

define('DDU_SKIP_SESSION', true);
require_once dirname(__DIR__) . '/bootstrap.php';

try {
    $deleted = Analytics::prune();
    fwrite(STDOUT, "Pembersihan analitik berhasil. Baris lama dihapus: {$deleted}\n");
    exit(0);
} catch (Throwable $error) {
    fwrite(STDERR, "Pembersihan analitik gagal. Periksa app.log.\n");
    error_log('[AnalyticsPrune] ' . $error->getMessage());
    exit(1);
}
