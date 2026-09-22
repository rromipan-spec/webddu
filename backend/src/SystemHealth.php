<?php
declare(strict_types=1);

final class SystemHealth
{
    public static function report(): array
    {
        $started = microtime(true);
        $health = HealthCheck::run();
        $responseMs = (int) round((microtime(true) - $started) * 1000);
        $backups = self::backups();
        $logs = self::logs();
        $analytics = self::analyticsFreshness();
        $webVitals = Analytics::webVitalSummary(7);
        $history = self::history();
        $alerts = [];

        if (!$health['ok']) $alerts[] = self::alert('critical', 'Health check gagal', 'Periksa database, tabel, serta izin folder upload dan log.');
        if ($backups['age_hours'] === null) $alerts[] = self::alert('critical', 'Backup belum tersedia', 'Jalankan backup pertama dan aktifkan cron backup harian.');
        elseif ($backups['age_hours'] > (int) Config::get('BACKUP_MAX_AGE_HOURS', '36')) $alerts[] = self::alert('warning', 'Backup terlambat', 'Backup terakhir lebih lama dari batas yang ditentukan.');
        if ($logs['errors_24h'] >= (int) Config::get('ERROR_ALERT_THRESHOLD', '5') && $logs['errors_24h'] > max(1, $logs['errors_previous_24h']) * 2) {
            $alerts[] = self::alert('critical', 'Lonjakan error', 'Error 24 jam terakhir meningkat lebih dari dua kali periode sebelumnya.');
        }
        if ($analytics['age_hours'] === null) {
            $alerts[] = self::alert('warning', 'Analitik belum menerima data', 'Buka website publik lalu periksa kembali endpoint pencatatan statistik.');
        } elseif ($analytics['age_hours'] > (int) Config::get('ANALYTICS_STALE_HOURS', '24')) {
            $alerts[] = self::alert('warning', 'Data analitik tidak masuk', 'Tidak ada event baru melewati batas pemantauan. Pastikan skrip analitik dan endpoint aktif.');
        }
        if ($responseMs > (int) Config::get('HEALTH_RESPONSE_ALERT_MS', '1000')) $alerts[] = self::alert('warning', 'Respons server melambat', 'Health check internal membutuhkan waktu lebih lama dari batas.');
        foreach ($webVitals as $vital) {
            if (($vital['status'] ?? '') === 'needs_attention' && (int) ($vital['samples'] ?? 0) >= 5) {
                $alerts[] = self::alert('warning', 'Pengalaman halaman melambat', (string) $vital['metric'] . ' p75 pada ' . (string) $vital['device'] . ' melewati ambang baik.');
            }
        }

        return [
            'status' => $alerts && in_array('critical', array_column($alerts, 'severity'), true) ? 'critical' : ($alerts ? 'warning' : 'healthy'),
            'checked_at' => gmdate('c'), 'response_ms' => $responseMs, 'checks' => $health['checks'],
            'backups' => $backups, 'logs' => $logs, 'analytics' => $analytics,
            'web_vitals' => $webVitals,
            'uploads' => self::uploads(), 'database' => self::database(), 'history' => $history,
            'alerts' => $alerts, 'migration_required' => !self::tableExists('system_health_snapshots'),
        ];
    }

    public static function snapshot(): array
    {
        $report = self::report();
        if (self::tableExists('system_health_snapshots')) {
            $s = Database::connection()->prepare(
                'INSERT INTO system_health_snapshots (status,response_ms,database_ok,uploads_ok,logs_ok,error_count)
                 VALUES (:status,:response_ms,:database_ok,:uploads_ok,:logs_ok,:error_count)'
            );
            $s->execute([
                'status' => ($report['checks']['database']['ok'] ?? false) && ($report['checks']['uploads']['ok'] ?? false) ? 'healthy' : 'unhealthy',
                'response_ms' => $report['response_ms'], 'database_ok' => (int) ($report['checks']['database']['ok'] ?? false),
                'uploads_ok' => (int) ($report['checks']['uploads']['ok'] ?? false), 'logs_ok' => (int) ($report['checks']['logs']['ok'] ?? false),
                'error_count' => $report['logs']['errors_24h'],
            ]);
        }
        self::notify($report);
        return $report;
    }

    private static function backups(): array
    {
        $directory = dirname(__DIR__) . '/storage/backups';
        $files = is_dir($directory) ? glob($directory . '/ddu-backup-*.zip') ?: [] : [];
        usort($files, static fn($a, $b) => (int) filemtime($b) <=> (int) filemtime($a));
        $latest = $files[0] ?? null; $modified = $latest ? (int) filemtime($latest) : null;
        return ['count' => count($files), 'latest_at' => $modified ? gmdate('c', $modified) : null,
            'age_hours' => $modified ? round((time() - $modified) / 3600, 1) : null,
            'latest_size_bytes' => $latest ? (int) filesize($latest) : 0, 'latest_name' => $latest ? basename($latest) : null];
    }

