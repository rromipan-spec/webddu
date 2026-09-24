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

Semua judul, deskripsi, media, tombol, nominal, dan elemen CTA bersifat opsional. Section dapat dinaikkan, diturunkan, diduplikasi, disembunyikan, atau dihapus dari panel admin.

## Batas aman

- Maksimal 50 section per program.
- Maksimal 24 media per galeri.
- Maksimal 20 item untuk Dampak dan FAQ.
- Tautan menerima HTTPS, path lokal yang diawali `/`, atau anchor yang diawali `#`.
- Media upload tetap mengikuti validasi ukuran, tipe, dan optimasi endpoint upload yang sudah ada.

## Kompatibilitas

Program lama tetap dirender memakai layout lama jika belum memiliki section. Ketika program lama pertama kali diedit, admin membentuk Hero, Konten, dan CTA awal dari data lama supaya konten tidak hilang.
