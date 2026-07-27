-- Jalankan satu kali melalui phpMyAdmin untuk menambahkan QR/barcode donasi
-- pada CTA artikel dan program.
ALTER TABLE posts
    ADD COLUMN IF NOT EXISTS donation_qr_image
        VARCHAR(500)
        NOT NULL DEFAULT ''
        AFTER whatsapp_message;

ALTER TABLE programs
    ADD COLUMN IF NOT EXISTS donation_qr_image
        VARCHAR(500)
        NOT NULL DEFAULT ''
        AFTER whatsapp_message;
