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

CREATE TABLE IF NOT EXISTS posts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    slug VARCHAR(180) NOT NULL UNIQUE,
    image VARCHAR(500) NOT NULL DEFAULT '',
    gallery_images TEXT NOT NULL,
    hero_image VARCHAR(500) NOT NULL DEFAULT '',
    hero_images TEXT NOT NULL,
    excerpt TEXT NOT NULL,
    content MEDIUMTEXT NOT NULL,
    whatsapp_number VARCHAR(20) NOT NULL DEFAULT '',
    whatsapp_message VARCHAR(500) NOT NULL DEFAULT '',
    seo_title VARCHAR(70) NOT NULL DEFAULT '',
    seo_description VARCHAR(170) NOT NULL DEFAULT '',
    social_image VARCHAR(500) NOT NULL DEFAULT '',
    image_alt VARCHAR(180) NOT NULL DEFAULT '',
    category VARCHAR(100) NOT NULL DEFAULT 'Umum',
    status ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
    published_at DATETIME NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_posts_created_at (created_at),
    INDEX idx_posts_publication (status, published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS programs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    slug VARCHAR(180) NOT NULL UNIQUE,
    image VARCHAR(500) NOT NULL DEFAULT '',
    gallery_images TEXT NOT NULL,
    excerpt TEXT NOT NULL,
    hero_title VARCHAR(180) NOT NULL DEFAULT '',
    hero_subtitle VARCHAR(300) NOT NULL DEFAULT '',
    content MEDIUMTEXT NOT NULL,
    whatsapp_number VARCHAR(20) NOT NULL DEFAULT '',
    whatsapp_message VARCHAR(500) NOT NULL DEFAULT '',
    seo_title VARCHAR(70) NOT NULL DEFAULT '',
    seo_description VARCHAR(170) NOT NULL DEFAULT '',
    social_image VARCHAR(500) NOT NULL DEFAULT '',
    image_alt VARCHAR(180) NOT NULL DEFAULT '',
    category VARCHAR(100) NOT NULL DEFAULT 'Umum',
    status ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
    published_at DATETIME NULL,
    featured_order INT UNSIGNED NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_programs_created_at (created_at),
    INDEX idx_programs_publication (status, published_at),
    INDEX idx_programs_featured (featured_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS stats (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type ENUM('visit', 'wa_click') NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_stats_type_created (type, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
