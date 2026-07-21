# SEO teknis dan Google Search Console

Website menyediakan sitemap dinamis, `robots.txt`, metadata artikel/program, gambar sosial, URL canonical, data terstruktur, serta halaman 404. Sitemap membaca artikel dan program langsung dari MySQL, sehingga konten baru masuk otomatis setelah disimpan.

## 1. Migrasi kolom SEO

Sebelum deploy kode SEO, buka **hPanel > Databases > phpMyAdmin**, pilih database website, lalu buka tab **SQL**. Salin seluruh isi `database/add_seo_fields.sql`, tekan **Go/Kirim**, dan pastikan tidak ada pesan merah.

Migrasi ini tidak menghapus data. Judul, ringkasan, dan gambar utama lama tetap menjadi nilai cadangan bila kolom SEO dikosongkan.

## 2. Verifikasi setelah deploy

Buka alamat berikut:

- `https://dompetdanaumat.com/sitemap.xml`
- `https://dompetdanaumat.com/robots.txt`
- satu URL artikel, misalnya `https://dompetdanaumat.com/artikel/nama-artikel`
- satu URL program, misalnya `https://dompetdanaumat.com/nama-program`
- `https://dompetdanaumat.com/halaman-yang-tidak-ada` dan pastikan halaman 404 tampil

Pada panel admin, kolom SEO boleh dikosongkan. Jika diisi, gunakan:

- Judul SEO: sekitar 50–60 karakter, jelas dan unik.
- Deskripsi SEO: sekitar 140–160 karakter.
- Gambar sosial: foto landscape yang relevan; sistem membuat format 1200 × 630 piksel.
- Alt gambar: jelaskan isi foto secara singkat, bukan menumpuk kata kunci.

## 3. Daftarkan Google Search Console

1. Masuk ke [Google Search Console](https://search.google.com/search-console).
2. Klik **Add property/Tambahkan properti**.
3. Pilih **Domain**, lalu isi `dompetdanaumat.com` tanpa `https://`.
4. Salin nilai TXT yang diberikan Google.
5. Di hPanel buka **Domains > DNS / Nameservers > Manage DNS records**.
6. Tambahkan record **TXT** untuk root domain (`@` atau kosong, mengikuti tampilan hPanel), tempel nilai dari Google, lalu simpan.
7. Kembali ke Search Console dan klik **Verify**. Jangan hapus record TXT setelah berhasil.
8. Buka menu **Sitemaps**, isi `sitemap.xml`, lalu klik **Submit**.
9. Gunakan **URL Inspection** untuk halaman utama serta artikel/program penting, kemudian pilih **Request indexing**.

DNS bisa memerlukan waktu sebelum terbaca. Menurut panduan Google, Domain property mencakup semua protokol dan subdomain dan diverifikasi melalui DNS: [menambahkan properti](https://support.google.com/webmasters/answer/34592) dan [verifikasi kepemilikan](https://support.google.com/webmasters/answer/9008080).

## 4. Pemeriksaan rutin

- Periksa menu **Pages** untuk URL yang gagal diindeks.
- Periksa **Core Web Vitals** untuk masalah pengalaman mobile.
- Setelah mengubah URL penting, cek canonical dan sitemap.
- Jangan memasukkan halaman admin atau API ke sitemap.
- Jangan menganggap sitemap menjamin indeks; Google tetap menentukan halaman yang dirayapi dan diindeks. Panduan format sitemap resmi tersedia di [Google Search Central](https://developers.google.com/search/docs/crawling-indexing/sitemaps/build-sitemap).
