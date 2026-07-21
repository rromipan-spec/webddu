# Website Dompet Dana Umat — PHP

Proyek ini dipisahkan agar file publik dan kode server tidak tercampur:

```text
websiteddu/
├── frontend/               # isi folder public_html di Hostinger
│   ├── halaman-utama/      # halaman publik, CSS, JS, dan aset
│   ├── admin/              # panel admin terpisah
│   ├── api/index.php       # satu pintu API PHP bersama
│   └── uploads/            # gambar hasil upload admin
├── backend/                # wajib diletakkan di luar public_html
│   ├── config/.env         # rahasia, jangan dipublikasikan
│   ├── src/                # database, autentikasi, sanitasi
│   └── storage/logs/
├── database/schema.sql     # struktur database MySQL
└── docs/                   # dokumentasi dan arsip versi Supabase
```

Panduan pemasangan lengkap ada di [docs/DEPLOY-HOSTINGER.md](docs/DEPLOY-HOSTINGER.md).
Panduan menambah dan mengubah akun admin ada di [docs/ADMIN-MYSQL.md](docs/ADMIN-MYSQL.md).
Panduan deployment otomatis tersedia di [docs/GITHUB-HOSTINGER.md](docs/GITHUB-HOSTINGER.md).
Panduan backup harian dan pemulihan tersedia di [docs/BACKUP-RESTORE.md](docs/BACKUP-RESTORE.md).
Panduan optimasi dan migrasi gambar tersedia di [docs/IMAGE-OPTIMIZATION.md](docs/IMAGE-OPTIMIZATION.md).
Panduan SEO teknis dan Google Search Console tersedia di [docs/GOOGLE-SEARCH-CONSOLE.md](docs/GOOGLE-SEARCH-CONSOLE.md).
Panduan draft, jadwal tayang, preview, kategori, dan riwayat admin tersedia di [docs/PUBLICATION-SYSTEM.md](docs/PUBLICATION-SYSTEM.md).
Panduan legalitas, rekening resmi, transparansi, dan kebijakan privasi tersedia di [docs/CREDIBILITY-PROFILE.md](docs/CREDIBILITY-PROFILE.md).

Setelah GitHub Secrets selesai dikonfigurasi, deploy perubahan dari terminal VS Code dengan:

```powershell
.\deploy.cmd "Jelaskan perubahan yang dibuat"
```

Script akan memeriksa file rahasia dan sintaks JavaScript, membuat commit, push ke branch `main`, lalu GitHub Actions meneruskan perubahan ke Hostinger.

## Keamanan yang sudah diterapkan

- PDO dengan prepared statement; tidak ada query input mentah.
- Login memakai `password_hash`/`password_verify`, session HttpOnly, SameSite Strict, dan rotasi session ID.
- Batas lima percobaan login lalu dikunci selama 15 menit.
- CSRF token untuk simpan, hapus, upload, dan logout.
- Validasi slug, panjang input, nomor WhatsApp, serta URL gambar.
- HTML artikel disaring dengan allowlist di server untuk mengurangi XSS.
- Upload hanya JPG/PNG/WebP maksimal 5 MB dengan nama acak; eksekusi PHP di folder upload diblokir.
- Pesan error produksi tidak membocorkan detail server; detail masuk ke log privat.
- File `.env`, log, SQL, directory listing, dan akses langsung ke backend diblokir.

## Menjalankan secara lokal

Butuh PHP 8.1+ dengan ekstensi PDO MySQL, mbstring, DOM, fileinfo, GD/WebP, dan zip, serta MySQL/MariaDB.

1. Salin `backend/config/.env.example` menjadi `backend/config/.env`.
2. Isi kredensial MySQL dan hash password admin.
3. Import `database/schema.sql`.
4. Dari folder proyek, jalankan `php -S localhost:8000 -t frontend`.
5. Buka `http://localhost:8000/halaman-utama/`; admin berada di `http://localhost:8000/admin/`.

Catatan: server bawaan PHP tidak membaca `.htaccess`. Di Apache/Hostinger, halaman publik otomatis tampil langsung dari root domain tanpa memperlihatkan nama folder `halaman-utama`.
