# Video Background Header Artikel

Header artikel dapat memakai slider gambar, video lokal, YouTube, atau Google Drive. Foto hero pertama tetap dipakai sebagai poster/fallback dan dapat menjadi thumbnail ketika tautan artikel dibagikan.

## 1. Migrasi database

Jalankan satu kali melalui phpMyAdmin:

`database/add_article_hero_video.sql`

Jika fitur video program belum dipasang, jalankan juga:

`database/add_program_hero_video.sql`

## 2. Cara memakai

1. Masuk ke **Admin > Artikel**.
2. Tambah atau edit artikel.
3. Unggah minimal satu foto pada **Slider Background Header Artikel**.
4. Pada **Media Background Header Artikel**, pilih:
   - **Slider gambar**
   - **Video lokal (MP4/WebM)**
   - **Tautan YouTube**
   - **Tautan Google Drive**
5. Klik **Simpan Artikel**.

Video diputar tanpa suara, otomatis, berulang, dan tidak dapat diklik karena berfungsi sebagai background.

## 3. Thumbnail tautan sosial

Video tidak dipakai sebagai thumbnail WhatsApp, Facebook, atau platform sosial. Urutan gambar sosial tetap:

1. Gambar sosial khusus dari pengaturan SEO.
2. Gambar utama artikel.
3. Foto galeri pertama.
4. Foto hero pertama.
5. Logo Dompet Dana Umat.

## 4. Rekomendasi

- Gunakan MP4 H.264 berdurasi 8–20 detik.
- Resolusi ideal 1280×720 atau 1920×1080.
- Ukuran ideal di bawah 15 MB; batas sistem 50 MB.
- Letakkan objek penting di tengah agar tidak terpotong pada ponsel.
- Untuk Google Drive, atur file menjadi **Siapa saja yang memiliki link — Viewer**.
- Video lokal pendek atau YouTube biasanya lebih stabil untuk pemutaran background.
