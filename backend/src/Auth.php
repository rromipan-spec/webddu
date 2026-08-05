<?php
declare(strict_types=1);

final class Auth
{
    private static ?bool $requestCheck = null;

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
        if (self::$requestCheck !== null) {
            return self::$requestCheck;
        }
        if (!isset($_SESSION['admin_id'], $_SESSION['admin_email'], $_SESSION['last_activity'])
            || (time() - (int) $_SESSION['last_activity']) > 3600) {
            return self::$requestCheck = false;
        }

        try {
            $admin = self::adminIdentity((int) $_SESSION['admin_id']);
            $sessionVersion = (int) ($_SESSION['admin_session_version'] ?? 1);
            if (!$admin || (int) $admin['is_active'] !== 1 || (int) $admin['session_version'] !== $sessionVersion) {
                return self::$requestCheck = false;
            }
            if (!self::syncTrackedSession((int) $admin['id'])) {
                return self::$requestCheck = false;
            }
            $_SESSION['admin_email'] = (string) $admin['email'];
            $_SESSION['admin_role'] = (string) $admin['role'];
            return self::$requestCheck = true;
        } catch (Throwable $error) {
            error_log('[Auth] Pemeriksaan sesi gagal: ' . $error->getMessage());
            return self::$requestCheck = false;
        }
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
            self::recordSecurityEvent('blocked', $email);
            header('Retry-After: ' . $retryAfter);
            Http::json(['ok' => false, 'message' => 'Terlalu banyak percobaan. Coba lagi beberapa menit.'], 429);
        }

        try {
            $stmt = Database::connection()->prepare(
                'SELECT id, email, password_hash, role, is_active, session_version FROM admins WHERE email = :email LIMIT 1'
            );
            $stmt->execute(['email' => $email]);
            $admin = $stmt->fetch();
        } catch (Throwable) {
            $stmt = Database::connection()->prepare(
                'SELECT id, email, password_hash, role, is_active, 1 AS session_version FROM admins WHERE email = :email LIMIT 1'
            );
            $stmt->execute(['email' => $email]);
            $admin = $stmt->fetch();
        }

        // Hash dummy menjaga waktu respons relatif seragam saat email tidak ditemukan.
        $hash = $admin['password_hash'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
        $passwordValid = password_verify($password, $hash);
        $valid = is_array($admin) && (int) $admin['is_active'] === 1 && $passwordValid;

        if (!$valid) {
            LoginThrottle::recordFailure($email);
            self::recordSecurityEvent('failure', $email, is_array($admin) ? (int) $admin['id'] : null);
            return false;
        }

        LoginThrottle::recordSuccess($email);
        session_regenerate_id(true);
        self::refreshSession(
            (int) $admin['id'],
            (string) $admin['email'],
            (string) $admin['role'],
            (int) ($admin['session_version'] ?? 1)
        );
        $update = Database::connection()->prepare('UPDATE admins SET last_login_at = NOW() WHERE id = :id');
        $update->execute(['id' => (int) $admin['id']]);
        self::recordSecurityEvent('success', $email, (int) $admin['id']);
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

    public static function email(): string
    {
        return (string) ($_SESSION['admin_email'] ?? '');
    }

    public static function refreshSession(int $id, string $email, string $role, int $sessionVersion): void
    {
        $_SESSION['admin_id'] = $id;
        $_SESSION['admin_email'] = $email;
        $_SESSION['admin_role'] = $role;
        $_SESSION['admin_session_version'] = max(1, $sessionVersion);
        $_SESSION['last_activity'] = time();
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        self::$requestCheck = true;
    }

    public static function recordSecurityEvent(string $eventType, string $email, ?int $adminId = null): void
    {
        $allowed = ['success', 'failure', 'blocked', 'password_changed', 'password_reset', 'profile_updated'];
        if (!in_array($eventType, $allowed, true)) {
            return;
        }
        try {
            $statement = Database::connection()->prepare(
                'INSERT INTO login_security_events (admin_id, email, event_type, ip_hash, user_agent)
                 VALUES (:admin_id, :email, :event_type, :ip_hash, :user_agent)'
            );
            $statement->execute([
                'admin_id' => $adminId,
                'email' => strtolower(trim($email)),
                'event_type' => $eventType,
                'ip_hash' => self::clientAddressHash(),
                'user_agent' => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            ]);
        } catch (Throwable $error) {
            // Login tetap dapat dipakai sebelum migrasi tabel keamanan dijalankan.
            error_log('[AuthSecurityEvent] ' . $error->getMessage());
        }
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
        self::revokeCurrentSession();
        self::$requestCheck = null;
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], '', (bool) $params['secure'], true);
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public static function sessions(?int $adminId = null): array
    {
        $targetId = $adminId ?: self::id();
        if ($targetId !== self::id() && self::role() !== 'super_admin') {
            return [];
        }
        try {
            $statement = Database::connection()->prepare(
                'SELECT s.id, s.admin_id, a.email, a.display_name, s.device_type,
                        s.os_family, s.browser_family, s.ip_hint, s.created_at, s.last_seen_at, s.revoked_at,
                        CASE WHEN s.token_hash = :current_token THEN 1 ELSE 0 END AS is_current,
                        CASE WHEN NOT EXISTS (
                            SELECT 1 FROM admin_sessions prior_session
                            WHERE prior_session.admin_id = s.admin_id AND prior_session.id < s.id
                              AND prior_session.device_type = s.device_type
                              AND prior_session.os_family = s.os_family
                              AND prior_session.browser_family = s.browser_family
                        ) THEN 1 ELSE 0 END AS is_first_device
                 FROM admin_sessions s
                 INNER JOIN admins a ON a.id = s.admin_id
                 WHERE (:all_admins = 1 OR s.admin_id = :admin_id)
                 ORDER BY (s.revoked_at IS NULL) DESC, s.last_seen_at DESC
                 LIMIT 100'
            );
            $statement->execute([
                'current_token' => self::sessionTokenHash(),
                'all_admins' => $adminId === null && self::role() === 'super_admin' ? 1 : 0,
                'admin_id' => $targetId,
            ]);
            return $statement->fetchAll();
        } catch (Throwable $error) {
            error_log('[AdminSessions] ' . $error->getMessage());
            return [];
        }
    }

    public static function revokeSession(int $sessionId): bool
    {
        try {
            $statement = Database::connection()->prepare(
                'SELECT id, admin_id, token_hash FROM admin_sessions WHERE id = :id LIMIT 1'
            );
            $statement->execute(['id' => $sessionId]);
            $session = $statement->fetch();
            if (!$session
                || ((int) $session['admin_id'] !== self::id() && self::role() !== 'super_admin')
                || hash_equals((string) $session['token_hash'], self::sessionTokenHash())) {
                return false;
            }
            $update = Database::connection()->prepare(
                'UPDATE admin_sessions SET revoked_at = UTC_TIMESTAMP() WHERE id = :id AND revoked_at IS NULL'
            );
            $update->execute(['id' => $sessionId]);
            return $update->rowCount() > 0;
        } catch (Throwable $error) {
            error_log('[AdminSessionRevoke] ' . $error->getMessage());
            return false;
        }
    }

    public static function revokeAllSessions(int $adminId): void
    {
        try {
            $statement = Database::connection()->prepare(
                'UPDATE admin_sessions SET revoked_at = UTC_TIMESTAMP()
                 WHERE admin_id = :admin_id AND revoked_at IS NULL'
            );
            $statement->execute(['admin_id' => $adminId]);
        } catch (Throwable $error) {
            error_log('[AdminSessionsRevokeAll] ' . $error->getMessage());
        }
    }

    private static function adminIdentity(int $id): array|false
    {
        try {
            $statement = Database::connection()->prepare(
                'SELECT id, email, role, is_active, session_version FROM admins WHERE id = :id LIMIT 1'
            );
            $statement->execute(['id' => $id]);
            return $statement->fetch();
        } catch (Throwable) {
            $statement = Database::connection()->prepare(
                'SELECT id, email, role, is_active, 1 AS session_version FROM admins WHERE id = :id LIMIT 1'
            );
            $statement->execute(['id' => $id]);
            return $statement->fetch();
        }
    }

    private static function clientAddressHash(): string
    {
        $address = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $salt = Config::get('DB_NAME', 'ddu') . '|' . Config::get('APP_URL', '');
        return hash('sha256', $salt . '|' . $address);
    }

    private static function syncTrackedSession(int $adminId): bool
    {
        try {
            $tokenHash = self::sessionTokenHash();
            $statement = Database::connection()->prepare(
                'SELECT id, revoked_at FROM admin_sessions WHERE token_hash = :token_hash LIMIT 1'
            );
            $statement->execute(['token_hash' => $tokenHash]);
            $tracked = $statement->fetch();
            if ($tracked && $tracked['revoked_at'] !== null) return false;

            if (!$tracked) {
                $client = Analytics::clientInfo((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
                $insert = Database::connection()->prepare(
                    'INSERT INTO admin_sessions
                        (admin_id, token_hash, device_type, os_family, browser_family, ip_hash, ip_hint)
                     VALUES (:admin_id, :token_hash, :device_type, :os_family, :browser_family, :ip_hash, :ip_hint)'
                );
                $insert->execute([
                    'admin_id' => $adminId,
                    'token_hash' => $tokenHash,
                    'device_type' => $client['device'],
                    'os_family' => $client['os'],
                    'browser_family' => $client['browser'],
                    'ip_hash' => self::clientAddressHash(),
                    'ip_hint' => self::clientAddressHint(),
                ]);
            } elseif ((time() - (int) ($_SESSION['tracked_session_touch'] ?? 0)) >= 60) {
                $update = Database::connection()->prepare(
                    'UPDATE admin_sessions SET last_seen_at = UTC_TIMESTAMP() WHERE id = :id'
                );
                $update->execute(['id' => (int) $tracked['id']]);
            }
            $_SESSION['tracked_session_touch'] = time();
            return true;
        } catch (Throwable $error) {
            // Login tetap berfungsi sebelum migrasi analitik dijalankan.
            error_log('[AdminSessionTracking] ' . $error->getMessage());
            return true;
        }
    }

    private static function revokeCurrentSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE || session_id() === '') return;
        try {
            $statement = Database::connection()->prepare(
                'UPDATE admin_sessions SET revoked_at = UTC_TIMESTAMP()
                 WHERE token_hash = :token_hash AND revoked_at IS NULL'
            );
            $statement->execute(['token_hash' => self::sessionTokenHash()]);
        } catch (Throwable) {
            // Tabel sesi bersifat kompatibel mundur.
        }
    }

    private static function sessionTokenHash(): string
    {
        $key = Config::get('ANALYTICS_HASH_KEY', Config::get('APP_URL', '') . '|' . Config::get('DB_NAME', 'ddu'));
        return hash_hmac('sha256', 'admin-session|' . session_id(), $key);
    }

    private static function clientAddressHint(): string
    {
        $address = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $address);
            return $parts[0] . '.' . $parts[1] . '.xxx.xxx';
        }
        if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = array_values(array_filter(explode(':', $address), static fn(string $part): bool => $part !== ''));
            return implode(':', array_slice($parts, 0, 2)) . ':xxxx:xxxx';
        }
        return 'Tidak diketahui';
    }
}
