# Analitik pengunjung dan perangkat admin

Fitur analitik DDU mencatat informasi teknis secara anonim untuk memahami penggunaan website. Sistem tidak menyimpan alamat IP pengunjung dalam bentuk mentah dan tidak mencoba mengetahui nama atau identitas pengunjung.

## 1. Mengaktifkan database

Setelah deployment berhasil, buka phpMyAdmin. Untuk instalasi lama jalankan migrasi yang belum pernah diterapkan secara berurutan:

```text
database/add_visitor_analytics.sql
database/upgrade_analytics_health.sql
```

Data lama pada tabel `stats` tetap dipertahankan. Data lama tetap masuk hitungan total, tetapi tidak memiliki rincian perangkat.

## 2. Konfigurasi `.env`

Tambahkan nilai berikut ke `backend/config/.env` di Hostinger:

```dotenv
ANALYTICS_RETENTION_DAYS=180
ANALYTICS_HASH_KEY=ganti_dengan_string_acak_minimal_32_karakter
```

`ANALYTICS_HASH_KEY` tidak boleh dimasukkan ke GitHub. Mengganti kunci ini akan memutus kesinambungan penghitungan pengunjung anonim lama dan baru.

## 3. Informasi yang dicatat

- waktu kejadian;
- path halaman tanpa query string;
- jenis perangkat: desktop, ponsel, atau tablet;
- keluarga browser dan sistem operasi;
- kelompok ukuran layar;
- sumber rujukan seperti Google atau Instagram;
- ID pengunjung dan sesi yang sudah di-hash;
- klik tombol WhatsApp.
- event ID unik untuk mencegah hitungan ganda;
- sesi lintas-tab yang berakhir setelah 30 menit tidak aktif;
- UTM sumber, medium, kampanye, dan konten;
- funnel lihat konten, sesi terlibat, dan konversi unik;
- Core Web Vitals LCP, INP, dan CLS dari sampel browser.

Sistem tidak mencatat isi formulir, nomor telepon pengunjung, kata sandi, alamat IP mentah, atau URL lengkap yang mungkin mengandung query sensitif. Bot umum dan Lighthouse tidak dimasukkan ke statistik. Sinyal browser **Do Not Track** dan **Global Privacy Control** dihormati dengan tidak mengirimkan kejadian analitik.

## 4. Perangkat login admin

Menu **Profil Saya** menampilkan perangkat milik akun yang sedang login. Super admin dapat melihat perangkat seluruh admin melalui menu **Admin**. Sesi selain perangkat yang sedang dipakai dapat dihentikan dari panel. Alamat jaringan hanya ditampilkan dalam bentuk tersamarkan, misalnya `192.168.xxx.xxx`; alamat lengkap hanya diubah menjadi hash untuk pemeriksaan keamanan.

Setelah migrasi pertama, logout lalu login kembali agar perangkat aktif tercatat secara lengkap.

## 5. Pembersihan otomatis

Pencatatan kunjungan sesekali membersihkan data yang melewati masa retensi. Untuk memastikan pembersihan berjalan setiap hari, tambahkan cron job Hostinger:

```bash
/usr/bin/php /home/u706044810/domains/dompetdanaumat.com/backend/bin/prune-analytics.php
```

Jalankan satu kali setiap hari. Nilai retensi yang direkomendasikan adalah 180 hari. Pilihan laporan satu tahun hanya lengkap bila retensi disetel 365 hari.

## 6. Cara membaca statistik

- Tampilan halaman bukan jumlah orang; satu pengunjung dapat membuka beberapa halaman.
- Pengunjung dan sesi bersifat perkiraan berdasarkan ID anonim pada browser.
- Browser yang menghapus penyimpanan lokal dapat dihitung sebagai pengunjung baru.
- Model ponsel spesifik tidak selalu tersedia sehingga panel hanya menampilkan jenis perangkat, OS, dan browser.
- Sesi berakhir setelah 30 menit tanpa aktivitas dan dapat berlanjut di tab lain pada browser yang sama.
- Sesi terlibat adalah sesi dengan minimal 10 detik aktif, dua tampilan halaman, atau kedalaman gulir minimal 50%.
- Konversi WhatsApp adalah jumlah **sesi unik** yang mengklik WhatsApp dibagi seluruh sesi. Klik berulang dalam satu sesi tidak menaikkan konversi.
- Perbandingan memakai periode sebelumnya yang sama panjang.
- Hari pada grafik mengikuti `Asia/Jakarta` (WIB), sedangkan penyimpanan waktu tetap UTC.
- Core Web Vitals memakai p75 dan disampel 20%; sampel sedikit dibaca sebagai indikasi.

## 7. Disiplin kampanye

Gunakan parameter yang konsisten pada tautan kampanye, misalnya:

```text
https://dompetdanaumat.com/wakaf-asrama?utm_source=instagram&utm_medium=social&utm_campaign=wakaf_asrama_2026
```

Jangan memasukkan nama, nomor telepon, atau informasi pribadi ke parameter UTM. Beri `data-analytics-cta="nama-tombol"` pada CTA baru agar sumber klik mudah dibedakan.
