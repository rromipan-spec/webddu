-- Jalankan satu kali melalui phpMyAdmin pada database produksi.
-- Menambahkan pemutusan sesi lama setelah password berubah dan audit keamanan login.

ALTER TABLE admins
    ADD COLUMN IF NOT EXISTS session_version INT UNSIGNED NOT NULL DEFAULT 1 AFTER is_active;

CREATE TABLE IF NOT EXISTS login_security_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id BIGINT UNSIGNED NULL,
    email VARCHAR(190) NOT NULL DEFAULT '',
    event_type ENUM('success', 'failure', 'blocked', 'password_changed', 'password_reset', 'profile_updated') NOT NULL,
    ip_hash CHAR(64) NOT NULL DEFAULT '',
    user_agent VARCHAR(255) NOT NULL DEFAULT '',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_login_security_admin (admin_id, created_at),
    INDEX idx_login_security_email (email, created_at),
    INDEX idx_login_security_type (event_type, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
