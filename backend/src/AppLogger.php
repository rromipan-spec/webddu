<?php
declare(strict_types=1);

final class AppLogger
{
    private static string $directory = '';
    private static string $file = '';
    private static bool $production = true;
    private static int $maxBytes = 5242880;
    private static int $retention = 5;
    private static bool $registered = false;
    private static bool $writing = false;
    private static array $secrets = [];

    public static function boot(string $directory): void
    {
        self::$directory = rtrim($directory, '/\\');
        self::$file = self::$directory . '/app.log';
        self::ensureDirectory();

        error_reporting(E_ALL);
        ini_set('display_errors', '0');
        ini_set('display_startup_errors', '0');
        ini_set('log_errors', '1');
        ini_set('error_log', self::$file);

        if (self::$registered) {
            return;
        }
        self::$registered = true;

        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if ((error_reporting() & $severity) === 0) {
                return false;
            }
            self::write('php_error', $message, [
                'severity' => $severity,
                'file' => $file,
                'line' => $line,
            ]);
            if (in_array($severity, [E_USER_ERROR, E_RECOVERABLE_ERROR], true)) {
                throw new ErrorException($message, 0, $severity, $file, $line);
            }
            return true;
        });

        set_exception_handler(static function (Throwable $error): void {
            $eventId = self::exception($error);
            if (PHP_SAPI === 'cli') {
                fwrite(STDERR, '[KESALAHAN] Kode kejadian: ' . $eventId . PHP_EOL);
                exit(1);
            }
            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
                header('Cache-Control: no-store');
            }
            $message = self::$production
                ? 'Terjadi kesalahan internal. Silakan coba kembali.'
                : self::redact($error->getMessage());
            echo json_encode([
                'ok' => false,
                'message' => $message,
                'event_id' => $eventId,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        });

        register_shutdown_function(static function (): void {
            $error = error_get_last();
            if (!is_array($error) || !in_array((int) $error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                return;
            }
            self::write('fatal', (string) $error['message'], [
                'severity' => (int) $error['type'],
                'file' => (string) $error['file'],
                'line' => (int) $error['line'],
            ]);
        });
    }

    public static function configure(bool $production, int $maxSizeMb, int $retention, array $secrets = []): void
    {
        self::$production = $production;
        self::$maxBytes = max(1, min(50, $maxSizeMb)) * 1024 * 1024;
        self::$retention = max(1, min(20, $retention));
        self::$secrets = array_values(array_filter(array_map('strval', $secrets), static fn(string $value): bool => strlen($value) >= 6));
        ini_set('display_errors', $production ? '0' : '1');
        ini_set('display_startup_errors', $production ? '0' : '1');
    }

    public static function exception(Throwable $error): string
    {
        return self::write('exception', $error->getMessage(), [
            'exception' => get_class($error),
            'code' => $error->getCode(),
            'file' => $error->getFile(),
            'line' => $error->getLine(),
            'trace' => self::safeTrace($error),
        ]);
    }

    public static function info(string $message, array $context = []): string
    {
        return self::write('info', $message, $context);
    }

    private static function write(string $level, string $message, array $context = []): string
    {
        $eventId = bin2hex(random_bytes(8));
        if (self::$writing) {
            return $eventId;
        }
        self::$writing = true;
        try {
            self::ensureDirectory();
            self::rotateIfNeeded();
            $entry = [
                'time' => gmdate('c'),
                'event_id' => $eventId,
                'level' => $level,
                'message' => self::redact($message),
                'request' => self::requestContext(),
                'context' => self::cleanContext($context),
            ];
            $json = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
            if (is_string($json)) {
                @file_put_contents(self::$file, $json . PHP_EOL, FILE_APPEND | LOCK_EX);
                @chmod(self::$file, 0600);
            }
        } catch (Throwable) {
            // Logging tidak boleh menyebabkan aplikasi masuk ke loop error.
        } finally {
            self::$writing = false;
        }
        return $eventId;
    }

    private static function ensureDirectory(): void
    {
        if (self::$directory === '') {
            return;
        }
        if (!is_dir(self::$directory)) {
            @mkdir(self::$directory, 0700, true);
        }
        if (is_dir(self::$directory)) {
            @chmod(self::$directory, 0700);
        }
    }

    private static function rotateIfNeeded(): void
    {
        if (!is_file(self::$file) || (int) @filesize(self::$file) < self::$maxBytes) {
            return;
        }
        for ($index = self::$retention; $index >= 1; $index--) {
            $source = $index === 1 ? self::$file : self::$file . '.' . ($index - 1);
            $target = self::$file . '.' . $index;
            if (!is_file($source)) {
                continue;
            }
            if ($index === self::$retention && is_file($target)) {
                @unlink($target);
            }
            @rename($source, $target);
            @chmod($target, 0600);
        }
    }

    private static function requestContext(): array
    {
        if (PHP_SAPI === 'cli') {
            return ['sapi' => 'cli', 'command' => basename((string) ($_SERVER['argv'][0] ?? 'php'))];
        }
        $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
        return [
            'sapi' => PHP_SAPI,
            'method' => strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')),
            'path' => is_string($path) ? mb_substr($path, 0, 500) : '/',
        ];
    }

    private static function cleanContext(array $context): array
    {
        $clean = [];
        foreach ($context as $key => $value) {
            $name = (string) $key;
            if (preg_match('/pass|password|secret|token|authorization|cookie|key/i', $name)) {
                $clean[$name] = '[REDACTED]';
            } elseif (is_scalar($value) || $value === null) {
                $clean[$name] = is_string($value) ? self::redact(mb_substr($value, 0, 2000)) : $value;
            } elseif (is_array($value)) {
                $clean[$name] = self::cleanContext($value);
            } else {
                $clean[$name] = get_debug_type($value);
            }
        }
        return $clean;
    }

    private static function redact(string $value): string
    {
        foreach (self::$secrets as $secret) {
            $value = str_replace($secret, '[REDACTED]', $value);
        }
        return (string) preg_replace(
            '/((?:pass(?:word)?|secret|token|authorization|api[_-]?key|server[_-]?key)\s*[=:]\s*)[^\s,;]+/i',
            '$1[REDACTED]',
            $value
        );
    }

    private static function safeTrace(Throwable $error): array
    {
        $trace = [];
        foreach (array_slice($error->getTrace(), 0, 20) as $frame) {
            $trace[] = [
                'file' => isset($frame['file']) ? (string) $frame['file'] : null,
                'line' => isset($frame['line']) ? (int) $frame['line'] : null,
                'function' => (string) (($frame['class'] ?? '') . ($frame['type'] ?? '') . ($frame['function'] ?? '')),
            ];
        }
        return $trace;
    }
}
