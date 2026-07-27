# CTA Donasi WhatsApp dan QR/Barcode

CTA pada halaman detail artikel dan program dapat menampilkan dua pilihan berdampingan:

1. WhatsApp resmi untuk konsultasi atau konfirmasi.
2. QR/barcode donasi yang dapat dipindai atau diperbesar.

Pada layar ponsel, kedua pilihan tersusun vertikal agar QR tetap mudah dipindai.

## 1. Migrasi database

Jalankan satu kali melalui phpMyAdmin:

`database/add_donation_qr_cta.sql`

## 2. Mengatur CTA dari panel admin

1. Masuk ke menu **Artikel** atau **Program**.
2. Tambah atau edit konten.
3. Buka bagian **CTA Donasi: WhatsApp & QR/Barcode**.
4. Isi nomor WhatsApp menggunakan format internasional tanpa tanda `+`, contoh `6285121277046`.
5. Isi pesan awal WhatsApp bila diperlukan.
6. Upload QR/barcode dalam format PNG.
7. Simpan artikel atau program.

Jika gambar QR dikosongkan, CTA otomatis hanya menampilkan bagian WhatsApp dalam satu kolom.

## 3. Ketentuan PNG

- Format wajib PNG.
- Ukuran maksimal 2 MB.
- Resolusi minimal 150×150 piksel.
- Resolusi maksimal 3000×3000 piksel.
- Gunakan gambar persegi dan sisakan area putih di sekeliling kode.
- Uji pemindaian menggunakan lebih dari satu ponsel sebelum dipublikasikan.

Server memvalidasi dan menyimpan ulang PNG secara lossless agar kode tetap tajam serta metadata yang tidak diperlukan dibuang.

## 4. Keamanan

- Tampilkan hanya QR/barcode rekening atau kanal resmi lembaga.
- Pastikan nama penerima yang muncul saat dipindai sesuai informasi resmi DDU.
- Ganti QR melalui panel admin jika rekening atau kanal pembayaran berubah.
- Foto QR ikut tersimpan dalam backup folder uploads.
