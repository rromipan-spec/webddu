<?php
declare(strict_types=1);

final class Http
{
    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function body(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || $raw === '') {
            return $_POST;
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            self::json(['ok' => false, 'message' => 'Format JSON tidak valid.'], 400);
        }
        return $data;
    }

    public static function securityHeaders(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        header('Strict-Transport-Security: max-age=31536000');
        header('Cross-Origin-Opener-Policy: same-origin');
        header('X-Permitted-Cross-Domain-Policies: none');
        header("Content-Security-Policy: default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'self'; form-action 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; connect-src 'self'; media-src 'self' https:; frame-src https://www.youtube.com https://www.youtube-nocookie.com https://drive.google.com; upgrade-insecure-requests");
        header('Cache-Control: no-store');
    }
}
