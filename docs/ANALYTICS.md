# Analitik pengunjung dan perangkat admin

Fitur analitik DDU mencatat informasi teknis secara anonim untuk memahami penggunaan website. Sistem tidak menyimpan alamat IP pengunjung dalam bentuk mentah dan tidak mencoba mengetahui nama atau identitas pengunjung.

## 1. Mengaktifkan database

Setelah deployment berhasil, buka phpMyAdmin lalu jalankan seluruh isi:

```text
database/add_visitor_analytics.sql
```

Data lama pada tabel `stats` tetap dipertahankan. Data lama tetap masuk hitungan total, tetapi tidak memiliki rincian perangkat.

## 2. Konfigurasi `.env`

Tambahkan nilai berikut ke `backend/config/.env` di Hostinger:

```dotenv
ANALYTICS_RETENTION_DAYS=90
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

Sistem tidak mencatat isi formulir, nomor telepon pengunjung, kata sandi, alamat IP mentah, atau URL lengkap yang mungkin mengandung query sensitif. Bot umum dan Lighthouse tidak dimasukkan ke statistik. Sinyal browser **Do Not Track** dan **Global Privacy Control** dihormati dengan tidak mengirimkan kejadian analitik.

## 4. Perangkat login admin

Menu **Profil Saya** menampilkan perangkat milik akun yang sedang login. Super admin dapat melihat perangkat seluruh admin melalui menu **Admin**. Sesi selain perangkat yang sedang dipakai dapat dihentikan dari panel. Alamat jaringan hanya ditampilkan dalam bentuk tersamarkan, misalnya `192.168.xxx.xxx`; alamat lengkap hanya diubah menjadi hash untuk pemeriksaan keamanan.

Setelah migrasi pertama, logout lalu login kembali agar perangkat aktif tercatat secara lengkap.

## 5. Pembersihan otomatis

Pencatatan kunjungan sesekali membersihkan data yang melewati masa retensi. Untuk memastikan pembersihan berjalan setiap hari, tambahkan cron job Hostinger:

```bash
/usr/bin/php /home/u706044810/domains/dompetdanaumat.com/backend/bin/prune-analytics.php
```

Jalankan satu kali setiap hari. Nilai retensi yang direkomendasikan adalah 90 hari.

## 6. Cara membaca statistik

- Tampilan halaman bukan jumlah orang; satu pengunjung dapat membuka beberapa halaman.
- Pengunjung dan sesi bersifat perkiraan berdasarkan ID anonim pada browser.
- Browser yang menghapus penyimpanan lokal dapat dihitung sebagai pengunjung baru.
- Model ponsel spesifik tidak selalu tersedia sehingga panel hanya menampilkan jenis perangkat, OS, dan browser.
- Konversi WhatsApp dihitung dari jumlah klik WhatsApp dibagi jumlah sesi pada periode terpilih.
