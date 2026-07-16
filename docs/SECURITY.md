# Operasional keamanan

- Panel admin berada di `/admin/`; batasi URL itu dengan proteksi tambahan hPanel/Cloudflare bila tersedia.
- Session admin berakhir setelah satu jam tidak aktif.
- Backup database minimal mingguan dan uji proses pemulihannya.
- Pantau ukuran tabel `stats` dan bersihkan data lama secara berkala.
- Jika kredensial database atau password admin pernah tersebar, rotasi segera dan tutup semua session dengan mengganti nama cookie di `backend/src/Auth.php`.
- File gambar tetap dianggap tidak tepercaya walaupun tipe MIME sudah diperiksa; jangan memberi izin eksekusi pada `uploads/`.
