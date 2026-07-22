# Operasional keamanan

- Panel admin berada di `/admin/`; batasi URL itu dengan proteksi tambahan hPanel/Cloudflare bila tersedia.
- Halaman `setup-admin.php` dinonaktifkan secara bawaan dan otomatis dihapus dari Hostinger saat deployment.
- Percobaan login dibatasi di server: akun diblokir sementara setelah 5 kegagalan dalam 15 menit. Alamat IP juga dibatasi untuk mengurangi percobaan massal.
- Session admin berakhir setelah satu jam tidak aktif.
- Backup database minimal mingguan dan uji proses pemulihannya.
- Pantau ukuran tabel `stats` dan bersihkan data lama secara berkala.
- Jika kredensial database atau password admin pernah tersebar, rotasi segera dan tutup semua session dengan mengganti nama cookie di `backend/src/Auth.php`.
- File gambar tetap dianggap tidak tepercaya walaupun tipe MIME sudah diperiksa; jangan memberi izin eksekusi pada `uploads/`.
- Jangan mengirim isi `backend/storage/logs/app.log` melalui chat atau mempublikasikannya. Gunakan hanya `event_id` ketika menelusuri sebuah error.
- Endpoint `/health.php` sengaja hanya mengembalikan status umum. Detail pemeriksaan hanya boleh dibaca melalui SSH.

## Pengaturan wajib di Hostinger

Edit `backend/config/.env` melalui File Manager atau SSH dan pastikan nilai berikut ada:

```dotenv
APP_ENV=production
APP_URL=https://dompetdanaumat.com
ADMIN_SETUP_ENABLED=false
ADMIN_SETUP_KEY=ganti_dengan_kunci_acak_baru
LOG_MAX_SIZE_MB=5
LOG_RETENTION_FILES=5
```

Untuk membuat kunci acak di PowerShell VS Code, jalankan:

```powershell
$rng = [Security.Cryptography.RandomNumberGenerator]::Create(); $bytes = New-Object byte[] 48; $rng.GetBytes($bytes); $rng.Dispose(); [Convert]::ToBase64String($bytes)
```

Salin hasilnya menjadi nilai `ADMIN_SETUP_KEY`. Jangan menaruh `.env`, password database, kunci setup, atau private key SSH di GitHub.

Setelah deployment, buka `https://dompetdanaumat.com/setup-admin.php`. Hasil yang benar adalah halaman 404/tidak ditemukan. Kemudian pastikan login admin masih berhasil.
