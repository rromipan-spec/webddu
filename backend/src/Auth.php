<?php
declare(strict_types=1);

final class Auth
{
    public static function start(): void
    {
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
        session_name('ddu_admin');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public static function check(): bool
    {
        return isset($_SESSION['admin_id'], $_SESSION['admin_email'], $_SESSION['last_activity'])
            && (time() - (int) $_SESSION['last_activity']) <= 3600;
    }

    public static function requireAdmin(): void
    {
        if (!self::check()) {
            self::logout();
            Http::json(['ok' => false, 'message' => 'Sesi login berakhir.'], 401);
        }
        $_SESSION['last_activity'] = time();
    }

    public static function login(string $email, string $password): bool
    {
        $email = strtolower(trim($email));
        $retryAfter = LoginThrottle::retryAfter($email);
        if ($retryAfter > 0) {
            header('Retry-After: ' . $retryAfter);
            Http::json(['ok' => false, 'message' => 'Terlalu banyak percobaan. Coba lagi beberapa menit.'], 429);
        }

        $stmt = Database::connection()->prepare(
            'SELECT id, email, password_hash, role, is_active FROM admins WHERE email = :email LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $admin = $stmt->fetch();

        // Hash dummy menjaga waktu respons relatif seragam saat email tidak ditemukan.
        $hash = $admin['password_hash'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
        $passwordValid = password_verify($password, $hash);
        $valid = is_array($admin) && (int) $admin['is_active'] === 1 && $passwordValid;

        if (!$valid) {
            LoginThrottle::recordFailure($email);
            return false;
        }

        LoginThrottle::recordSuccess($email);
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int) $admin['id'];
        $_SESSION['admin_email'] = (string) $admin['email'];
        $_SESSION['admin_role'] = (string) $admin['role'];
        $_SESSION['last_activity'] = time();
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        $update = Database::connection()->prepare('UPDATE admins SET last_login_at = NOW() WHERE id = :id');
        $update->execute(['id' => (int) $admin['id']]);
        return true;
    }

    public static function csrf(): string
    {
        return (string) ($_SESSION['csrf'] ?? '');
    }

    public static function role(): string
    {
        return (string) ($_SESSION['admin_role'] ?? '');
    }

    public static function id(): int
    {
        return (int) ($_SESSION['admin_id'] ?? 0);
    }

    public static function requireSuperAdmin(): void
    {
        self::requireAdmin();
        if (self::role() !== 'super_admin') {
            Http::json(['ok' => false, 'message' => 'Hanya super admin yang dapat mengelola akun admin.'], 403);
        }
    }

    public static function verifyCsrf(): void
    {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if ($token === '' || !hash_equals(self::csrf(), $token)) {
            Http::json(['ok' => false, 'message' => 'Token keamanan tidak valid. Muat ulang halaman.'], 419);
        }
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], '', (bool) $params['secure'], true);
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}
