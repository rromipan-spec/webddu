<?php
declare(strict_types=1);

final class HealthCheck
{
    private const REQUIRED_TABLES = [
        'admins', 'posts', 'programs', 'stats', 'content_history', 'institution_profile',
    ];
    private const REQUIRED_EXTENSIONS = ['pdo_mysql', 'mbstring', 'dom', 'fileinfo', 'gd', 'zip'];

    public static function run(): array
    {
        $checks = [];
        $checks['php'] = self::check(
            version_compare(PHP_VERSION, '8.1.0', '>='),
            'PHP 8.1 atau lebih baru tersedia.'
        );
        $missingExtensions = array_values(array_filter(
            self::REQUIRED_EXTENSIONS,
            static fn(string $extension): bool => !extension_loaded($extension)
        ));
        $checks['extensions'] = [
            'ok' => $missingExtensions === [] && function_exists('imagewebp'),
            'message' => $missingExtensions === [] && function_exists('imagewebp')
                ? 'Semua ekstensi PHP penting tersedia.'
                : 'Ada ekstensi PHP penting yang belum tersedia.',
            'missing' => $missingExtensions,
            'webp' => function_exists('imagewebp'),
        ];
        $checks['database'] = self::databaseCheck();
        $checks['tables'] = self::tableCheck();
        $checks['uploads'] = self::directoryCheck(self::uploadsDirectory(), false);
        $checks['logs'] = self::directoryCheck(dirname(__DIR__) . '/storage/logs', true);

        $healthy = !in_array(false, array_column($checks, 'ok'), true);
        return [
            'ok' => $healthy,
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'checked_at' => gmdate('c'),
            'checks' => $checks,
        ];
    }

    public static function publicResult(array $result): array
    {
        return [
            'ok' => (bool) ($result['ok'] ?? false),
            'status' => (string) ($result['status'] ?? 'unhealthy'),
            'checked_at' => (string) ($result['checked_at'] ?? gmdate('c')),
        ];
    }

    private static function databaseCheck(): array
    {
        try {
            $value = Database::connection()->query('SELECT 1')->fetchColumn();
            return self::check((int) $value === 1, 'Koneksi database berhasil.');
        } catch (Throwable $error) {
            AppLogger::exception($error);
            return self::check(false, 'Koneksi database gagal.');
        }
    }

    private static function tableCheck(): array
    {
        try {
            $placeholders = implode(',', array_fill(0, count(self::REQUIRED_TABLES), '?'));
            $statement = Database::connection()->prepare(
                "SELECT table_name FROM information_schema.tables
                 WHERE table_schema = DATABASE() AND table_name IN ({$placeholders})"
            );
            $statement->execute(self::REQUIRED_TABLES);
            $available = array_map('strval', $statement->fetchAll(PDO::FETCH_COLUMN));
            $missing = array_values(array_diff(self::REQUIRED_TABLES, $available));
            return [
                'ok' => $missing === [],
                'message' => $missing === [] ? 'Semua tabel penting tersedia.' : 'Ada tabel penting yang belum tersedia.',
                'missing' => $missing,
            ];
        } catch (Throwable $error) {
            AppLogger::exception($error);
            return self::check(false, 'Pemeriksaan tabel gagal.');
        }
    }

    private static function directoryCheck(string $directory, bool $create): array
    {
        if ($create && !is_dir($directory)) {
            @mkdir($directory, 0700, true);
        }
        return self::check(is_dir($directory) && is_writable($directory), 'Folder tersedia dan dapat ditulis.');
    }

    private static function uploadsDirectory(): string
    {
        $domainRoot = dirname(__DIR__, 2);
        $production = $domainRoot . '/public_html/uploads';
        return is_dir($production) ? $production : $domainRoot . '/frontend/uploads';
    }

    private static function check(bool $ok, string $message): array
    {
        return ['ok' => $ok, 'message' => $message];
    }
}
