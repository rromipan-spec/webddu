-- Jalankan satu kali melalui phpMyAdmin untuk menambahkan background header artikel.
ALTER TABLE posts
    ADD COLUMN IF NOT EXISTS hero_image VARCHAR(500) NOT NULL DEFAULT '' AFTER gallery_images;

ALTER TABLE posts
    ADD COLUMN IF NOT EXISTS hero_images TEXT NOT NULL AFTER hero_image;

-- Pertahankan background tunggal lama sebagai foto pertama slider.
UPDATE posts
SET hero_images = JSON_ARRAY(hero_image)
WHERE hero_image <> '' AND (hero_images = '' OR hero_images IS NULL);
