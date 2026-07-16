# Mengelola admin melalui MySQL

## Cara termudah: setup otomatis

Tambahkan kunci acak minimal 32 karakter ke `backend/config/.env`:

```env
ADMIN_SETUP_KEY=ganti_dengan_kunci_acak_rahasia_minimal_32_karakter
```

Kunci acak dapat dibuat melalui Terminal Hostinger:

```bash
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

Upload `frontend/setup-admin.php`, buka `https://domainanda.com/setup-admin.php`, lalu masukkan kunci setup, nama, email, password biasa, dan konfirmasi password. PHP otomatis membuat serta memverifikasi hash. Jika email sudah ada dengan password yang salah, akun tersebut otomatis diperbaiki dan dijadikan `super_admin`.

Setelah login berhasil, segera hapus `public_html/setup-admin.php` dan hapus `ADMIN_SETUP_KEY` dari `.env`. Selanjutnya gunakan menu **Admin** di panel untuk menambah atau memperbarui akun lain.

## 1. Buat tabel admin

Jika `schema.sql` sudah pernah diimpor sebelum fitur ini dibuat, import `database/add_admins.sql` melalui phpMyAdmin. Jika memasang database dari awal, cukup import `database/schema.sql`.

## 2. Buat hash password

Jalankan melalui Terminal Hostinger atau komputer dengan PHP:

```bash
php -r "echo password_hash('PASSWORD_ADMIN_YANG_KUAT', PASSWORD_DEFAULT), PHP_EOL;"
```

Salin seluruh hasil yang diawali `$2y$`. Jangan memakai MD5, SHA1, atau menyimpan password biasa di database.

## 3. Tambah admin

Ganti email, nama, hash, dan role sebelum menjalankan:

```sql
INSERT INTO admins (email, password_hash, display_name, role, is_active)
VALUES (
    'admin@domainanda.com',
    '$2y$10$GANTI_DENGAN_HASH_DARI_PHP',
    'Administrator Utama',
    'super_admin',
    1
);
```

Gunakan role `super_admin` untuk pemilik utama dan `admin` untuk pengelola biasa.

## 4. Ubah data admin

```sql
UPDATE admins
SET email = 'emailbaru@domainanda.com',
    display_name = 'Nama Baru',
    role = 'admin'
WHERE id = 1;
```

Selalu periksa ID lebih dahulu:

```sql
SELECT id, email, display_name, role, is_active, last_login_at, created_at
FROM admins
ORDER BY id;
```

## 5. Ganti password

Buat hash baru dengan PHP, kemudian jalankan:

```sql
UPDATE admins
SET password_hash = '$2y$10$GANTI_DENGAN_HASH_BARU'
WHERE id = 1;
```

## 6. Nonaktifkan atau aktifkan akun

Nonaktifkan tanpa menghapus riwayat:

```sql
UPDATE admins SET is_active = 0 WHERE id = 2;
```

Aktifkan kembali:

```sql
UPDATE admins SET is_active = 1 WHERE id = 2;
```

Hindari menghapus satu-satunya akun `super_admin`. Gunakan `is_active = 0` sebagai pilihan utama.
