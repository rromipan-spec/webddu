-- Pemulihan terarah CTA program Al-Qur'an untuk Santri Baru.
-- Tidak mengubah QR, nomor/pesan WhatsApp, label tombol, media, galeri, atau cerita program.

START TRANSACTION;

SET @program_slug = 'al-qur-an-untuk-santri-baru-pesantren-daarul-uluum';
SET @program_id = (
    SELECT id
    FROM programs
    WHERE slug = @program_slug
    LIMIT 1
);

-- Simpan salinan kondisi sebelum pemulihan. INSERT IGNORE membuat skrip aman dijalankan ulang.
CREATE TABLE IF NOT EXISTS program_sections_recovery_20261104 LIKE program_sections;
INSERT IGNORE INTO program_sections_recovery_20261104
SELECT *
FROM program_sections
WHERE program_id = @program_id;

UPDATE program_sections
SET section_data = JSON_SET(
    section_data,
    '$.eyebrow', 'Langkah Kebaikan',
    '$.title', 'Hadiahkan Al-Qur’an untuk Perjalanan Ilmu Mereka',
    '$.subtitle', 'Satu mushaf mungkin terlihat sederhana, tetapi bagi seorang santri, ia akan menjadi teman belajar, menghafal, dan beribadah setiap hari. Mari lengkapi kebutuhan 300 santri baru, satu mushaf demi satu mushaf.'
)
WHERE program_id = @program_id
  AND section_type = 'cta';

UPDATE program_sections
SET section_data = JSON_SET(
    section_data,
    '$.target', 30000000,
    '$.deadline', '2027-06-30'
)
WHERE program_id = @program_id
  AND section_type = 'progress';

-- Tampilkan hasil akhir agar dapat diperiksa sebelum meninggalkan phpMyAdmin.
SELECT
    p.slug,
    ps.section_type,
    JSON_UNQUOTE(JSON_EXTRACT(ps.section_data, '$.eyebrow')) AS eyebrow,
    JSON_UNQUOTE(JSON_EXTRACT(ps.section_data, '$.title')) AS title,
    JSON_UNQUOTE(JSON_EXTRACT(ps.section_data, '$.subtitle')) AS subtitle,
    JSON_UNQUOTE(JSON_EXTRACT(ps.section_data, '$.target')) AS target,
    JSON_UNQUOTE(JSON_EXTRACT(ps.section_data, '$.deadline')) AS deadline
FROM programs p
JOIN program_sections ps ON ps.program_id = p.id
WHERE p.slug = @program_slug
  AND ps.section_type IN ('cta', 'progress')
ORDER BY ps.sort_order;

COMMIT;
