-- Jalankan satu kali melalui phpMyAdmin sebelum deploy fitur video hero program.
ALTER TABLE programs
    ADD COLUMN IF NOT EXISTS hero_media_type
        ENUM('images', 'video', 'youtube', 'drive')
        NOT NULL DEFAULT 'images'
        AFTER hero_subtitle,
    ADD COLUMN IF NOT EXISTS hero_video_url
        VARCHAR(1000)
        NOT NULL DEFAULT ''
        AFTER hero_media_type;
