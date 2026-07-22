# Deploy aman ke Hostinger

## 1. Siapkan database

1. Di hPanel buka **Websites → Manage → Databases Management**.
2. Buat database MySQL, user database, dan password acak yang kuat.
3. Buka phpMyAdmin, pilih database tersebut, lalu import `database/schema.sql`.
4. Jangan gunakan user database milik aplikasi lain.

## 2. Buat konfigurasi privat

Salin `backend/config/.env.example` menjadi `backend/config/.env`, lalu isi seluruh konfigurasi aplikasi dan database. File `.env` asli tidak boleh dikirim melalui chat, Git, atau ditaruh dalam `public_html`.

Buat hash password admin (minimal 16 karakter unik) melalui Terminal Hostinger atau komputer yang memiliki PHP:

```bash
php -r "echo password_hash('GANTI_DENGAN_PASSWORD_ADMIN', PASSWORD_DEFAULT), PHP_EOL;"
```

Masukkan hasil yang diawali `$2y$...` ke kolom `password_hash` pada tabel `admins`, bukan ke `.env`. Perintah SQL lengkap tersedia di `docs/ADMIN-MYSQL.md`. Jangan menyimpan password biasa di file atau database.

Sebagai alternatif yang lebih mudah, atur `ADMIN_SETUP_KEY` dan buka `/setup-admin.php`. Formulir setup akan membuat sekaligus memverifikasi hash secara otomatis. Hapus file setup dan kuncinya setelah login berhasil.

## 3. Upload dengan posisi yang benar

Contoh struktur akun Hostinger:

```text
/home/u123456789/domains/domainanda.com/
├── backend/       ← upload isi folder backend proyek ke sini
└── public_html/   ← upload isi folder frontend proyek ke sini
```

Penting: yang masuk ke `public_html` adalah **isi** `frontend`, bukan folder `frontend` sebagai satu subfolder. Folder `backend` harus sejajar dengan `public_html`. Folder `halaman-utama` dan `admin` jangan digabung; `.htaccess` akan menampilkan halaman utama dari root domain dan panel admin dari `/admin/`.

Permission yang disarankan:

- folder: `755`;
- file biasa: `644`;
- `backend/config/.env`: `600` jika File Manager mengizinkan;
- jangan pernah memakai `777`.

## 4. Pilih versi PHP dan HTTPS

1. Pilih PHP 8.2 atau 8.3 di hPanel.
2. Pastikan ekstensi `pdo_mysql`, `mbstring`, `dom`, dan `fileinfo` aktif.
3. Aktifkan sertifikat SSL Hostinger.
4. `.htaccess` frontend akan mengarahkan trafik produksi ke HTTPS.

## 5. Uji setelah upload

1. Buka halaman utama dan pastikan tidak ada error di Console browser.
2. Buka `/api/index.php?resource=posts`; hasil normal berupa JSON dengan `"ok": true`.
3. Buka `/admin/`, coba login, tambah satu artikel, upload gambar, edit, lalu hapus.
4. Pastikan URL `/backend/`, `/.env`, dan `/uploads/contoh.php` tidak dapat diakses.
5. Periksa `backend/storage/logs/app.log` bila API menghasilkan status 500. Jangan mempublikasikan isi log.
6. Buka `/health.php`; hasil normal adalah JSON dengan `"status":"healthy"`.

## 6. Checklist sebelum tayang

- Ganti semua nilai contoh di `.env`.
- Gunakan email admin privat dan password unik yang tidak dipakai di layanan lain.
- Aktifkan 2FA pada akun Hostinger dan email pemulihan.
- Simpan backup database dan file sebelum perubahan besar.
- Perbarui PHP ke rilis 8.x yang masih didukung.
- Hapus arsip ZIP instalasi dari hosting setelah ekstraksi.
- Jangan upload folder `docs/` atau `database/` ke `public_html`.

## Catatan migrasi data lama

Versi lama menggunakan Supabase. Struktur lamanya disimpan di `docs/legacy/`, tetapi frontend produksi sudah tidak memuat kunci Supabase. Data artikel/program lama perlu diekspor dari Supabase sebagai CSV, lalu disesuaikan dan diimpor ke tabel MySQL. Jangan mengaktifkan kembali `docs/legacy/supabase-config.js` pada website.
