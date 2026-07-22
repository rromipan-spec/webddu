# Pengujian otomatis sebelum deployment

Setiap push ke branch `main` sekarang menjalankan job **Automated integration tests** sebelum file dikirim ke Hostinger. Job deployment produksi memiliki ketergantungan pada job tersebut, sehingga deployment tidak dimulai jika satu tes gagal.

## Database yang digunakan

GitHub Actions membuat container MySQL sementara bernama `ddu_integration_test`. Database tersebut:

- bukan database Hostinger;
- tidak memakai password atau kredensial produksi;
- hanya hidup selama job GitHub Actions berjalan;
- dihapus otomatis bersama runner setelah job selesai.

Skrip `tests/prepare-test-db.php` juga menolak berjalan jika `APP_ENV` bukan `testing` atau nama database tidak mengandung kata `test`. Pengaman ini mencegah fixture pengujian dijalankan secara tidak sengaja pada database produksi.

## Cakupan pengujian

Tes memeriksa:

1. health check aplikasi;
2. sesi pengunjung anonim;
3. penolakan password admin yang salah;
4. login super admin dan pembuatan CSRF token;
5. penolakan perubahan tanpa CSRF token;
6. pembuatan artikel draft dan preview admin;
7. artikel draft tidak terlihat oleh publik;
8. publikasi artikel dan pencantuman pada sitemap;
9. artikel terjadwal masa depan belum terlihat oleh publik;
10. pembuatan dan pembacaan program publik;
11. upload PNG dan pembuatan varian gambar;
12. penolakan file PHP yang menyamar sebagai gambar;
13. pencatatan statistik WhatsApp;
14. riwayat perubahan artikel/program;
15. penghapusan data;
16. logout dan perlindungan endpoint admin;
17. respons 404.

Semua artikel, program, akun admin, statistik, gambar, session, dan log yang dibuat tes berada pada runner sementara.

## Membaca hasil di GitHub

1. Buka repository GitHub.
2. Pilih tab **Actions**.
3. Buka workflow **Deploy to Hostinger** terbaru.
4. Pastikan **Automated integration tests** hijau.
5. Setelah itu job **Deploy production** akan berjalan.

Jika tes merah, buka langkah **Run HTTP integration tests**. Log menampilkan nomor tes dan respons terakhir yang menyebabkan kegagalan. Jangan menekan deploy ulang berkali-kali tanpa membaca penyebabnya.

Setelah deployment berhasil, job **Mobile Lighthouse audit** mengukur performa, aksesibilitas, praktik terbaik, dan SEO pada website produksi. Panduan membaca laporannya tersedia di `docs/LIGHTHOUSE.md`.

## Menjalankan secara lokal

Tes dirancang untuk Linux/GitHub Actions dan membutuhkan PHP 8.1+, ekstensi aplikasi, MySQL, Bash, curl, serta jq. Gunakan database lokal khusus yang namanya mengandung `test`; jangan pernah menunjuk `.env` lokal ke database produksi.

Alur manualnya:

```bash
php tests/prepare-test-db.php
php -S 127.0.0.1:8080 -t frontend
TEST_BASE_URL=http://127.0.0.1:8080 bash tests/integration.sh
```

Password yang terlihat dalam fixture adalah kredensial sementara khusus integration test dan tidak digunakan pada Hostinger.
