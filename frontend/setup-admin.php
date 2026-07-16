<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/backend/bootstrap.php';

$db = Database::connection();

$setupKey = Config::get('ADMIN_SETUP_KEY', '');
if (strlen($setupKey) < 32) {
    http_response_code(503);
    exit('ADMIN_SETUP_KEY minimal 32 karakter belum diatur pada backend/config/.env.');
}

if (empty($_SESSION['setup_csrf'])) {
    $_SESSION['setup_csrf'] = bin2hex(random_bytes(32));
}

$message = '';
$success = false;
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $csrf = (string) ($_POST['csrf'] ?? '');
    $key = (string) ($_POST['setup_key'] ?? '');
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $name = trim((string) ($_POST['display_name'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirmation = (string) ($_POST['password_confirmation'] ?? '');

    if (!hash_equals((string) $_SESSION['setup_csrf'], $csrf)) {
        $message = 'Token keamanan tidak valid. Muat ulang halaman.';
    } elseif (!hash_equals($setupKey, $key)) {
        usleep(500000);
        $message = 'Kunci setup salah.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Email tidak valid.';
    } elseif (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
        $message = 'Nama harus berisi 2–120 karakter.';
    } elseif (strlen($password) < 12) {
        $message = 'Password minimal 12 karakter.';
    } elseif (!hash_equals($password, $passwordConfirmation)) {
        $message = 'Konfirmasi password tidak sama.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        if ($hash === false || !password_verify($password, $hash)) {
            throw new RuntimeException('Server gagal membuat atau memverifikasi hash password.');
        }
        $stmt = $db->prepare(
            "INSERT INTO admins (email, password_hash, display_name, role, is_active)
             VALUES (:email, :password_hash, :display_name, 'super_admin', 1)
             ON DUPLICATE KEY UPDATE
                password_hash = VALUES(password_hash),
                display_name = VALUES(display_name),
                role = 'super_admin',
                is_active = 1"
        );
        $stmt->execute(['email' => $email, 'password_hash' => $hash, 'display_name' => $name]);
        unset($_SESSION['setup_csrf']);
        $success = true;
        $message = 'Password untuk ' . $email . ' berhasil dibuat dan diverifikasi. Hapus setup-admin.php setelah login berhasil.';
    }
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Setup/Reset Admin DDU</title>
    <style>
        body{font-family:Arial,sans-serif;background:#f1f5f9;margin:0;padding:40px 16px;color:#172033}.card{max-width:480px;margin:40px auto;background:#fff;padding:30px;border-radius:14px;box-shadow:0 12px 35px #0f172a1a}label{display:block;font-weight:700;margin:16px 0 6px}input{box-sizing:border-box;width:100%;padding:12px;border:1px solid #cbd5e1;border-radius:8px}button{width:100%;padding:13px;margin-top:22px;border:0;border-radius:8px;background:#164e9b;color:#fff;font-weight:700;cursor:pointer}.message{padding:12px;border-radius:8px;background:#fff3cd;margin-bottom:16px}.success{background:#dcfce7}small{color:#64748b;line-height:1.5;display:block;margin-top:15px}
    </style>
</head>
<body><main class="card"><h1>Setup/Reset Super Admin</h1>
<?php if ($message !== ''): ?><div class="message <?= $success ? 'success' : '' ?>"><?= e($message) ?></div><?php endif; ?>
<?php if ($success): ?><a href="admin/">Masuk ke panel admin</a>
<?php else: ?><form method="post" autocomplete="off">
    <input type="hidden" name="csrf" value="<?= e((string) $_SESSION['setup_csrf']) ?>">
    <label for="setup_key">Kunci setup dari .env</label><input id="setup_key" name="setup_key" type="password" required>
    <label for="display_name">Nama admin</label><input id="display_name" name="display_name" maxlength="120" required>
    <label for="email">Email</label><input id="email" name="email" type="email" autocomplete="username" required>
    <label for="password">Password</label><input id="password" name="password" type="password" minlength="12" autocomplete="new-password" required>
    <label for="password_confirmation">Ulangi Password</label><input id="password_confirmation" name="password_confirmation" type="password" minlength="12" autocomplete="new-password" required>
    <button type="submit">Buat/Reset Super Admin</button>
    <small>Password otomatis di-hash dan diverifikasi oleh PHP. Halaman ini dilindungi kunci setup, tetapi tetap harus dihapus setelah login berhasil.</small>
</form><?php endif; ?>
</main></body></html>
