# Video Background Hero Program

Fitur ini membuat gambar di belakang overlay halaman detail program dapat diganti dengan video lokal, YouTube, atau Google Drive. Foto pertama program tetap dipakai sebagai poster dan cadangan ketika video gagal dimuat.

## 1. Migrasi database

Jalankan satu kali melalui phpMyAdmin:

`database/add_program_hero_video.sql`

Lakukan migrasi sebelum membuka atau menyimpan program melalui panel admin versi baru.

## 2. Memilih media di panel admin

1. Masuk ke **Admin > Program**.
2. Tambah atau edit program.
3. Isi **Slider Utama Program** minimal satu foto untuk poster/fallback.
4. Pada **Media Background Hero**, pilih salah satu:
   - **Slider gambar**: menggunakan tampilan foto seperti sebelumnya.
   - **Video lokal**: unggah MP4 atau WebM maksimal 50 MB.
   - **Tautan YouTube**: tempel URL video YouTube lengkap.
   - **Tautan Google Drive**: tempel URL file video di Google Drive.
5. Klik **Simpan Program**.

Video diputar tanpa suara, otomatis, berulang, dan tidak menerima interaksi agar tetap berfungsi sebagai background.

## 3. Syarat video Google Drive

Atur file menjadi **Siapa saja yang memiliki link** dengan akses **Viewer**. Jika Google Drive menolak pemutaran langsung, foto utama tetap tampil sebagai cadangan. Untuk hasil background paling stabil dan cepat, gunakan video lokal pendek atau YouTube.

## 4. Rekomendasi video lokal

- Format utama: MP4 (H.264), alternatif WebM.
- Durasi: sekitar 8–20 detik.
- Resolusi: 1280×720 atau 1920×1080.
- Ukuran ideal: di bawah 15 MB walaupun batas sistem 50 MB.
- Jangan mengandalkan suara karena background selalu diputar dalam keadaan muted.
- Letakkan objek penting dekat bagian tengah agar tetap terlihat pada layar ponsel.

## 5. Pemulihan

Jika video bermasalah, edit program dan pilih kembali **Slider gambar**. URL video akan dilepas dari program, sedangkan file lokal tetap berada di folder uploads dan masuk ke backup uploads.
