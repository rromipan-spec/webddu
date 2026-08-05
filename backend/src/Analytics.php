<?php
declare(strict_types=1);

final class Analytics
{
    public static function record(array $body): void
    {
        $type = (string) ($body['type'] ?? '');
        if (!in_array($type, ['visit', 'wa_click'], true) || self::isBot()) {
            return;
        }

        $path = self::pagePath((string) ($body['page'] ?? '/'));
        $rateKey = 'last_stat_' . hash('sha256', $type . '|' . $path);
        if ((time() - (int) ($_SESSION[$rateKey] ?? 0)) < ($type === 'visit' ? 10 : 2)) {
            return;
        }

        [$contentType, $contentSlug] = self::contentIdentity($path);
        $client = self::clientInfo((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
        $visitorHash = self::anonymousHash((string) ($body['visitor_id'] ?? ''), true);
        $sessionHash = self::anonymousHash((string) ($body['session_id'] ?? ''), false);

        try {
            $statement = Database::connection()->prepare(
                'INSERT INTO stats
                    (type, page_path, content_type, content_slug, visitor_hash, session_hash,
                     device_type, os_family, browser_family, referrer_source, screen_bucket)
                 VALUES
                    (:type, :page_path, :content_type, :content_slug, :visitor_hash, :session_hash,
                     :device_type, :os_family, :browser_family, :referrer_source, :screen_bucket)'
            );
            $statement->execute([
                'type' => $type,
                'page_path' => $path,
                'content_type' => $contentType,
                'content_slug' => $contentSlug,
                'visitor_hash' => $visitorHash,
                'session_hash' => $sessionHash,
                'device_type' => $client['device'],
                'os_family' => $client['os'],
                'browser_family' => $client['browser'],
                'referrer_source' => self::referrerSource((string) ($body['referrer'] ?? '')),
                'screen_bucket' => self::screenBucket((int) ($body['screen_width'] ?? 0)),
            ]);
        } catch (Throwable $error) {
            // Website tetap mencatat hit dasar sebelum migrasi analitik dijalankan.
            $statement = Database::connection()->prepare('INSERT INTO stats (type) VALUES (:type)');
            $statement->execute(['type' => $type]);
            error_log('[AnalyticsMigration] ' . $error->getMessage());
        }

        $_SESSION[$rateKey] = time();
        if (random_int(1, 100) === 1) self::prune();
    }

    public static function report(int $days = 30): array
    {
        $days = max(7, min(90, $days));
        $db = Database::connection();
        if (!self::hasDimensions()) {
            $rows = $db->query('SELECT type, COUNT(*) AS total FROM stats GROUP BY type')->fetchAll();
            $summary = ['page_views' => 0, 'wa_clicks' => 0, 'visitors' => 0, 'sessions' => 0];
            foreach ($rows as $row) {
                if ($row['type'] === 'visit') $summary['page_views'] = (int) $row['total'];
                if ($row['type'] === 'wa_click') $summary['wa_clicks'] = (int) $row['total'];
            }
            return self::emptyReport($days, $summary, true);
        }

        $start = gmdate('Y-m-d H:i:s', time() - ($days - 1) * 86400);
        $summaryStatement = $db->prepare(
            "SELECT
                SUM(type = 'visit') AS page_views,
                SUM(type = 'wa_click') AS wa_clicks,
                COUNT(DISTINCT NULLIF(visitor_hash, '')) AS visitors,
                COUNT(DISTINCT NULLIF(session_hash, '')) AS sessions
             FROM stats WHERE created_at >= :start"
        );
        $summaryStatement->execute(['start' => $start]);
        $summary = $summaryStatement->fetch() ?: [];
        $summary = array_map('intval', [
            'page_views' => $summary['page_views'] ?? 0,
            'wa_clicks' => $summary['wa_clicks'] ?? 0,
            'visitors' => $summary['visitors'] ?? 0,
            'sessions' => $summary['sessions'] ?? 0,
        ]);
        $summary['conversion_rate'] = $summary['sessions'] > 0
            ? round(($summary['wa_clicks'] / $summary['sessions']) * 100, 1)
            : 0.0;

        return [
            'days' => $days,
            'retention_days' => self::retentionDays(),
            'migration_required' => false,
            'summary' => $summary,
            'daily' => self::daily($db, $start),
            'devices' => self::grouped($db, 'device_type', $start, 5),
            'operating_systems' => self::grouped($db, 'os_family', $start, 8),
            'browsers' => self::grouped($db, 'browser_family', $start, 8),
            'sources' => self::grouped($db, 'referrer_source', $start, 10),
            'screens' => self::grouped($db, 'screen_bucket', $start, 8),
            'pages' => self::topPages($db, $start),
            'whatsapp_pages' => self::topWhatsAppPages($db, $start),
            'visitor_types' => self::visitorTypes($db, $start),
        ];
    }

    public static function clientInfo(string $userAgent): array
    {
        $ua = strtolower($userAgent);
        if (preg_match('/bot|crawler|spider|slurp|headless|lighthouse/', $ua)) {
            return ['device' => 'unknown', 'os' => 'Bot', 'browser' => 'Bot'];
        }
        $device = preg_match('/ipad|tablet|kindle|silk/', $ua) || (str_contains($ua, 'android') && !str_contains($ua, 'mobile'))
            ? 'tablet'
            : (preg_match('/mobile|iphone|ipod|android/', $ua) ? 'mobile' : 'desktop');
        $os = match (true) {
            str_contains($ua, 'android') => 'Android',
            preg_match('/iphone|ipad|ipod/', $ua) === 1 => 'iOS/iPadOS',
            str_contains($ua, 'windows') => 'Windows',
            str_contains($ua, 'cros') => 'ChromeOS',
            str_contains($ua, 'mac os') || str_contains($ua, 'macintosh') => 'macOS',
            str_contains($ua, 'linux') => 'Linux',
            default => 'Lainnya',
        };
        $browser = match (true) {
            str_contains($ua, 'edg/') || str_contains($ua, 'edgios') => 'Edge',
            str_contains($ua, 'opr/') || str_contains($ua, 'opera') => 'Opera',
            str_contains($ua, 'samsungbrowser') => 'Samsung Internet',
            str_contains($ua, 'firefox') || str_contains($ua, 'fxios') => 'Firefox',
            str_contains($ua, 'crios') || str_contains($ua, 'chrome') => 'Chrome',
            str_contains($ua, 'safari') => 'Safari',
            default => 'Lainnya',
        };
        return ['device' => $device, 'os' => $os, 'browser' => $browser];
    }

    public static function prune(): int
    {
        $cutoff = gmdate('Y-m-d H:i:s', time() - self::retentionDays() * 86400);
        $statement = Database::connection()->prepare('DELETE FROM stats WHERE created_at < :cutoff');
        $statement->execute(['cutoff' => $cutoff]);
        $deleted = $statement->rowCount();
        try {
            $sessions = Database::connection()->prepare(
                'DELETE FROM admin_sessions
                 WHERE (revoked_at IS NOT NULL AND revoked_at < :revoked_cutoff)
                    OR last_seen_at < :seen_cutoff'
            );
            $sessions->execute(['revoked_cutoff' => $cutoff, 'seen_cutoff' => $cutoff]);
            $deleted += $sessions->rowCount();
        } catch (Throwable) {
            // Tabel sesi mungkin belum dibuat saat migrasi belum dijalankan.
        }
        return $deleted;
    }

    private static function grouped(PDO $db, string $column, string $start, int $limit): array
    {
        $allowed = ['device_type', 'os_family', 'browser_family', 'referrer_source', 'screen_bucket'];
        if (!in_array($column, $allowed, true)) return [];
        $statement = $db->prepare(
            "SELECT {$column} AS label, COUNT(*) AS total
             FROM stats WHERE type = 'visit' AND created_at >= :start
             GROUP BY {$column} ORDER BY total DESC LIMIT {$limit}"
        );
        $statement->execute(['start' => $start]);
        return self::integerRows($statement->fetchAll());
    }

    private static function daily(PDO $db, string $start): array
    {
        $statement = $db->prepare(
            "SELECT DATE(created_at) AS label,
                    SUM(type = 'visit') AS total,
                    SUM(type = 'wa_click') AS secondary
             FROM stats WHERE created_at >= :start
             GROUP BY DATE(created_at) ORDER BY label"
        );
        $statement->execute(['start' => $start]);
        return array_map(static fn(array $row): array => [
            'label' => (string) $row['label'],
            'total' => (int) $row['total'],
            'secondary' => (int) $row['secondary'],
        ], $statement->fetchAll());
    }

    private static function topPages(PDO $db, string $start): array
    {
        $statement = $db->prepare(
            "SELECT page_path AS label, content_type, content_slug, COUNT(*) AS total
             FROM stats WHERE type = 'visit' AND created_at >= :start
             GROUP BY page_path, content_type, content_slug ORDER BY total DESC LIMIT 12"
        );
        $statement->execute(['start' => $start]);
        return array_map(static fn(array $row): array => [
            'label' => (string) $row['label'],
            'content_type' => (string) $row['content_type'],
            'content_slug' => (string) $row['content_slug'],
            'total' => (int) $row['total'],
        ], $statement->fetchAll());
    }

    private static function topWhatsAppPages(PDO $db, string $start): array
    {
        $statement = $db->prepare(
            "SELECT page_path AS label, COUNT(*) AS total
             FROM stats WHERE type = 'wa_click' AND created_at >= :start
             GROUP BY page_path ORDER BY total DESC LIMIT 10"
        );
        $statement->execute(['start' => $start]);
        return self::integerRows($statement->fetchAll());
    }

    private static function visitorTypes(PDO $db, string $start): array
    {
        $statement = $db->prepare(
            "SELECT
                SUM(first_seen >= :start_new) AS new_visitors,
                SUM(first_seen < :start_returning AND last_seen >= :start_active) AS returning_visitors
             FROM (
                SELECT visitor_hash, MIN(created_at) AS first_seen, MAX(created_at) AS last_seen
                FROM stats WHERE visitor_hash <> '' AND type = 'visit' GROUP BY visitor_hash
             ) visitors"
        );
        $statement->execute([
            'start_new' => $start,
            'start_returning' => $start,
            'start_active' => $start,
        ]);
        $row = $statement->fetch() ?: [];
        return [
            ['label' => 'Pengunjung baru', 'total' => (int) ($row['new_visitors'] ?? 0)],
            ['label' => 'Pengunjung kembali', 'total' => (int) ($row['returning_visitors'] ?? 0)],
        ];
    }

    private static function integerRows(array $rows): array
    {
        return array_map(static fn(array $row): array => [
            'label' => (string) ($row['label'] ?: 'Tidak diketahui'),
            'total' => (int) $row['total'],
        ], $rows);
    }

    private static function emptyReport(int $days, array $summary, bool $migrationRequired): array
    {
        $summary['conversion_rate'] = 0.0;
        return [
            'days' => $days,
            'retention_days' => self::retentionDays(),
            'migration_required' => $migrationRequired,
            'summary' => $summary,
            'daily' => [], 'devices' => [], 'operating_systems' => [], 'browsers' => [],
            'sources' => [], 'screens' => [], 'pages' => [], 'whatsapp_pages' => [],
            'visitor_types' => [],
        ];
    }

    private static function anonymousHash(string $identifier, bool $visitor): string
    {
        $identifier = preg_replace('/[^a-zA-Z0-9._-]/', '', $identifier) ?? '';
        if (strlen($identifier) < 16 || strlen($identifier) > 100) {
            $fallback = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown')
                . '|' . (string) ($_SERVER['HTTP_USER_AGENT'] ?? '')
                . ($visitor ? '' : '|' . gmdate('Y-m-d-H'));
            $identifier = $fallback;
        }
        $key = Config::get('ANALYTICS_HASH_KEY', Config::get('APP_URL', '') . '|' . Config::get('DB_NAME', 'ddu'));
        return hash_hmac('sha256', ($visitor ? 'visitor|' : 'session|') . $identifier, $key);
    }

    private static function pagePath(string $value): string
    {
        $path = parse_url($value, PHP_URL_PATH) ?: '/';
        $path = '/' . ltrim((string) $path, '/');
        if (!preg_match('#^/[a-zA-Z0-9/_.-]*$#', $path)) return '/';
        return mb_substr($path, 0, 255);
    }

    private static function contentIdentity(string $path): array
    {
        if (preg_match('#^/artikel/([a-z0-9]+(?:-[a-z0-9]+)*)/?$#', $path, $matches)) {
            return ['article', $matches[1]];
        }
        $reserved = ['admin', 'api', 'uploads', 'about.html', 'index.html', 'transparansi.html', 'kebijakan-privasi.html'];
        if (preg_match('#^/([a-z0-9]+(?:-[a-z0-9]+)*)/?$#', $path, $matches)
            && !in_array($matches[1], $reserved, true)) {
            return ['program', $matches[1]];
        }
        return ['page', ''];
    }

    private static function referrerSource(string $referrer): string
    {
        if ($referrer === '') return 'Langsung';
        $host = strtolower((string) parse_url($referrer, PHP_URL_HOST));
        $ownHost = strtolower((string) parse_url(Config::get('APP_URL', ''), PHP_URL_HOST));
        if ($host === '' || $host === $ownHost || str_ends_with($host, '.' . $ownHost)) return 'Internal';
        return match (true) {
            str_contains($host, 'google.') => 'Google',
            str_contains($host, 'instagram.') => 'Instagram',
            str_contains($host, 'facebook.') || $host === 'fb.com' => 'Facebook',
            str_contains($host, 'tiktok.') => 'TikTok',
            str_contains($host, 'youtube.') || $host === 'youtu.be' => 'YouTube',
            str_contains($host, 'whatsapp.') || $host === 'wa.me' => 'WhatsApp',
            str_contains($host, 'bing.') => 'Bing',
            default => mb_substr($host, 0, 80),
        };
    }

    private static function screenBucket(int $width): string
    {
        return match (true) {
            $width <= 0 => 'Tidak diketahui',
            $width <= 480 => '≤ 480 px',
            $width <= 768 => '481–768 px',
            $width <= 1024 => '769–1024 px',
            $width <= 1440 => '1025–1440 px',
            default => '> 1440 px',
        };
    }

    private static function isBot(): bool
    {
        return preg_match('/bot|crawler|spider|slurp|headless|lighthouse|preview/i', (string) ($_SERVER['HTTP_USER_AGENT'] ?? '')) === 1;
    }

    private static function retentionDays(): int
    {
        return max(30, min(365, (int) Config::get('ANALYTICS_RETENTION_DAYS', '90')));
    }

    private static function hasDimensions(): bool
    {
        try {
            $statement = Database::connection()->query("SHOW COLUMNS FROM stats LIKE 'device_type'");
            return (bool) $statement->fetch();
        } catch (Throwable) {
            return false;
        }
    }
}
