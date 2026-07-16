<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$resource = preg_replace('/[^a-z_]/', '', (string) ($_GET['resource'] ?? ''));
$allowedTables = ['posts', 'programs'];

if ($resource === 'session' && $method === 'GET') {
    Http::json([
        'ok' => true,
        'authenticated' => Auth::check(),
        'csrf' => Auth::check() ? Auth::csrf() : null,
        'role' => Auth::check() ? Auth::role() : null,
    ]);
}

if ($resource === 'login' && $method === 'POST') {
    $body = Http::body();
    $email = (string) ($body['email'] ?? '');
    $password = (string) ($body['password'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        Http::json(['ok' => false, 'message' => 'Email atau password tidak valid.'], 422);
    }
    if (!Auth::login($email, $password)) {
        usleep(500000);
        Http::json(['ok' => false, 'message' => 'Email atau password salah.'], 401);
    }
    Http::json(['ok' => true, 'csrf' => Auth::csrf(), 'role' => Auth::role()]);
}

if ($resource === 'logout' && $method === 'POST') {
    Auth::requireAdmin();
    Auth::verifyCsrf();
    Auth::logout();
    Http::json(['ok' => true]);
}

if ($resource === 'upload' && $method === 'POST') {
    Auth::requireAdmin();
    Auth::verifyCsrf();
    handleUpload();
}

if ($resource === 'stats') {
    if ($method === 'GET') {
        Auth::requireAdmin();
        $rows = Database::connection()->query('SELECT type, COUNT(*) AS total FROM stats GROUP BY type')->fetchAll();
        $stats = ['visit' => 0, 'wa_click' => 0];
        foreach ($rows as $row) {
            $stats[$row['type']] = (int) $row['total'];
        }
        Http::json(['ok' => true, 'data' => $stats]);
    }
    if ($method === 'POST') {
        $body = Http::body();
        $type = (string) ($body['type'] ?? '');
        if (!in_array($type, ['visit', 'wa_click'], true)) {
            Http::json(['ok' => false, 'message' => 'Tipe statistik tidak valid.'], 422);
        }
        $key = 'last_stat_' . $type;
        if ((time() - (int) ($_SESSION[$key] ?? 0)) >= 10) {
            $stmt = Database::connection()->prepare('INSERT INTO stats (type) VALUES (:type)');
            $stmt->execute(['type' => $type]);
            $_SESSION[$key] = time();
        }
        Http::json(['ok' => true], 201);
    }
}

if ($resource === 'admins') {
    Auth::requireSuperAdmin();
    if ($method === 'GET') {
        $rows = Database::connection()->query(
            'SELECT id, email, display_name, role, is_active, last_login_at, created_at FROM admins ORDER BY created_at DESC'
        )->fetchAll();
        Http::json(['ok' => true, 'data' => $rows]);
    }
    if ($method === 'POST') {
        Auth::verifyCsrf();
        saveAdmin(Http::body());
    }
    if ($method === 'DELETE') {
        Auth::verifyCsrf();
        deactivateAdmin();
    }
    Http::json(['ok' => false, 'message' => 'Metode tidak diizinkan.'], 405);
}

if (!in_array($resource, $allowedTables, true)) {
    Http::json(['ok' => false, 'message' => 'Endpoint tidak ditemukan.'], 404);
}

if ($method === 'GET') {
    readResource($resource);
}

if ($method === 'POST') {
    Auth::requireAdmin();
    Auth::verifyCsrf();
    writeResource($resource, Http::body());
}

if ($method === 'DELETE') {
    Auth::requireAdmin();
    Auth::verifyCsrf();
    deleteResource($resource);
}

Http::json(['ok' => false, 'message' => 'Metode tidak diizinkan.'], 405);

function readResource(string $table): never
{
    $db = Database::connection();
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $slug = trim((string) ($_GET['slug'] ?? ''));
    $limit = max(1, min(50, (int) ($_GET['limit'] ?? 50)));
    $exclude = trim((string) ($_GET['exclude'] ?? ''));

    if ($id) {
        Auth::requireAdmin();
        $stmt = $db->prepare("SELECT * FROM {$table} WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        Http::json(['ok' => true, 'data' => $row ?: null], $row ? 200 : 404);
    }

    if ($slug !== '') {
        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            Http::json(['ok' => false, 'message' => 'Slug tidak valid.'], 422);
        }
        $stmt = $db->prepare("SELECT * FROM {$table} WHERE slug = :slug LIMIT 1");
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();
        Http::json(['ok' => true, 'data' => $row ?: null], $row ? 200 : 404);
    }

    if ($exclude !== '' && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $exclude)) {
        $stmt = $db->prepare("SELECT * FROM {$table} WHERE slug <> :slug ORDER BY created_at DESC LIMIT {$limit}");
        $stmt->execute(['slug' => $exclude]);
    } else {
        $stmt = $db->query("SELECT * FROM {$table} ORDER BY created_at DESC LIMIT {$limit}");
    }
    Http::json(['ok' => true, 'data' => $stmt->fetchAll()]);
}