    private static function logs(): array
    {
        $files = glob(dirname(__DIR__) . '/storage/logs/app.log*') ?: []; $current = 0; $previous = 0; $recent = [];
        foreach ($files as $file) {
            if (!is_file($file)) continue;
            $handle = fopen($file, 'rb');
            if ($handle) {
                while (($line = fgets($handle)) !== false) {
                    $entry = json_decode($line, true); if (!is_array($entry)) continue;
                    $timestamp = strtotime((string) ($entry['time'] ?? '')) ?: 0;
                    $level = (string) ($entry['level'] ?? ''); if (!in_array($level, ['exception','fatal','php_error'], true)) continue;
                    if ($timestamp >= time() - 86400) { $current++; $recent[] = ['time'=>$entry['time']??null,'level'=>$level,'event_id'=>$entry['event_id']??'','message'=>mb_substr((string)($entry['message']??''),0,180)]; }
                    elseif ($timestamp >= time() - 172800) $previous++;
                }
                fclose($handle);
            }
        }
        usort($recent, static fn(array $a, array $b): int => strcmp((string) ($a['time'] ?? ''), (string) ($b['time'] ?? '')));
        return ['errors_24h'=>$current,'errors_previous_24h'=>$previous,'recent'=>array_slice(array_reverse($recent),0,8)];
    }

    private static function analyticsFreshness(): array
    {
        try { $last = Database::connection()->query('SELECT MAX(created_at) FROM stats')->fetchColumn(); }
        catch (Throwable) { $last = false; }
        try { $time = $last ? (new DateTimeImmutable((string) $last, new DateTimeZone('UTC')))->getTimestamp() : false; }
        catch (Throwable) { $time = false; }
        return ['last_event_at'=>$last ?: null,'age_hours'=>$time ? round((time()-$time)/3600,1) : null];
    }

    private static function history(): array
    {
        if (!self::tableExists('system_health_snapshots')) return ['samples'=>0,'uptime_percent'=>null,'average_response_ms'=>null];
        $row=Database::connection()->query("SELECT COUNT(*) samples,AVG(status='healthy')*100 uptime_percent,AVG(response_ms) average_response_ms FROM system_health_snapshots WHERE checked_at>=UTC_TIMESTAMP()-INTERVAL 30 DAY")->fetch()?:[];
        return ['samples'=>(int)($row['samples']??0),'uptime_percent'=>isset($row['uptime_percent'])?round((float)$row['uptime_percent'],2):null,'average_response_ms'=>isset($row['average_response_ms'])?round((float)$row['average_response_ms']):null];
    }

    private static function uploads(): array
    {
        $root=dirname(__DIR__,2);$directory=is_dir($root.'/public_html/uploads')?$root.'/public_html/uploads':$root.'/frontend/uploads';
        return self::directorySize($directory);
    }

    private static function database(): array
    {
        try { $s=Database::connection()->query('SELECT SUM(data_length+index_length) bytes FROM information_schema.tables WHERE table_schema=DATABASE()');return ['size_bytes'=>(int)($s->fetchColumn()?:0),'ok'=>true]; }
        catch(Throwable){return ['size_bytes'=>0,'ok'=>false];}
    }

    private static function directorySize(string $directory): array
    {
        $bytes=0;$files=0;
        try { if(is_dir($directory)){foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory,FilesystemIterator::SKIP_DOTS)) as $file){if($file->isFile()){$bytes+=$file->getSize();$files++;}}} }
        catch (Throwable) {}
        return ['size_bytes'=>$bytes,'files'=>$files];
    }

    private static function alert(string $severity,string $title,string $message): array { return compact('severity','title','message'); }
    private static function notify(array $report): void
    {
        $recipient = trim(Config::get('ALERT_EMAIL', ''));
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL) || empty($report['alerts']) || !function_exists('mail')) return;
        $stateFile = dirname(__DIR__) . '/storage/logs/.monitor-alert-state.json';
        $signature = hash('sha256', json_encode(array_column($report['alerts'], 'title')) ?: 'alerts');
        $state = is_file($stateFile) ? json_decode((string) file_get_contents($stateFile), true) : [];
        $cooldown = max(1, (int) Config::get('ALERT_COOLDOWN_HOURS', '6')) * 3600;
        if (($state['signature'] ?? '') === $signature && time() - (int) ($state['sent_at'] ?? 0) < $cooldown) return;
        $lines = ['Peringatan kesehatan website DDU:', ''];
        foreach ($report['alerts'] as $alert) $lines[] = strtoupper((string) $alert['severity']) . ' - ' . $alert['title'] . ': ' . $alert['message'];
        $lines[] = ''; $lines[] = 'Diperiksa: ' . $report['checked_at'];
        $headers = 'From: monitor@' . (parse_url(Config::get('APP_URL', ''), PHP_URL_HOST) ?: 'localhost')
            . "\r\nContent-Type: text/plain; charset=UTF-8";
        if (@mail($recipient, '[DDU] Peringatan kesehatan website', implode(PHP_EOL, $lines), $headers)) {
            @file_put_contents($stateFile, json_encode(['signature'=>$signature,'sent_at'=>time()]), LOCK_EX);
        }
    }
    private static function tableExists(string $table): bool { try{$s=Database::connection()->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=:table');$s->execute(['table'=>$table]);return(int)$s->fetchColumn()>0;}catch(Throwable){return false;} }
}
