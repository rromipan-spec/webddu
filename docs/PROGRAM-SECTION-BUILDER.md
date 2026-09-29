# Section Builder Program

Panel **Program** menggunakan Section Builder agar landing page dapat disusun tanpa batas layout yang kaku. Setiap program menyimpan section secara terpisah, berurutan, dan dapat disembunyikan tanpa dihapus.

## Aktivasi pada website lama

Instalasi baru cukup mengimpor `database/schema.sql`. Untuk database produksi yang sudah berjalan, impor sekali:

```text
database/add_program_section_builder.sql
```

Lakukan backup database sebelum migrasi. API tetap dapat membaca program lama ketika tabel section belum tersedia, tetapi penyimpanan Section Builder baru akan ditolak sampai migrasi dijalankan.

## Jenis section

- **Hero / Header** — gambar desktop dan mobile, video lokal, YouTube, Google Drive, overlay, tinggi, tombol, atau tautan seluruh hero.
- **Konten Teks + Media** — paragraf dengan media di atas, bawah, kiri, kanan, atau sebagai background.
- **Progres Donasi** — target, dana terkumpul, kekurangan, jumlah donatur, tenggat, dan persentase otomatis.
- **Galeri** — satu media, grid 2/3 kolom, featured, mosaic, atau carousel.
- **Dampak / Statistik** — kartu angka dan capaian dalam 2–4 kolom.
- **CTA Donasi** — QR/barcode dan WhatsApp yang dapat berbeda untuk setiap section.
- **FAQ** — daftar pertanyaan dan jawaban lipat.
- **Kanvas Fleksibel** — editor visual tiga panel untuk menyusun judul, paragraf, kutipan, daftar poin, gambar, video, tombol, kolom, jarak, garis, progres, QR/barcode, dan WhatsApp.

Semua judul, deskripsi, media, tombol, nominal, dan elemen CTA bersifat opsional. Section dapat dinaikkan, diturunkan, diduplikasi, disembunyikan, atau dihapus dari panel admin.

## Editor visual

Kanvas Fleksibel memakai alur kerja yang menyerupai aplikasi desain, tetapi hasil akhirnya tetap HTML responsif dan ringan:

1. Pilih **Kanvas Fleksibel**, lalu tambahkan elemen dari panel kiri.
2. Klik elemen pada kanvas untuk membuka pengaturan di panel kanan.
3. Tarik elemen ke bagian atas/bawah elemen lain untuk mengubah urutan. Jatuhkan di tepi kiri/kanan untuk membuat pasangan dua kolom 50:50 secara otomatis, misalnya foto di kiri dan paragraf di kanan. Pada layar mobile pasangan tersebut otomatis ditumpuk agar tetap terbaca.
4. Gunakan tombol **½ Kiri**, **½ Kanan**, atau **Lebar penuh** agar pasangan foto dan teks dapat ditempatkan secara pasti tanpa bergantung pada drag-and-drop.
5. Atur lebar, perataan, tipografi, tinggi/fokus media, crop atau contain, warna, garis tepi, bayangan, radius, padding, tautan, serta visibilitas desktop/tablet/mobile.
6. Gunakan tombol **Desktop**, **Tablet**, dan **Mobile**, kontrol **Zoom**, serta **Grid** untuk memeriksa responsivitas dan kerapian sebelum menyimpan.
7. Klik **Buka tab baru** untuk memakai Studio Kanvas selebar layar. Draft pada studio dan tab admin utama disinkronkan otomatis melalui penyimpanan browser; penyimpanan akhir program tetap dilakukan dari tab admin utama.

Editor menyimpan draft otomatis di browser, menyediakan tombol simpan draft langsung, undo/redo hingga 40 langkah, dan menyimpan sampai 15 titik revisi lokal. Setiap penyimpanan program ke server juga tetap tercatat di menu **Riwayat**. Program lama otomatis dinormalisasi saat diedit dan tidak perlu dibuat ulang.

## Batas aman

- Maksimal 50 section per program.
- Maksimal 80 elemen dalam satu Kanvas Fleksibel.
- Maksimal 24 media per galeri.
- Maksimal 20 item untuk Dampak dan FAQ.
- Tautan menerima HTTPS, path lokal yang diawali `/`, atau anchor yang diawali `#`.
- Media upload tetap mengikuti validasi ukuran, tipe, dan optimasi endpoint upload yang sudah ada.

## Kompatibilitas

Program lama tetap dirender memakai layout lama jika belum memiliki section. Ketika program lama pertama kali diedit, admin membentuk Hero, Konten, dan CTA awal dari data lama supaya konten tidak hilang.