function writeResource(string $table, array $body): never
{
    $id = isset($body['id']) && $body['id'] !== '' ? filter_var($body['id'], FILTER_VALIDATE_INT) : null;
    $fields = validatePayload($table, $body);
    $db = Database::connection();

    try {
        if ($id) {
            $sets = implode(', ', array_map(static fn(string $field): string => "{$field} = :{$field}", array_keys($fields)));
            $fields['id'] = $id;
            $stmt = $db->prepare("UPDATE {$table} SET {$sets} WHERE id = :id");
            $stmt->execute($fields);
            Http::json(['ok' => true, 'id' => $id]);
        }

        $columns = implode(', ', array_keys($fields));
        $params = implode(', ', array_map(static fn(string $field): string => ":{$field}", array_keys($fields)));
        $stmt = $db->prepare("INSERT INTO {$table} ({$columns}) VALUES ({$params})");
        $stmt->execute($fields);
        Http::json(['ok' => true, 'id' => (int) $db->lastInsertId()], 201);
    } catch (PDOException $error) {
        if ((string) $error->getCode() === '23000') {
            Http::json(['ok' => false, 'message' => 'Slug sudah digunakan.'], 409);
        }
        throw $error;
    }
}

function deleteResource(string $table): never
{
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$id) {
        Http::json(['ok' => false, 'message' => 'ID tidak valid.'], 422);
    }
    $stmt = Database::connection()->prepare("DELETE FROM {$table} WHERE id = :id");
    $stmt->execute(['id' => $id]);
    Http::json(['ok' => true, 'deleted' => $stmt->rowCount()]);
}

function validatePayload(string $table, array $body): array
{
    $title = trim((string) ($body['title'] ?? ''));
    $slug = strtolower(trim((string) ($body['slug'] ?? '')));
    if ($title === '' || mb_strlen($title) > 180) {
        Http::json(['ok' => false, 'message' => 'Judul wajib diisi dan maksimal 180 karakter.'], 422);
    }
    if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) || strlen($slug) > 180) {
        Http::json(['ok' => false, 'message' => 'Slug hanya boleh berisi huruf kecil, angka, dan tanda hubung.'], 422);
    }

    $image = trim((string) ($body['image'] ?? ''));
    if ($image !== '' && !filter_var($image, FILTER_VALIDATE_URL) && !str_starts_with($image, '/uploads/')) {
        Http::json(['ok' => false, 'message' => 'Alamat gambar tidak valid.'], 422);
    }
    $wa = preg_replace('/\D+/', '', (string) ($body['whatsapp_number'] ?? ''));
    if ($wa !== '' && (strlen($wa) < 8 || strlen($wa) > 16)) {
        Http::json(['ok' => false, 'message' => 'Nomor WhatsApp tidak valid.'], 422);
    }

    $payload = [
        'title' => $title,
        'slug' => $slug,
        'image' => $image,
        'excerpt' => mb_substr(trim((string) ($body['excerpt'] ?? '')), 0, 1000),
        'content' => Sanitizer::richText((string) ($body['content'] ?? '')),
        'whatsapp_number' => $wa,
        'whatsapp_message' => mb_substr(trim((string) ($body['whatsapp_message'] ?? '')), 0, 500),
    ];

    if ($table === 'programs') {
        $payload['hero_title'] = mb_substr(trim((string) ($body['hero_title'] ?? '')), 0, 180);
        $payload['hero_subtitle'] = mb_substr(trim((string) ($body['hero_subtitle'] ?? '')), 0, 300);
    }
    return $payload;
}

