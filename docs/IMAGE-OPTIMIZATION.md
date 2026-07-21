# Optimasi gambar

Setiap upload baru diproses server menjadi:

| Varian | Ukuran | Format | Pemakaian |
|---|---:|---|---|
| Thumbnail | 360×240 | WebP | Preview kecil admin |
| Card | 800×520 | WebP | Card artikel dan program |
| Content | Maks. 1440×1800 | WebP | Isi dan slider detail |
| Hero | 1920×1080 | WebP | Background header |
| Social | 1200×630 | JPEG | Thumbnail WhatsApp/Facebook |

Input tetap dibatasi 5 MB, maksimal 6000×6000, dan maksimal 24 megapiksel. File asli tidak disimpan secara bawaan.

Jika file asli benar-benar dibutuhkan, tambahkan ke `backend/config/.env`:

```dotenv
KEEP_UPLOAD_ORIGINAL=true
```

Untuk penggunaan normal, pertahankan `false` agar penyimpanan tetap hemat.

## Deploy

```powershell
.\deploy.cmd "Optimalkan upload gambar menjadi WebP responsif"
```

## Periksa dukungan server

Masuk ke SSH Hostinger, kemudian jalankan:

```sh
/usr/bin/php -r "echo extension_loaded('gd') && function_exists('imagewebp') ? 'GD WebP OK' : 'GD WebP BELUM AKTIF';"
```

Hasil yang dibutuhkan adalah `GD WebP OK`.

## Migrasi gambar lama

Pastikan backup terbaru sudah berhasil, lalu jalankan satu kali:

```sh
/usr/bin/php /home/u706044810/domains/dompetdanaumat.com/backend/bin/optimize-existing-images.php
```

Script akan:

1. mencari gambar lokal yang masih memakai format lama;
2. membuat semua varian baru;
3. mengganti URL di database dalam satu transaksi;
4. mempertahankan file lama sebagai pengaman.

Setelah migrasi, buka beberapa artikel dan program pada desktop serta mobile. Jika semuanya benar, file lama dapat dibersihkan pada tahap pemeliharaan berikutnya. Jangan menghapusnya sebelum pemeriksaan selesai.

## Pengujian upload baru

1. Masuk panel admin.
2. Upload satu JPG atau PNG.
3. Simpan artikel/program.
4. Pastikan card, slider, hero, dan thumbnail tautan tetap tampil.
5. Di File Manager, gambar baru akan berada dalam folder seperti:

   ```text
   public_html/uploads/0123456789abcdef0123456789abcdef/
   ```

   Folder tersebut berisi `thumb.webp`, `card.webp`, `content.webp`, `hero.webp`, dan `social.jpg`.
