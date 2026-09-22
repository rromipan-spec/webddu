-- Jalankan satu kali melalui phpMyAdmin sebelum memakai Analitik Akurat dan Kesehatan Website.
-- Aman dijalankan ulang. File ini juga melengkapi kolom analitik dasar bila
-- add_visitor_analytics.sql belum pernah dijalankan atau baru berjalan sebagian.

ALTER TABLE stats MODIFY COLUMN type VARCHAR(40) NOT NULL;
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

ALTER TABLE stats
    ADD COLUMN IF NOT EXISTS event_id CHAR(36) NULL AFTER id,
    ADD COLUMN IF NOT EXISTS landing_path VARCHAR(255) NOT NULL DEFAULT '/' AFTER screen_bucket,
    ADD COLUMN IF NOT EXISTS utm_source VARCHAR(100) NOT NULL DEFAULT '' AFTER landing_path,
    ADD COLUMN IF NOT EXISTS utm_medium VARCHAR(100) NOT NULL DEFAULT '' AFTER utm_source,
    ADD COLUMN IF NOT EXISTS utm_campaign VARCHAR(150) NOT NULL DEFAULT '' AFTER utm_medium,
    ADD COLUMN IF NOT EXISTS utm_content VARCHAR(150) NOT NULL DEFAULT '' AFTER utm_campaign,
    ADD COLUMN IF NOT EXISTS cta_id VARCHAR(100) NOT NULL DEFAULT '' AFTER utm_content,
    ADD COLUMN IF NOT EXISTS event_label VARCHAR(120) NOT NULL DEFAULT '' AFTER cta_id,
    ADD COLUMN IF NOT EXISTS engagement_ms INT UNSIGNED NOT NULL DEFAULT 0 AFTER event_label,
    ADD COLUMN IF NOT EXISTS scroll_depth TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER engagement_ms,
    ADD COLUMN IF NOT EXISTS metric_name VARCHAR(30) NOT NULL DEFAULT '' AFTER scroll_depth,
    ADD COLUMN IF NOT EXISTS metric_value DECIMAL(12,3) NULL AFTER metric_name;

SET @event_index_exists = (
    SELECT COUNT(*) FROM information_schema.statistics
    WHERE table_schema = DATABASE() AND table_name = 'stats' AND index_name = 'uq_stats_event_id'
);
SET @event_index_sql = IF(@event_index_exists = 0,
    'ALTER TABLE stats ADD UNIQUE KEY uq_stats_event_id (event_id)', 'SELECT 1');
PREPARE event_index_statement FROM @event_index_sql;
EXECUTE event_index_statement;
DEALLOCATE PREPARE event_index_statement;

CREATE TABLE IF NOT EXISTS analytics_visitors (
    visitor_hash CHAR(64) PRIMARY KEY,
    first_seen_at DATETIME NOT NULL,
    last_seen_at DATETIME NOT NULL,
    total_sessions INT UNSIGNED NOT NULL DEFAULT 0,
    total_page_views INT UNSIGNED NOT NULL DEFAULT 0,
    INDEX idx_analytics_visitors_seen (last_seen_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS analytics_sessions (
    session_hash CHAR(64) PRIMARY KEY,
    visitor_hash CHAR(64) NOT NULL,
    started_at DATETIME NOT NULL,
    last_seen_at DATETIME NOT NULL,
    landing_path VARCHAR(255) NOT NULL DEFAULT '/',
    referrer_source VARCHAR(80) NOT NULL DEFAULT 'Langsung',
    utm_source VARCHAR(100) NOT NULL DEFAULT '',
    utm_medium VARCHAR(100) NOT NULL DEFAULT '',
    utm_campaign VARCHAR(150) NOT NULL DEFAULT '',
    device_type ENUM('desktop', 'mobile', 'tablet', 'unknown') NOT NULL DEFAULT 'unknown',
    page_views INT UNSIGNED NOT NULL DEFAULT 0,
    event_count INT UNSIGNED NOT NULL DEFAULT 0,
    is_engaged TINYINT(1) NOT NULL DEFAULT 0,
    converted_at DATETIME NULL,
    INDEX idx_analytics_sessions_started (started_at),
    INDEX idx_analytics_sessions_visitor (visitor_hash, started_at),
    INDEX idx_analytics_sessions_conversion (converted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS system_health_snapshots (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    status ENUM('healthy', 'unhealthy') NOT NULL,
    response_ms INT UNSIGNED NOT NULL DEFAULT 0,
    database_ok TINYINT(1) NOT NULL DEFAULT 0,
    uploads_ok TINYINT(1) NOT NULL DEFAULT 0,
    logs_ok TINYINT(1) NOT NULL DEFAULT 0,
    error_count INT UNSIGNED NOT NULL DEFAULT 0,
    checked_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_health_checked (checked_at),
    INDEX idx_health_status (status, checked_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO analytics_visitors
    (visitor_hash, first_seen_at, last_seen_at, total_sessions, total_page_views)
SELECT visitor_hash, MIN(created_at), MAX(created_at),
       COUNT(DISTINCT NULLIF(session_hash, '')), SUM(type IN ('visit', 'page_view'))
FROM stats WHERE visitor_hash <> '' GROUP BY visitor_hash;
