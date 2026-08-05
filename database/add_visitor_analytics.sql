-- Jalankan satu kali melalui phpMyAdmin untuk mengaktifkan analitik pengunjung
-- dan daftar perangkat login admin yang menjaga privasi.

ALTER TABLE stats
    ADD COLUMN IF NOT EXISTS page_path VARCHAR(255) NOT NULL DEFAULT '/' AFTER type,
    ADD COLUMN IF NOT EXISTS content_type ENUM('page', 'article', 'program') NOT NULL DEFAULT 'page' AFTER page_path,
    ADD COLUMN IF NOT EXISTS content_slug VARCHAR(180) NOT NULL DEFAULT '' AFTER content_type,
    ADD COLUMN IF NOT EXISTS visitor_hash CHAR(64) NOT NULL DEFAULT '' AFTER content_slug,
    ADD COLUMN IF NOT EXISTS session_hash CHAR(64) NOT NULL DEFAULT '' AFTER visitor_hash,
    ADD COLUMN IF NOT EXISTS device_type ENUM('desktop', 'mobile', 'tablet', 'unknown') NOT NULL DEFAULT 'unknown' AFTER session_hash,
    ADD COLUMN IF NOT EXISTS os_family VARCHAR(40) NOT NULL DEFAULT 'Lainnya' AFTER device_type,
    ADD COLUMN IF NOT EXISTS browser_family VARCHAR(40) NOT NULL DEFAULT 'Lainnya' AFTER os_family,
    ADD COLUMN IF NOT EXISTS referrer_source VARCHAR(80) NOT NULL DEFAULT 'Langsung' AFTER browser_family,
    ADD COLUMN IF NOT EXISTS screen_bucket VARCHAR(20) NOT NULL DEFAULT 'Tidak diketahui' AFTER referrer_source;

-- Index dibuat kondisional agar file aman dijalankan ulang.
SET @analytics_index = (
    SELECT IF(COUNT(*) = 0, 'CREATE INDEX idx_stats_created_at ON stats (created_at)', 'SELECT 1')
    FROM information_schema.statistics
    WHERE table_schema = DATABASE() AND table_name = 'stats' AND index_name = 'idx_stats_created_at'
);
PREPARE stmt_analytics FROM @analytics_index; EXECUTE stmt_analytics; DEALLOCATE PREPARE stmt_analytics;

SET @analytics_index = (
    SELECT IF(COUNT(*) = 0, 'CREATE INDEX idx_stats_page_created ON stats (page_path, created_at)', 'SELECT 1')
    FROM information_schema.statistics
    WHERE table_schema = DATABASE() AND table_name = 'stats' AND index_name = 'idx_stats_page_created'
);
PREPARE stmt_analytics FROM @analytics_index; EXECUTE stmt_analytics; DEALLOCATE PREPARE stmt_analytics;

SET @analytics_index = (
    SELECT IF(COUNT(*) = 0, 'CREATE INDEX idx_stats_device_created ON stats (device_type, created_at)', 'SELECT 1')
    FROM information_schema.statistics
    WHERE table_schema = DATABASE() AND table_name = 'stats' AND index_name = 'idx_stats_device_created'
);
PREPARE stmt_analytics FROM @analytics_index; EXECUTE stmt_analytics; DEALLOCATE PREPARE stmt_analytics;

SET @analytics_index = (
    SELECT IF(COUNT(*) = 0, 'CREATE INDEX idx_stats_session_created ON stats (session_hash, created_at)', 'SELECT 1')
    FROM information_schema.statistics
    WHERE table_schema = DATABASE() AND table_name = 'stats' AND index_name = 'idx_stats_session_created'
);
PREPARE stmt_analytics FROM @analytics_index; EXECUTE stmt_analytics; DEALLOCATE PREPARE stmt_analytics;

CREATE TABLE IF NOT EXISTS admin_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    device_type ENUM('desktop', 'mobile', 'tablet', 'unknown') NOT NULL DEFAULT 'unknown',
    os_family VARCHAR(40) NOT NULL DEFAULT 'Lainnya',
    browser_family VARCHAR(40) NOT NULL DEFAULT 'Lainnya',
    ip_hash CHAR(64) NOT NULL DEFAULT '',
    ip_hint VARCHAR(64) NOT NULL DEFAULT '',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_seen_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    revoked_at DATETIME NULL,
    INDEX idx_admin_sessions_admin (admin_id, last_seen_at),
    INDEX idx_admin_sessions_active (admin_id, revoked_at, last_seen_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE admin_sessions
    ADD COLUMN IF NOT EXISTS ip_hint VARCHAR(64) NOT NULL DEFAULT '' AFTER ip_hash;
