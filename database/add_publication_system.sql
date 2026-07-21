-- Jalankan satu kali melalui phpMyAdmin sebelum deploy sistem publikasi.
-- Data lama tetap berstatus publik dan tanggal terbitnya memakai tanggal pembuatan.

ALTER TABLE posts
    ADD COLUMN IF NOT EXISTS category VARCHAR(100) NOT NULL DEFAULT 'Umum' AFTER image_alt,
    ADD COLUMN IF NOT EXISTS status ENUM('draft', 'published') NOT NULL DEFAULT 'published' AFTER category,
    ADD COLUMN IF NOT EXISTS published_at DATETIME NULL AFTER status,
    ADD COLUMN IF NOT EXISTS created_by BIGINT UNSIGNED NULL AFTER published_at,
    ADD COLUMN IF NOT EXISTS updated_by BIGINT UNSIGNED NULL AFTER created_by;

UPDATE posts
SET published_at = created_at
WHERE status = 'published' AND published_at IS NULL;

ALTER TABLE posts MODIFY COLUMN status ENUM('draft', 'published') NOT NULL DEFAULT 'draft';

ALTER TABLE programs
    ADD COLUMN IF NOT EXISTS category VARCHAR(100) NOT NULL DEFAULT 'Umum' AFTER image_alt,
    ADD COLUMN IF NOT EXISTS status ENUM('draft', 'published') NOT NULL DEFAULT 'published' AFTER category,
    ADD COLUMN IF NOT EXISTS published_at DATETIME NULL AFTER status,
    ADD COLUMN IF NOT EXISTS featured_order INT UNSIGNED NULL AFTER published_at,
    ADD COLUMN IF NOT EXISTS created_by BIGINT UNSIGNED NULL AFTER featured_order,
    ADD COLUMN IF NOT EXISTS updated_by BIGINT UNSIGNED NULL AFTER created_by;

UPDATE programs
SET published_at = created_at
WHERE status = 'published' AND published_at IS NULL;

ALTER TABLE programs MODIFY COLUMN status ENUM('draft', 'published') NOT NULL DEFAULT 'draft';

CREATE TABLE IF NOT EXISTS content_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    content_type ENUM('posts', 'programs') NOT NULL,
    content_id BIGINT UNSIGNED NOT NULL,
    action ENUM('created', 'updated', 'deleted') NOT NULL,
    admin_id BIGINT UNSIGNED NOT NULL,
    admin_email VARCHAR(190) NOT NULL DEFAULT '',
    summary VARCHAR(500) NOT NULL DEFAULT '',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_history_content (content_type, content_id, created_at),
    INDEX idx_history_admin (admin_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Index dibuat kondisional melalui information_schema agar migrasi aman dijalankan ulang.
SET @posts_publication_index = (
    SELECT IF(COUNT(*) = 0,
        'CREATE INDEX idx_posts_publication ON posts (status, published_at)',
        'SELECT 1')
    FROM information_schema.statistics
    WHERE table_schema = DATABASE() AND table_name = 'posts' AND index_name = 'idx_posts_publication'
);
PREPARE stmt_posts_publication FROM @posts_publication_index;
EXECUTE stmt_posts_publication;
DEALLOCATE PREPARE stmt_posts_publication;

SET @programs_publication_index = (
    SELECT IF(COUNT(*) = 0,
        'CREATE INDEX idx_programs_publication ON programs (status, published_at)',
        'SELECT 1')
    FROM information_schema.statistics
    WHERE table_schema = DATABASE() AND table_name = 'programs' AND index_name = 'idx_programs_publication'
);
PREPARE stmt_programs_publication FROM @programs_publication_index;
EXECUTE stmt_programs_publication;
DEALLOCATE PREPARE stmt_programs_publication;

SET @programs_featured_index = (
    SELECT IF(COUNT(*) = 0,
        'CREATE INDEX idx_programs_featured ON programs (featured_order)',
        'SELECT 1')
    FROM information_schema.statistics
    WHERE table_schema = DATABASE() AND table_name = 'programs' AND index_name = 'idx_programs_featured'
);
PREPARE stmt_programs_featured FROM @programs_featured_index;
EXECUTE stmt_programs_featured;
DEALLOCATE PREPARE stmt_programs_featured;
