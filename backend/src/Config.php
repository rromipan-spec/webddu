<?php
declare(strict_types=1);

final class Config
{
    private static array $values = [];

    public static function load(string $path): void
    {
        if (!is_file($path)) {
            throw new RuntimeException('File konfigurasi .env belum tersedia.');
        }

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = array_map('trim', explode('=', $line, 2));
            self::$values[$key] = trim($value, "\"'");
        }
    }

    public static function get(string $key, ?string $default = null): string
    {
        $value = self::$values[$key] ?? getenv($key) ?: $default;
        if ($value === null) {
            throw new RuntimeException("Konfigurasi {$key} belum diatur.");
        }
        return (string) $value;
    }
}

