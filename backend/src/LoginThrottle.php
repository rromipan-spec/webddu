<?php
declare(strict_types=1);

final class LoginThrottle
{
    private const WINDOW_SECONDS = 900;
    private const BLOCK_SECONDS = 900;
    private const ACCOUNT_LIMIT = 5;
    private const IP_LIMIT = 30;

    public static function retryAfter(string $email): int
    {
        try {
            return self::withState(static function (array &$state) use ($email): int {
                $now = time();
                self::prune($state, $now);
                $retryAfter = 0;
                foreach (self::keys($email) as $key => $limit) {
                    $bucket = $state['buckets'][$key] ?? null;
                    if (!is_array($bucket)) {
                        continue;
                    }
                    $blockedUntil = (int) ($bucket['blocked_until'] ?? 0);
                    if ($blockedUntil > $now) {
                        $retryAfter = max($retryAfter, $blockedUntil - $now);
                    }
                }
                return $retryAfter;
            });
        } catch (Throwable $error) {
            error_log('[LoginThrottle] ' . $error->getMessage());
            return 0;
        }
    }

    public static function recordFailure(string $email): void
    {
        try {
            self::withState(static function (array &$state) use ($email) {
                $now = time();
                self::prune($state, $now);
                foreach (self::keys($email) as $key => $limit) {
                    $bucket = $state['buckets'][$key] ?? [
                        'window_started' => $now,
                        'count' => 0,
                        'blocked_until' => 0,
                    ];
                    if (($now - (int) $bucket['window_started']) >= self::WINDOW_SECONDS) {
                        $bucket = ['window_started' => $now, 'count' => 0, 'blocked_until' => 0];
                    }
                    $bucket['count'] = (int) $bucket['count'] + 1;
                    if ($bucket['count'] >= $limit) {
                        $bucket['blocked_until'] = $now + self::BLOCK_SECONDS;
                    }
                    $state['buckets'][$key] = $bucket;
                }
                return null;
            });
        } catch (Throwable $error) {
            error_log('[LoginThrottle] ' . $error->getMessage());
        }
    }

    public static function recordSuccess(string $email): void
    {
        try {
            self::withState(static function (array &$state) use ($email) {
                unset($state['buckets'][self::accountKey($email)]);
                return null;
            });
        } catch (Throwable $error) {
            error_log('[LoginThrottle] ' . $error->getMessage());
        }
    }

    private static function keys(string $email): array
    {
        return [
            self::accountKey($email) => self::ACCOUNT_LIMIT,
            'ip:' . hash('sha256', self::clientAddress()) => self::IP_LIMIT,
        ];
    }

    private static function accountKey(string $email): string
    {
        return 'account:' . hash('sha256', strtolower(trim($email)));
    }

    private static function clientAddress(): string
    {
        // REMOTE_ADDR tidak menerima nilai header buatan pengguna secara langsung.
        return (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    }

    private static function prune(array &$state, int $now): void
    {
        $state['buckets'] = is_array($state['buckets'] ?? null) ? $state['buckets'] : [];
        foreach ($state['buckets'] as $key => $bucket) {
            if (!is_array($bucket)) {
                unset($state['buckets'][$key]);
                continue;
            }
            $windowStarted = (int) ($bucket['window_started'] ?? 0);
            $blockedUntil = (int) ($bucket['blocked_until'] ?? 0);
            if ($blockedUntil <= $now && ($now - $windowStarted) > (self::WINDOW_SECONDS * 2)) {
                unset($state['buckets'][$key]);
            }
        }
    }

    private static function withState(callable $callback): mixed
    {
        $directory = dirname(__DIR__) . '/storage/security';
        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            throw new RuntimeException('Folder keamanan tidak dapat dibuat.');
        }
        $path = $directory . '/login-throttle.json';
        $handle = fopen($path, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Penyimpanan pembatas login tidak dapat dibuka.');
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Penyimpanan pembatas login sedang terkunci.');
            }
            rewind($handle);
            $raw = stream_get_contents($handle);
            $decoded = is_string($raw) && $raw !== '' ? json_decode($raw, true) : [];
            $state = is_array($decoded) ? $decoded : [];
            $result = $callback($state);
            $json = json_encode($state, JSON_UNESCAPED_SLASHES);
            if ($json === false) {
                throw new RuntimeException('Data pembatas login tidak dapat diproses.');
            }
            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, $json);
            fflush($handle);
            flock($handle, LOCK_UN);
            return $result;
        } finally {
            fclose($handle);
        }
    }
}
