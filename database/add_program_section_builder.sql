-- Jalankan satu kali melalui phpMyAdmin sebelum memakai Section Builder Program.
CREATE TABLE IF NOT EXISTS program_sections (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    program_id BIGINT UNSIGNED NOT NULL,
    section_key VARCHAR(64) NOT NULL,
    section_type VARCHAR(32) NOT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    is_visible TINYINT(1) NOT NULL DEFAULT 1,
    section_data MEDIUMTEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_program_section_key (program_id, section_key),
    INDEX idx_program_sections_order (program_id, sort_order),
    CONSTRAINT fk_program_sections_program
        FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
