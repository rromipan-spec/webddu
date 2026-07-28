-- Jalankan satu kali melalui phpMyAdmin untuk menambahkan nama penulis artikel.
ALTER TABLE posts
    ADD COLUMN IF NOT EXISTS author_name VARCHAR(120) NOT NULL DEFAULT '' AFTER content;
