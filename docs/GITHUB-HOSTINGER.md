# Deployment otomatis GitHub ke Hostinger

Workflow `.github/workflows/deploy-hostinger.yml` menjalankan deploy setiap ada push ke branch `main`. Frontend dikirim ke `public_html`, sedangkan backend dikirim ke folder privat yang sejajar dengannya.

Workflow sengaja tidak mengunggah atau menimpa:

- `backend/config/.env`;
- `backend/storage/logs/`;
- `frontend/uploads/`;
- `frontend/setup-admin.php`.

Database juga tidak dimigrasikan otomatis. Perubahan SQL tetap dijalankan secara sadar melalui phpMyAdmin agar data produksi tidak rusak.

## 1. Rotasi rahasia dan periksa Git

Ganti `ADMIN_SETUP_KEY` yang pernah terlihat, kemudian hapus kunci serta `public_html/setup-admin.php` setelah akun admin berfungsi. Pastikan `.env` tidak pernah masuk Git:

```powershell
git check-ignore backend/config/.env
```

Perintah harus menampilkan `backend/config/.env`. Gunakan repository GitHub **private**.

## 2. Aktifkan SSH Hostinger

Di hPanel buka **Websites → Dashboard → Advanced → SSH Access**, lalu aktifkan SSH. Catat:

- IP/host SSH;
- port SSH, sering kali `65002`;
- username SSH;
- root domain absolut.

Untuk menemukan root domain, login SSH lalu jalankan `pwd` dan `ls`. Nilai yang dibutuhkan biasanya seperti:

```text
/home/u123456789/domains/dompetdanaumat.com
```

Pastikan di dalamnya ada `public_html`.

## 3. Buat SSH key khusus deployment

Di PowerShell Windows:

```powershell
ssh-keygen -t ed25519 -C "github-actions-hostinger" -f "$env:USERPROFILE\.ssh\ddu_hostinger_deploy"
```

Untuk key otomatisasi, biarkan passphrase kosong. Key ini hanya boleh dipakai untuk deployment website ini.

Dua file akan dibuat:

```text
ddu_hostinger_deploy      ← private, masukkan ke GitHub Secret
ddu_hostinger_deploy.pub  ← public, masukkan ke Hostinger
```

Di hPanel → SSH Access → **Add SSH key**, tempel isi file `.pub`. Jangan pernah memasukkan private key ke hPanel, repository, atau percakapan.

## 4. Tambahkan GitHub Actions Secrets

Di repository GitHub buka **Settings → Secrets and variables → Actions → New repository secret**. Tambahkan:

| Secret | Isi |
|---|---|
| `HOSTINGER_SSH_HOST` | IP/hostname dari hPanel |
| `HOSTINGER_SSH_PORT` | Port SSH, misalnya `65002` |
| `HOSTINGER_SSH_USER` | Username SSH Hostinger |
| `HOSTINGER_SSH_PRIVATE_KEY` | Seluruh isi private key `ddu_hostinger_deploy` |
| `HOSTINGER_DOMAIN_ROOT` | Path absolut root domain, tanpa garis miring terakhir |
| `SITE_URL` | `https://dompetdanaumat.com` |

Jangan menambahkan `DB_PASS`, isi `.env`, atau password admin ke workflow. File `.env` tetap dibuat dan dikelola hanya di Hostinger.

## 5. Buat environment production

Di GitHub buka **Settings → Environments → New environment**, buat environment bernama `production`. Jika paket GitHub mendukungnya, aktifkan required reviewers agar deploy perlu persetujuan.

## 6. Push repository

Jika repository belum dibuat:

```powershell
git init
git branch -M main
git add .
git status
git commit -m "Prepare PHP MySQL website deployment"
git remote add origin https://github.com/USERNAME/NAMA-REPO.git
git push -u origin main
```

Sebelum `git commit`, pastikan `backend/config/.env` tidak muncul pada `git status`.

Setiap push berikutnya akan deploy otomatis:

```powershell
git add .
git commit -m "Jelaskan perubahan"
git push
```

Atau gunakan satu perintah dari terminal PowerShell VS Code:

```powershell
.\deploy.cmd "Jelaskan perubahan yang dibuat"
```

Gunakan pesan berbeda sesuai perubahan, misalnya `Perbaiki login admin` atau `Ubah tampilan halaman utama`.

Pantau hasilnya di tab **Actions → Deploy to Hostinger**. Workflow juga dapat dijalankan manual melalui tombol **Run workflow**.

## 7. Jika workflow gagal

- `Permission denied (publickey)`: public key belum benar di hPanel atau private key GitHub tidak lengkap.
- `Host key verification failed`: host/port SSH salah.
- `test: ... public_html`: `HOSTINGER_DOMAIN_ROOT` salah.
- `rsync: command not found`: konfirmasi dukungan `rsync` ke Hostinger atau gunakan integrasi Git hPanel.
- API verification gagal: periksa `backend/storage/logs/app.log` dan konfigurasi `.env` produksi.
