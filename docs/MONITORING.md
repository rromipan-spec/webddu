# Error log dan health check

Sistem ini mencatat warning, exception, dan fatal error PHP ke folder privat `backend/storage/logs`. Detail error tidak ditampilkan kepada pengunjung pada mode produksi. Pengunjung hanya menerima pesan umum dan `event_id` yang dapat dipakai admin untuk mencari kejadian terkait.

## 1. Konfigurasi

Tambahkan atau pastikan nilai berikut tersedia di `backend/config/.env` pada Hostinger:

```dotenv
APP_ENV=production
LOG_MAX_SIZE_MB=5
LOG_RETENTION_FILES=5
```

Kedua pengaturan log bersifat opsional. Jika belum ditambahkan, aplikasi otomatis memakai ukuran 5 MB dan mempertahankan lima versi lama. Jangan mengubah `APP_ENV` menjadi `development` pada website aktif karena pesan internal dapat terlihat oleh pengunjung.

Saat `app.log` mencapai batas ukuran, file dipindahkan menjadi `app.log.1`, lalu versi lebih lama bergeser sampai batas retensi. File di luar batas akan dihapus otomatis.

## 2. Pemeriksaan publik

Buka:

```text
https://dompetdanaumat.com/health.php
```

Hasil normal:

```json
{"ok":true,"status":"healthy","checked_at":"2026-07-22T00:00:00+00:00"}
```

Endpoint publik tidak menampilkan nama database, tabel yang hilang, path hosting, kredensial, atau pesan exception. GitHub Actions memanggil endpoint ini setelah setiap deployment dan menghentikan deployment check jika statusnya tidak sehat.

## 3. Pemeriksaan lengkap melalui SSH

Masuk menggunakan SSH key dari terminal VS Code:

```powershell
ssh -i "$env:USERPROFILE\.ssh\ddu_hostinger_deploy" -p 65002 u706044810@46.202.186.167
```

Kemudian jalankan:

```bash
/usr/bin/php /home/u706044810/domains/dompetdanaumat.com/backend/bin/health-check.php
```

Pemeriksaan lengkap mencakup:

- PHP minimal versi 8.1 dan ekstensi penting untuk database, gambar, serta backup;
- koneksi MySQL;
- tabel `admins`, `posts`, `programs`, `stats`, `content_history`, dan `institution_profile`;
- folder uploads dapat ditulis;
- folder log privat dapat ditulis.

Perintah menghasilkan exit code `0` ketika sehat dan `1` ketika ada masalah, sehingga aman dipakai oleh cron atau pemeriksaan server.

## 4. Membaca error log

Untuk melihat 50 baris terakhir:

```bash
tail -n 50 /home/u706044810/domains/dompetdanaumat.com/backend/storage/logs/app.log
```

Untuk mencari kode kejadian yang terlihat pada respons error:

```bash
grep 'GANTI_DENGAN_EVENT_ID' /home/u706044810/domains/dompetdanaumat.com/backend/storage/logs/app.log
```

Log berbentuk satu objek JSON per baris. Password database dan kunci setup yang diketahui aplikasi disamarkan sebelum ditulis. Isi request, cookie, authorization header, password, serta query string URL tidak dicatat.

## 5. Jika status unhealthy

1. Jalankan pemeriksaan lengkap melalui SSH.
2. Pastikan semua migrasi SQL sudah dijalankan.
3. Pastikan kredensial database di `.env` benar.
4. Pastikan folder `public_html/uploads` dan `backend/storage/logs` dimiliki akun hosting serta dapat ditulis.
5. Periksa `app.log` berdasarkan `event_id`.
6. Jangan menghapus log sebelum penyebab masalah dicatat dan diperbaiki.

Tidak ada migrasi SQL baru khusus untuk fitur monitoring ini.
