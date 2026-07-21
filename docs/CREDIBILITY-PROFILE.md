# Profil kredibilitas lembaga

## Instalasi

Sebelum deploy, jalankan seluruh isi `database/add_institution_profile.sql` melalui tab **SQL** di phpMyAdmin. Migrasi ini tidak mengubah artikel, program, admin, maupun transaksi lain.

Setelah SQL berhasil, deploy:

```powershell
.\deploy.cmd "Tambahkan profil kredibilitas dan kebijakan privasi"
```

## Mengisi informasi

Login ke panel admin lalu buka menu **Kredibilitas**. Isi hanya data yang sudah diperiksa terhadap dokumen asli:

- nama badan hukum;
- nomor akta dan nomor pengesahan kementerian;
- NPWP lembaga;
- alamat dan kontak resmi;
- nama serta jabatan pengurus;
- rekening, QRIS, dan nama pemilik rekening resmi;
- laporan penghimpunan dan penyaluran;
- tautan dokumentasi penerima manfaat;
- disclaimer kanal resmi.

Gunakan satu baris untuk satu pengurus, rekening, laporan, atau dokumentasi. Untuk laporan dan dokumentasi, tautan yang dimulai dengan `https://` akan menjadi tautan **Buka dokumen** pada halaman publik.

Jangan mempublikasikan:

- NIK, foto KTP, informasi login, atau data bank pribadi;
- alamat rumah pribadi pengurus;
- identitas anak atau data penerima manfaat yang belum memperoleh persetujuan yang sesuai;
- nomor rekening yang belum dikonfirmasi sebagai rekening lembaga.

## Halaman publik

- `https://dompetdanaumat.com/transparansi.html`
- `https://dompetdanaumat.com/kebijakan-privasi.html`

Kedua halaman otomatis masuk sitemap. Footer seluruh halaman juga menyediakan tautan menuju Transparansi dan Kebijakan Privasi.

Kebijakan privasi yang disediakan merupakan kerangka operasional website, bukan pendapat hukum. Lembaga sebaiknya meninjau ulang kebijakan bersama pihak yang memahami proses internal dan kewajiban hukumnya. Referensi utama yang digunakan adalah [UU Nomor 27 Tahun 2022 tentang Pelindungan Data Pribadi](https://peraturan.bpk.go.id/Details/229798/uu-no-27-tahun-2022).
