-- Jalankan satu kali melalui phpMyAdmin sebelum memakai kolom SEO di panel admin.
ALTER TABLE posts
    ADD COLUMN IF NOT EXISTS seo_title VARCHAR(70) NOT NULL DEFAULT '' AFTER whatsapp_message,
    ADD COLUMN IF NOT EXISTS seo_description VARCHAR(170) NOT NULL DEFAULT '' AFTER seo_title,
    ADD COLUMN IF NOT EXISTS social_image VARCHAR(500) NOT NULL DEFAULT '' AFTER seo_description,
    ADD COLUMN IF NOT EXISTS image_alt VARCHAR(180) NOT NULL DEFAULT '' AFTER social_image;

ALTER TABLE programs
    ADD COLUMN IF NOT EXISTS seo_title VARCHAR(70) NOT NULL DEFAULT '' AFTER whatsapp_message,
    ADD COLUMN IF NOT EXISTS seo_description VARCHAR(170) NOT NULL DEFAULT '' AFTER seo_title,
    ADD COLUMN IF NOT EXISTS social_image VARCHAR(500) NOT NULL DEFAULT '' AFTER seo_description,
    ADD COLUMN IF NOT EXISTS image_alt VARCHAR(180) NOT NULL DEFAULT '' AFTER social_image;

-- Isi alt gambar lama dengan judul agar gambar tidak memiliki alt kosong.
UPDATE posts SET image_alt = title WHERE image_alt = '';
UPDATE programs SET image_alt = title WHERE image_alt = '';
