-- Jalankan file ini di phpMyAdmin jika schema.sql sudah pernah diimpor sebelumnya.
CREATE TABLE IF NOT EXISTS admins (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(120) NOT NULL DEFAULT '',
    role ENUM('super_admin', 'admin') NOT NULL DEFAULT 'admin',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_admins_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Contoh tambah admin (buat hash bcrypt terlebih dahulu, lalu ganti semua nilai contoh):
-- INSERT INTO admins (email, password_hash, display_name, role)
-- VALUES ('admin@domainanda.com', '$2y$10$GANTI_DENGAN_HASH_BCRYPT', 'Administrator Utama', 'super_admin');

