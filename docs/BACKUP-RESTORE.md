# Backup dan pemulihan Hostinger

Sistem ini membuat satu arsip ZIP yang berisi:

- `database.sql`: struktur dan data MySQL;
- `uploads/`: seluruh gambar yang diunggah melalui panel admin;
- `manifest.json`: waktu backup, jumlah tabel, jumlah baris, jumlah file, dan checksum database.

Arsip disimpan di `backend/storage/backups`, yaitu di luar `public_html`. Secara bawaan, 14 versi terbaru dipertahankan. File yang lebih lama dihapus otomatis setelah backup baru berhasil.

> Backup mengandung data website dan hash password admin. Jangan unggah arsip ke folder publik atau mengirimkannya sembarangan.

## 1. Deploy sistem backup

Dari terminal VS Code:

```powershell
.\deploy.cmd "Tambahkan backup harian database dan uploads"
```

Tunggu GitHub Actions selesai dengan centang hijau.

## 2. Atur jumlah versi

Di File Manager Hostinger, buka `backend/config/.env`, lalu tambahkan:

```dotenv
BACKUP_RETENTION=14
```

Nilai yang diizinkan adalah 3 sampai 60. Karena setiap versi juga membawa folder uploads, periksa kapasitas penyimpanan secara berkala. Gunakan 7 jika ukuran gambar sudah besar.

## 3. Uji satu backup melalui SSH

Masuk melalui terminal VS Code:

```powershell
ssh -p 65002 u706044810@46.202.186.167
```

Cari lokasi script yang sudah di-deploy:

```sh
find /home/u706044810 -path '*/backend/bin/backup.php' -print
```

Lokasi normal untuk domain ini adalah:

```text
/home/u706044810/domains/dompetdanaumat.com/backend/bin/backup.php
```

Jalankan pengujian:

```sh
/usr/bin/php /home/u706044810/domains/dompetdanaumat.com/backend/bin/backup.php
```

Hasil yang benar diawali dengan `Backup berhasil`. Lihat arsipnya:

```sh
ls -lh /home/u706044810/domains/dompetdanaumat.com/backend/storage/backups
```

Setiap versi memiliki dua file, misalnya:

```text
ddu-backup-20260722-021500.zip
ddu-backup-20260722-021500.zip.sha256
```

## 4. Aktifkan backup otomatis setiap hari

Di hPanel:

1. Buka **Websites → Dashboard** untuk `dompetdanaumat.com`.
2. Pilih **Advanced → Cron Jobs**.
3. Pilih tipe **PHP**.
4. Pada **Command to Run**, masukkan path file PHP saja:

   ```text
   /home/u706044810/domains/dompetdanaumat.com/backend/bin/backup.php
   ```

5. Pilih jadwal setiap hari. Cron Hostinger memakai UTC. Untuk sekitar pukul **02.15 WIB**, gunakan **19.15 UTC** atau ekspresi `15 19 * * *`.
6. Simpan.

Sesudah jadwal dijalankan, buka daftar Cron Jobs dan tekan **View Output**. Pastikan tertulis `Backup berhasil`, bukan `[BACKUP GAGAL]`.

## 5. Simpan salinan di luar Hostinger

Backup dalam akun hosting tidak cukup jika seluruh akun atau server bermasalah. Minimal satu kali seminggu:

1. Buka **File Manager**.
2. Masuk ke `backend/storage/backups`.
3. Unduh ZIP terbaru beserta file `.sha256` yang namanya sama.
4. Simpan satu salinan di komputer dan satu salinan di penyimpanan cloud yang privat.

Hostinger juga menyediakan backup bawaan melalui **Websites → Dashboard → Backups**. Pertahankan backup bawaan itu sebagai lapisan tambahan.

### Memeriksa checksum di Windows

Buka PowerShell di folder file yang sudah diunduh:

```powershell
$zip = Get-ChildItem .\ddu-backup-*.zip | Sort-Object LastWriteTime -Descending | Select-Object -First 1
$expected = ((Get-Content "$($zip.FullName).sha256") -split '\s+')[0].ToLower()
$actual = (Get-FileHash $zip.FullName -Algorithm SHA256).Hash.ToLower()
$actual -eq $expected
```

Hasil `True` berarti arsip tidak rusak atau berubah.

## 6. Memulihkan database saja

1. Unduh backup kondisi website saat ini terlebih dahulu sebagai pengaman.
2. Ekstrak ZIP backup di komputer.
3. Di hPanel, buka **Databases → phpMyAdmin**.
4. Pilih database DDU yang sesuai dengan `DB_NAME` pada `.env`.
5. Pilih tab **Import**, kemudian unggah `database.sql`.
6. Tunggu pesan berhasil, lalu uji halaman utama dan login admin.

`database.sql` akan mengganti tabel DDU dengan kondisi pada waktu backup. Artikel atau perubahan setelah waktu tersebut akan hilang.

Untuk SQL lebih besar dari batas phpMyAdmin, gunakan SSH sesuai panduan Hostinger atau minta bantuan sebelum melakukan restore.

## 7. Memulihkan folder uploads

1. Simpan salinan folder `public_html/uploads` yang sedang aktif.
2. Dari arsip backup, buka folder `uploads`.
3. Unggah isinya ke `public_html/uploads` melalui File Manager atau FTP.
4. Pastikan file `public_html/uploads/.htaccess` tetap ada agar file PHP tidak dapat dijalankan di folder upload.
5. Buka beberapa artikel dan program untuk memastikan gambarnya tampil.

## 8. Jika hosting harus dibuat ulang

Urutan pemulihan penuh:

1. Buat website **Custom PHP/HTML** dan database MySQL baru di Hostinger.
2. Deploy kode dari GitHub ke hosting baru.
3. Buat ulang `backend/config/.env` dengan kredensial database baru.
4. Import `database.sql` ke database baru.
5. Unggah isi folder `uploads` dari backup ke `public_html/uploads`.
6. Pastikan `ADMIN_SETUP_ENABLED=false`.
7. Uji halaman utama, artikel, program, gambar, API, dan login admin.

File `.env` sengaja tidak dimasukkan ke arsip backup karena mengandung password database. Simpan catatan konfigurasi tersebut secara terpisah di password manager, bukan di GitHub.