function handleUpload(): never
{
    if (!isset($_FILES['image']) || !is_uploaded_file($_FILES['image']['tmp_name'])) {
        Http::json(['ok' => false, 'message' => 'File gambar tidak ditemukan.'], 422);
    }
    $file = $_FILES['image'];
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 5 * 1024 * 1024) {
        Http::json(['ok' => false, 'message' => 'Upload gagal atau ukuran melebihi 5 MB.'], 422);
    }
    $info = @getimagesize($file['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = $info['mime'] ?? '';
    if (!isset($allowed[$mime])) {
        Http::json(['ok' => false, 'message' => 'Hanya JPG, PNG, dan WebP yang diperbolehkan.'], 422);
    }
    $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    $targetDir = dirname(__DIR__) . '/uploads';
    if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
        throw new RuntimeException('Folder upload tidak dapat dibuat.');
    }
    if (!move_uploaded_file($file['tmp_name'], $targetDir . '/' . $name)) {
        throw new RuntimeException('Gagal menyimpan gambar.');
    }
    Http::json(['ok' => true, 'url' => '/uploads/' . $name], 201);
}

function saveAdmin(array $body): never
{
    $email = strtolower(trim((string) ($body['email'] ?? '')));
    $name = trim((string) ($body['display_name'] ?? ''));
    $password = (string) ($body['password'] ?? '');
    $role = (string) ($body['role'] ?? 'admin');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        Http::json(['ok' => false, 'message' => 'Email admin tidak valid.'], 422);
    }
    if (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
        Http::json(['ok' => false, 'message' => 'Nama admin harus berisi 2–120 karakter.'], 422);
    }
    if (strlen($password) < 12) {
        Http::json(['ok' => false, 'message' => 'Password admin minimal 12 karakter.'], 422);
    }
    if (!in_array($role, ['super_admin', 'admin'], true)) {
        Http::json(['ok' => false, 'message' => 'Role admin tidak valid.'], 422);
    }

    $stmt = Database::connection()->prepare(
        'INSERT INTO admins (email, password_hash, display_name, role, is_active)
         VALUES (:email, :password_hash, :display_name, :role, 1)
         ON DUPLICATE KEY UPDATE
            password_hash = VALUES(password_hash),
            display_name = VALUES(display_name),
            role = VALUES(role),
            is_active = 1'
    );
    $stmt->execute([
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'display_name' => $name,
        'role' => $role,
    ]);
    Http::json(['ok' => true, 'message' => 'Admin berhasil ditambahkan atau diperbarui.'], 201);
}

function deactivateAdmin(): never
{
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$id) {
        Http::json(['ok' => false, 'message' => 'ID admin tidak valid.'], 422);
    }
    if ((int) $id === Auth::id()) {
        Http::json(['ok' => false, 'message' => 'Anda tidak dapat menonaktifkan akun sendiri.'], 422);
    }
    $stmt = Database::connection()->prepare('UPDATE admins SET is_active = 0 WHERE id = :id');
    $stmt->execute(['id' => $id]);
    Http::json(['ok' => true, 'message' => 'Admin dinonaktifkan.']);
}
