<?php
declare(strict_types=1);

define('DDU_SKIP_SESSION', true);
require_once dirname(__DIR__) . '/bootstrap.php';

$report = SystemHealth::snapshot();
echo json_encode([
    'ok' => $report['status'] !== 'critical',
    'status' => $report['status'],
    'checked_at' => $report['checked_at'],
    'alerts' => $report['alerts'],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit($report['status'] === 'critical' ? 1 : 0);
