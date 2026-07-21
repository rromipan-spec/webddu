-- Jalankan satu kali melalui phpMyAdmin untuk menambahkan slider maksimal 3 foto.
ALTER TABLE posts
    ADD COLUMN IF NOT EXISTS gallery_images TEXT NOT NULL AFTER image;

ALTER TABLE programs
    ADD COLUMN IF NOT EXISTS gallery_images TEXT NOT NULL AFTER image;

-- Gunakan gambar utama lama sebagai foto pertama agar data lama tetap tampil.
UPDATE posts
SET gallery_images = JSON_ARRAY(image)
WHERE image <> '' AND (gallery_images = '' OR gallery_images IS NULL);

UPDATE programs
SET gallery_images = JSON_ARRAY(image)
WHERE image <> '' AND (gallery_images = '' OR gallery_images IS NULL);
