# Sistem publikasi artikel dan program

## Pemasangan pertama

Sebelum deploy kode, buka **phpMyAdmin**, pilih database website, lalu jalankan seluruh isi `database/add_publication_system.sql` melalui tab **SQL**.

Migrasi tersebut:

- mempertahankan seluruh artikel dan program lama sebagai konten publik;
- menambahkan status draft dan publikasi;
- menambahkan jadwal tayang dalam zona waktu WIB;
- menambahkan kategori;
- menambahkan urutan program unggulan;
- membuat tabel riwayat perubahan admin.

Setelah SQL berhasil tanpa pesan merah, deploy dari terminal VS Code:

```powershell
.\deploy.cmd "Tambahkan sistem publikasi admin"
```

## Menggunakan status

- **Draft**: hanya terlihat di panel admin dan melalui tombol Preview ketika admin masih login.
- **Publikasi tanpa jadwal**: tayang segera setelah disimpan.
- **Publikasi dengan jadwal mendatang**: ditandai Terjadwal dan otomatis muncul ketika waktu WIB yang dipilih tercapai.

Konten draft atau terjadwal belum masuk API publik, halaman utama, halaman detail, maupun sitemap Google.

## Preview

1. Simpan artikel atau program sebagai Draft.
2. Cari konten pada daftar di bawah formulir.
3. Klik **Preview**.
4. Preview dibuka di tab baru dan hanya dapat diakses selama sesi admin masih aktif.
5. Halaman preview memakai `noindex` agar tidak dimasukkan ke hasil pencarian.

## Kategori dan program unggulan

Kategori ditampilkan pada card halaman utama dan header detail. Gunakan penamaan yang konsisten, misalnya `Wakaf`, `Pendidikan`, `Kabar Umat`, atau `Sosial`.

Pada program, isi **Urutan Program Unggulan** dengan angka. Angka lebih kecil tampil lebih dahulu. Kosongkan kolom jika program bukan unggulan.

## Riwayat perubahan

Menu **Riwayat** mencatat admin yang membuat, mengubah, atau menghapus artikel/program. Email admin disimpan sebagai snapshot sehingga catatan tetap dapat dibaca walaupun akun kemudian dinonaktifkan.
