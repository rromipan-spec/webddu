# Website Dompet Dana Umat

Website resmi Dompet Dana Umat Daarul Uluum (DDU), dibangun dengan PHP, MySQL, HTML, CSS, dan JavaScript tanpa framework. Repositori ini mencakup website publik, panel admin, API, optimasi media, SEO teknis, analitik anonim, backup, monitoring, serta deployment otomatis ke Hostinger.

- Website produksi: [dompetdanaumat.com](https://dompetdanaumat.com/)
- Panel admin: [dompetdanaumat.com/admin/](https://dompetdanaumat.com/admin/)
- Health check: [dompetdanaumat.com/health.php](https://dompetdanaumat.com/health.php)
- Sitemap: [dompetdanaumat.com/sitemap.xml](https://dompetdanaumat.com/sitemap.xml)

## Fitur utama

- Artikel dan program dengan status draft, terjadwal, atau dipublikasikan.
- Editor konten, kategori, penulis, media, hero gambar/video, QR donasi, dan WhatsApp.
- Hero homepage adaptif dengan daftar foto desktop/mobile terpisah, cross-fade otomatis, teks opsional, dan tautan foto.
- SEO per konten, Open Graph, robots.txt, sitemap dinamis, serta halaman 404.
- Optimasi unggahan gambar ke WebP dan beberapa ukuran tampilan.
- Profil lembaga, legalitas, rekening resmi, laporan, dan kebijakan privasi.
- Akun admin, peran admin/super admin, riwayat perubahan, keamanan login, dan sesi perangkat.
- Statistik anonim akurat dengan sesi 30 menit, deduplikasi event, funnel, UTM, tren periode, dan Core Web Vitals.
- Dashboard kesehatan berisi backup, uptime internal, kapasitas, error tersaring, peringatan otomatis, dan kesegaran analitik.

## Struktur proyek

```text
websiteddu/
|-- .github/workflows/       # pengujian dan deployment otomatis
|-- backend/
|   |-- bin/                 # backup, health check, optimasi, pemeliharaan
|   |-- config/              # contoh konfigurasi; .env tidak masuk Git
|   |-- src/                 # database, autentikasi, layanan, dan keamanan
|   `-- storage/             # log, cache, backup, dan data keamanan privat
|-- database/                # skema baru dan migrasi database
|-- docs/                    # panduan operasional per fitur
|-- frontend/
|   |-- admin/               # panel pengelolaan website
|   |-- api/                 # pintu masuk API publik/admin
|   |-- halaman-utama/       # halaman, gaya, skrip, font, dan aset publik
|   |-- uploads/             # media unggahan; isi produksi tidak masuk Git
|   |-- .htaccess            # routing dan proteksi Apache
|   `-- health.php           # endpoint pemeriksaan kesehatan publik
|-- tests/                   # integration test HTTP dan router lokal
|-- deploy.cmd               # pintasan deployment untuk Windows
`-- deploy.ps1               # pemeriksaan, commit, dan push deployment
```

Folder `docs/legacy/` hanya menyimpan referensi migrasi Supabase lama. Berkas tersebut bukan bagian dari aplikasi produksi dan tidak ikut disalin oleh workflow deployment.

## Persyaratan

- PHP 8.1 atau lebih baru.
- MySQL 8 atau MariaDB yang kompatibel.
- Ekstensi PHP: PDO MySQL, mbstring, DOM, fileinfo, GD dengan WebP, dan ZIP.
- Node.js untuk pemeriksaan sintaks JavaScript dan audit Lighthouse.
- Apache dengan `mod_rewrite` untuk URL produksi.

## Instalasi lokal

1. Salin `backend/config/.env.example` menjadi `backend/config/.env`.
2. Isi `APP_URL`, kredensial database, `ADMIN_SETUP_KEY`, dan `ANALYTICS_HASH_KEY` dengan nilai lokal yang aman.
3. Buat database lalu import `database/schema.sql`.
4. Siapkan admin pertama mengikuti [panduan akun MySQL](docs/ADMIN-MYSQL.md). Aktifkan `ADMIN_SETUP_ENABLED` hanya saat proses setup, kemudian kembalikan ke `false`.
5. Jalankan server lokal dari root repositori:

```powershell
php -S 127.0.0.1:8000 -t frontend tests/router.php
```

6. Buka `http://127.0.0.1:8000/` dan panel admin di `http://127.0.0.1:8000/admin/`.

Server bawaan PHP tidak membaca `.htaccess`; `tests/router.php` menyediakan routing lokal yang setara untuk kebutuhan pengembangan. Jangan commit `backend/config/.env`, isi `frontend/uploads/`, log, atau backup produksi.

## Database dan migrasi

Gunakan `database/schema.sql` untuk instalasi baru. File `database/add_*.sql` dan migrasi bernama khusus dipertahankan untuk memperbarui instalasi lama. Sebelum menjalankan migrasi pada produksi:

1. Buat backup database dan uploads.
2. Baca migrasi yang akan dijalankan.
3. Jalankan hanya migrasi yang belum pernah diterapkan.
4. Jalankan health check setelah migrasi.

## Pengujian

GitHub Actions menjalankan pemeriksaan sintaks seluruh JavaScript dan PHP, menyiapkan database MySQL sementara, menjalankan integration test HTTP, lalu mencoba audit Lighthouse mobile. Deployment hanya dimulai jika pengujian wajib berhasil.

Pengujian lengkap paling mudah dijalankan melalui workflow. Untuk pemeriksaan lokal dasar:

```powershell
Get-ChildItem frontend -Recurse -Filter *.js | ForEach-Object { node --check $_.FullName }
php backend/bin/health-check.php
```

Integration test memerlukan Bash, `curl`, `jq`, server PHP, serta database uji yang sudah disiapkan. Detailnya tersedia di [docs/AUTOMATED-TESTS.md](docs/AUTOMATED-TESTS.md).

## Deployment ke Hostinger

Deployment produksi berjalan melalui `.github/workflows/deploy-hostinger.yml`: test, validasi sumber, sinkronisasi lewat SSH, verifikasi health endpoint, dan audit pascadeploy.

Dari PowerShell di root repositori:

```powershell
.\deploy.cmd "Jelaskan perubahan yang dibuat"
```

Perintah tersebut memeriksa repository, sintaks JavaScript, whitespace Git, membuat commit, lalu push ke branch `main`. Pantau hasilnya pada tab **Actions** di GitHub. Konfigurasi awal SSH dan GitHub Secrets dijelaskan di [docs/GITHUB-HOSTINGER.md](docs/GITHUB-HOSTINGER.md).

## Operasional server

Contoh berikut memakai lokasi produksi saat ini:

```bash
/usr/bin/php /home/u706044810/domains/dompetdanaumat.com/backend/bin/health-check.php
/usr/bin/php /home/u706044810/domains/dompetdanaumat.com/backend/bin/backup.php
/usr/bin/php /home/u706044810/domains/dompetdanaumat.com/backend/bin/optimize-existing-images.php
/usr/bin/php /home/u706044810/domains/dompetdanaumat.com/backend/bin/prune-analytics.php
/usr/bin/php /home/u706044810/domains/dompetdanaumat.com/backend/bin/system-monitor.php
```

Jangan memindahkan `backend/config/.env`, `backend/storage/`, atau `frontend/uploads/` ke area publik lain. Workflow mempertahankan data produksi tersebut saat deployment.

## Dokumentasi

- [Deployment Hostinger](docs/DEPLOY-HOSTINGER.md)
- [GitHub Actions dan Hostinger](docs/GITHUB-HOSTINGER.md)
- [Akun dan database admin](docs/ADMIN-MYSQL.md)
- [Keamanan akun admin](docs/ADMIN-ACCOUNT-SECURITY.md)
- [Keamanan aplikasi](docs/SECURITY.md)
- [Sistem publikasi](docs/PUBLICATION-SYSTEM.md)
- [Optimasi gambar](docs/IMAGE-OPTIMIZATION.md)
- [Hero video artikel](docs/ARTICLE-HERO-VIDEO.md)
- [Hero video program](docs/PROGRAM-HERO-VIDEO.md)
- [QR dan CTA donasi](docs/DONATION-QR-CTA.md)
- [Profil dan kredibilitas](docs/CREDIBILITY-PROFILE.md)
- [Google Search Console](docs/GOOGLE-SEARCH-CONSOLE.md)
- [Analitik anonim](docs/ANALYTICS.md)
- [Monitoring dan health check](docs/MONITORING.md)
- [Backup dan pemulihan](docs/BACKUP-RESTORE.md)
- [Pengujian otomatis](docs/AUTOMATED-TESTS.md)
- [Audit Lighthouse](docs/LIGHTHOUSE.md)

## Prinsip keamanan

- Password disimpan sebagai hash dan tidak dapat dibaca kembali, termasuk oleh super admin; super admin hanya dapat melakukan reset.
- Query database menggunakan prepared statement.
- Operasi admin dilindungi sesi, CSRF token, validasi input, pembatasan login, dan pencatatan kejadian keamanan.
- HTML konten disaring dengan allowlist untuk mengurangi risiko XSS.
- Upload dibatasi berdasarkan tipe dan ukuran, dinamai acak, serta tidak boleh mengeksekusi PHP.
- Detail error produksi masuk ke log privat dan tidak ditampilkan kepada pengunjung.
- File rahasia, SQL, log, backup, directory listing, dan akses langsung ke backend diblokir.

## Aturan kontribusi

- Jangan commit kredensial, private key, dump database, backup, atau media produksi.
- Pertahankan URL dan kontrak API yang sudah digunakan halaman publik dan panel admin.
- Jalankan pemeriksaan sintaks dan `git diff --check` sebelum deployment.
- Tambahkan dokumentasi saat mengubah konfigurasi, skema database, proses deployment, atau prosedur operasional.
