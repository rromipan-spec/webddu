# Audit Lighthouse mobile

Setelah integration test berhasil, GitHub Actions menjalankan langkah **Run mobile Lighthouse audit locally** terhadap server pengujian sementara:

1. halaman utama;
2. halaman About.

Audit mengukur performa, aksesibilitas, praktik terbaik, dan SEO dengan simulasi perangkat mobile. Pada tahap awal, batas nilai dibuat sebagai peringatan:

- Performance: 60;
- Accessibility: 85;
- Best Practices: 85;
- SEO: 90.

Nilai di bawah batas belum membatalkan deployment. Tujuannya adalah mendapatkan nilai awal yang stabil terlebih dahulu sebelum pemeriksaan dijadikan lebih ketat.

Audit tidak dijalankan langsung ke domain produksi karena perlindungan otomatis Hostinger dapat menolak browser HeadlessChrome dengan HTTP 403. Menjalankan audit pada server tes menjaga perlindungan Hostinger tetap aktif. Koneksi database, header keamanan, sitemap, robots.txt, dan status 404 pada produksi tetap diperiksa oleh langkah **Verify production API**.

## Membuka laporan

1. Buka repository GitHub lalu pilih **Actions**.
2. Buka workflow **Deploy to Hostinger** terbaru.
3. Buka hasil job **Automated integration tests** dan pastikan langkah audit selesai.
4. Pada halaman ringkasan workflow, cari bagian **Artifacts** lalu unduh file bernama `lighthouse-mobile-...`.
5. Ekstrak ZIP lalu buka laporan HTML menggunakan browser.

Artifact disimpan selama 14 hari dan tidak diunggah ke penyimpanan laporan publik.

## Arti nilai

- **Performance** menilai kecepatan pemuatan dan kestabilan visual.
- **Accessibility** memeriksa keterbacaan, label elemen, kontras, dan navigasi dasar.
- **Best Practices** memeriksa praktik keamanan dan penggunaan API browser.
- **SEO** memeriksa elemen teknis yang membantu mesin pencari memahami halaman.

Nilai Lighthouse dapat berubah beberapa poin antar-run karena kondisi jaringan dan runner GitHub. Perbaikan sebaiknya diprioritaskan berdasarkan masalah yang berulang, bukan satu hasil saja.
