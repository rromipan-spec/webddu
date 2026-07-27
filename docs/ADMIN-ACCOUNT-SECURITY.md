# Keamanan Akun Admin

Fitur keamanan akun tersedia melalui menu **Profil Saya** pada panel admin.

## Aktivasi pertama kali

1. Masuk ke hPanel Hostinger.
2. Buka **Database → phpMyAdmin**.
3. Pilih database website Dompet Dana Umat.
4. Buka tab **SQL**.
5. Salin seluruh isi `database/add_admin_security.sql`.
6. Jalankan SQL satu kali.
7. Deploy kode terbaru ke Hostinger.

Migrasi menambahkan nomor versi sesi dan tabel catatan aktivitas login. Kata sandi tetap disimpan menggunakan hash aman dari PHP dan tidak pernah disimpan sebagai teks biasa.

## Fitur untuk semua admin

- Mengubah nama dan email sendiri.
- Mengganti kata sandi sendiri dengan konfirmasi kata sandi lama.
- Melihat hak akses, waktu login terakhir, dan tanggal pembuatan akun.
- Mendapat peringatan jika terdapat minimal tiga login gagal atau pemblokiran dalam 24 jam.

Perubahan profil atau kata sandi akan menghentikan sesi akun tersebut di perangkat lain.

## Fitur khusus super admin

Menu **Admin** menampilkan waktu login terakhir setiap akun. Tombol **Reset Password** membuat kata sandi baru untuk admin lain dan langsung menghentikan semua sesi lama akun tersebut.

Super admin tidak dapat mereset atau menonaktifkan akunnya sendiri melalui daftar admin. Untuk akun sendiri, gunakan menu **Profil Saya**.

## Respons terhadap peringatan login

1. Pastikan percobaan tersebut bukan dilakukan oleh pengelola resmi.
2. Ganti kata sandi melalui **Profil Saya**.
3. Gunakan kata sandi unik minimal 15 karakter.
4. Jangan membagikan kata sandi melalui WhatsApp atau email.
5. Jika aktivitas terus terjadi, periksa catatan error dan keamanan hosting.
