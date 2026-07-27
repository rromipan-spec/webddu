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
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '' || strlen($password) > 1024) {
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

if ($resource === 'profile') {
    Auth::requireAdmin();
    if ($method === 'GET') {
        serveOwnProfile();
    }
    if ($method === 'POST') {
        Auth::verifyCsrf();
        updateOwnAccount(Http::body());
    }
    Http::json(['ok' => false, 'message' => 'Metode tidak diizinkan.'], 405);
}

if ($resource === 'admin_password_reset') {
    Auth::requireSuperAdmin();
    if ($method !== 'POST') {
        Http::json(['ok' => false, 'message' => 'Metode tidak diizinkan.'], 405);
    }
    Auth::verifyCsrf();
    resetAdminPassword(Http::body());
}

if ($resource === 'upload' && $method === 'POST') {
    Auth::requireAdmin();
    Auth::verifyCsrf();
    handleUpload();
}

if ($resource === 'video_upload' && $method === 'POST') {
    Auth::requireAdmin();
    Auth::verifyCsrf();
    handleVideoUpload();
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

if ($resource === 'gold_price') {
    if ($method !== 'GET') {
        Http::json(['ok' => false, 'message' => 'Metode tidak diizinkan.'], 405);
    }
    serveGoldPrice();
}

if ($resource === 'admins') {
    Auth::requireSuperAdmin();
    if ($method === 'GET') {
        $rows = Database::connection()->query(
            'SELECT id, email, display_name, role, is_active, last_login_at, created_at, updated_at
             FROM admins ORDER BY created_at DESC'
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

if ($resource === 'institution') {
    if ($method === 'GET') {
        $rows = Database::connection()->query('SELECT profile_key, profile_value, updated_at FROM institution_profile ORDER BY profile_key')->fetchAll();
        $profile = [];
        $updatedAt = null;
        foreach ($rows as $row) {
            $profile[(string) $row['profile_key']] = (string) $row['profile_value'];
            if ($updatedAt === null || (string) $row['updated_at'] > $updatedAt) $updatedAt = (string) $row['updated_at'];
        }
        Http::json(['ok' => true, 'data' => $profile, 'updated_at' => $updatedAt]);
    }
    if ($method === 'POST') {
        Auth::requireAdmin();
        Auth::verifyCsrf();
        saveInstitutionProfile(Http::body());
    }
    Http::json(['ok' => false, 'message' => 'Metode tidak diizinkan.'], 405);
}

if ($resource === 'history') {
    Auth::requireAdmin();
    if ($method !== 'GET') {
        Http::json(['ok' => false, 'message' => 'Metode tidak diizinkan.'], 405);
    }
    $limit = max(1, min(200, (int) ($_GET['limit'] ?? 100)));
    $rows = Database::connection()->query(
        "SELECT id, content_type, content_id, action, admin_id, admin_email, summary, created_at
         FROM content_history ORDER BY created_at DESC, id DESC LIMIT {$limit}"
    )->fetchAll();
    Http::json(['ok' => true, 'data' => $rows]);
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
    $preview = (string) ($_GET['preview'] ?? '') === '1' && Auth::check();
    $publicWhere = "status = 'published' AND (published_at IS NULL OR published_at <= UTC_TIMESTAMP())";

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
        $publicationFilter = $preview ? '' : " AND {$publicWhere}";
        $stmt = $db->prepare("SELECT * FROM {$table} WHERE slug = :slug{$publicationFilter} LIMIT 1");
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();
        if ($row && !$preview) $row = publicContentRow($row);
        Http::json(['ok' => true, 'data' => $row ?: null], $row ? 200 : 404);
    }

    $adminListing = (string) ($_GET['admin'] ?? '') === '1';
    if ($adminListing) {
        Auth::requireAdmin();
        readAdminContentListing($table);
    }
    $where = $adminListing ? '' : "WHERE {$publicWhere}";
    $order = $table === 'programs' && !$adminListing
        ? 'ORDER BY CASE WHEN featured_order IS NULL THEN 1 ELSE 0 END, featured_order ASC, published_at DESC'
        : 'ORDER BY created_at DESC';

    if ($exclude !== '' && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $exclude)) {
        $where = $adminListing ? 'WHERE slug <> :slug' : "WHERE slug <> :slug AND {$publicWhere}";
        $stmt = $db->prepare("SELECT * FROM {$table} {$where} {$order} LIMIT {$limit}");
        $stmt->execute(['slug' => $exclude]);
    } else {
        $stmt = $db->query("SELECT * FROM {$table} {$where} {$order} LIMIT {$limit}");
    }
    $rows = $stmt->fetchAll();
    if (!$adminListing) $rows = array_map('publicContentRow', $rows);
    Http::json(['ok' => true, 'data' => $rows]);
}

function readAdminContentListing(string $table): never
{
    $db = Database::connection();
    $search = mb_substr(trim((string) ($_GET['search'] ?? '')), 0, 180);
    $status = (string) ($_GET['status'] ?? 'all');
    $category = mb_substr(trim((string) ($_GET['category'] ?? '')), 0, 100);
    $sort = (string) ($_GET['sort'] ?? 'created_desc');
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $perPage = max(5, min(50, (int) ($_GET['per_page'] ?? 10)));

    if (!in_array($status, ['all', 'draft', 'scheduled', 'published'], true)) {
        $status = 'all';
    }
    $sortSql = [
        'created_desc' => 'created_at DESC, id DESC',
        'created_asc' => 'created_at ASC, id ASC',
        'updated_desc' => 'updated_at DESC, id DESC',
        'published_desc' => 'COALESCE(published_at, created_at) DESC, id DESC',
        'published_asc' => 'COALESCE(published_at, created_at) ASC, id ASC',
    ][$sort] ?? 'created_at DESC, id DESC';

    $conditions = [];
    $parameters = [];
    if ($search !== '') {
        $conditions[] = 'title LIKE :search';
        $parameters['search'] = '%' . addcslashes($search, '%_\\') . '%';
    }
    if ($category !== '') {
        $conditions[] = 'category = :category';
        $parameters['category'] = $category;
    }
    if ($status === 'draft') {
        $conditions[] = "status = 'draft'";
    } elseif ($status === 'scheduled') {
        $conditions[] = "status = 'published' AND published_at > UTC_TIMESTAMP()";
    } elseif ($status === 'published') {
        $conditions[] = "status = 'published' AND (published_at IS NULL OR published_at <= UTC_TIMESTAMP())";
    }

    $where = $conditions ? 'WHERE ' . implode(' AND ', array_map(
        static fn(string $condition): string => '(' . $condition . ')',
        $conditions
    )) : '';
    $countStatement = $db->prepare("SELECT COUNT(*) FROM {$table} {$where}");
    $countStatement->execute($parameters);
    $total = (int) $countStatement->fetchColumn();
    $totalPages = max(1, (int) ceil($total / $perPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;

    $statement = $db->prepare(
        "SELECT * FROM {$table} {$where} ORDER BY {$sortSql} LIMIT :limit OFFSET :offset"
    );
    foreach ($parameters as $key => $value) {
        $statement->bindValue(':' . $key, $value, PDO::PARAM_STR);
    }
    $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
    $statement->execute();

    $categories = $db->query(
        "SELECT DISTINCT category FROM {$table}
         WHERE category <> '' ORDER BY category ASC"
    )->fetchAll(PDO::FETCH_COLUMN);

    Http::json([
        'ok' => true,
        'data' => $statement->fetchAll(),
        'meta' => [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
            'categories' => array_values(array_map('strval', $categories)),
        ],
    ]);
}

function publicContentRow(array $row): array
{
    unset($row['created_by'], $row['updated_by']);
    return $row;
}

function writeResource(string $table, array $body): never
{
    $id = isset($body['id']) && $body['id'] !== '' ? filter_var($body['id'], FILTER_VALIDATE_INT) : null;
    $fields = validatePayload($table, $body);
    $db = Database::connection();

    try {
        $db->beginTransaction();
        if ($id) {
            $oldStatement = $db->prepare("SELECT * FROM {$table} WHERE id = :id LIMIT 1 FOR UPDATE");
            $oldStatement->execute(['id' => $id]);
            $before = $oldStatement->fetch();
            if (!$before) {
                $db->rollBack();
                Http::json(['ok' => false, 'message' => 'Data tidak ditemukan.'], 404);
            }
            $fields['updated_by'] = Auth::id();
            $sets = implode(', ', array_map(static fn(string $field): string => "{$field} = :{$field}", array_keys($fields)));
            $fields['id'] = $id;
            $stmt = $db->prepare("UPDATE {$table} SET {$sets} WHERE id = :id");
            $stmt->execute($fields);
            $afterStatement = $db->prepare("SELECT * FROM {$table} WHERE id = :id LIMIT 1");
            $afterStatement->execute(['id' => $id]);
            recordContentHistory($db, $table, (int) $id, 'updated', $before, $afterStatement->fetch() ?: []);
            $db->commit();
            Http::json(['ok' => true, 'id' => $id]);
        }

        $fields['created_by'] = Auth::id();
        $fields['updated_by'] = Auth::id();
        $columns = implode(', ', array_keys($fields));
        $params = implode(', ', array_map(static fn(string $field): string => ":{$field}", array_keys($fields)));
        $stmt = $db->prepare("INSERT INTO {$table} ({$columns}) VALUES ({$params})");
        $stmt->execute($fields);
        $newId = (int) $db->lastInsertId();
        $newStatement = $db->prepare("SELECT * FROM {$table} WHERE id = :id LIMIT 1");
        $newStatement->execute(['id' => $newId]);
        recordContentHistory($db, $table, $newId, 'created', [], $newStatement->fetch() ?: []);
        $db->commit();
        Http::json(['ok' => true, 'id' => $newId], 201);
    } catch (PDOException $error) {
        if ($db->inTransaction()) $db->rollBack();
        if ((string) $error->getCode() === '23000') {
            Http::json(['ok' => false, 'message' => 'Slug sudah digunakan.'], 409);
        }
        throw $error;
    } catch (Throwable $error) {
        if ($db->inTransaction()) $db->rollBack();
        throw $error;
    }
}

function deleteResource(string $table): never
{
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$id) {
        Http::json(['ok' => false, 'message' => 'ID tidak valid.'], 422);
    }
    $db = Database::connection();
    try {
        $db->beginTransaction();
        $oldStatement = $db->prepare("SELECT * FROM {$table} WHERE id = :id LIMIT 1 FOR UPDATE");
        $oldStatement->execute(['id' => $id]);
        $before = $oldStatement->fetch();
        if (!$before) {
            $db->rollBack();
            Http::json(['ok' => false, 'message' => 'Data tidak ditemukan.'], 404);
        }
        $stmt = $db->prepare("DELETE FROM {$table} WHERE id = :id");
        $stmt->execute(['id' => $id]);
        recordContentHistory($db, $table, (int) $id, 'deleted', $before, []);
        $db->commit();
        Http::json(['ok' => true, 'deleted' => $stmt->rowCount()]);
    } catch (Throwable $error) {
        if ($db->inTransaction()) $db->rollBack();
        throw $error;
    }
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
    $galleryInput = $body['gallery_images'] ?? [];
    if (is_string($galleryInput)) {
        $decodedGallery = json_decode($galleryInput, true);
        $galleryInput = is_array($decodedGallery) ? $decodedGallery : [];
    }
    if (!is_array($galleryInput)) {
        Http::json(['ok' => false, 'message' => 'Data slider gambar tidak valid.'], 422);
    }
    $galleryImages = [];
    foreach (array_slice($galleryInput, 0, 3) as $galleryImage) {
        $url = trim((string) $galleryImage);
        if ($url === '') continue;
        if (!filter_var($url, FILTER_VALIDATE_URL) && !str_starts_with($url, '/uploads/')) {
            Http::json(['ok' => false, 'message' => 'Salah satu gambar slider tidak valid.'], 422);
        }
        if (!in_array($url, $galleryImages, true)) $galleryImages[] = $url;
    }
    if ($image !== '' && !in_array($image, $galleryImages, true)) {
        array_unshift($galleryImages, $image);
        $galleryImages = array_slice($galleryImages, 0, 3);
    }
    if ($image === '' && $galleryImages !== []) $image = $galleryImages[0];
    $wa = preg_replace('/\D+/', '', (string) ($body['whatsapp_number'] ?? ''));
    if ($wa !== '' && (strlen($wa) < 8 || strlen($wa) > 16)) {
        Http::json(['ok' => false, 'message' => 'Nomor WhatsApp tidak valid.'], 422);
    }
    $socialImage = trim((string) ($body['social_image'] ?? ''));
    if ($socialImage !== '' && !filter_var($socialImage, FILTER_VALIDATE_URL) && !str_starts_with($socialImage, '/uploads/')) {
        Http::json(['ok' => false, 'message' => 'Alamat gambar sosial tidak valid.'], 422);
    }

    $payload = [
        'title' => $title,
        'slug' => $slug,
        'image' => $image,
        'gallery_images' => json_encode($galleryImages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'excerpt' => mb_substr(trim((string) ($body['excerpt'] ?? '')), 0, 1000),
        'content' => Sanitizer::richText((string) ($body['content'] ?? '')),
        'whatsapp_number' => $wa,
        'whatsapp_message' => mb_substr(trim((string) ($body['whatsapp_message'] ?? '')), 0, 500),
        'seo_title' => mb_substr(trim((string) ($body['seo_title'] ?? '')), 0, 70),
        'seo_description' => mb_substr(trim((string) ($body['seo_description'] ?? '')), 0, 170),
        'social_image' => $socialImage,
        'image_alt' => mb_substr(trim((string) ($body['image_alt'] ?? '')), 0, 180),
        'category' => publicationCategory($body['category'] ?? ''),
        'status' => publicationStatus($body['status'] ?? ''),
        'published_at' => publicationDate($body['status'] ?? '', $body['published_at'] ?? ''),
    ];

    if ($table === 'posts') {
        $heroImage = trim((string) ($body['hero_image'] ?? ''));
        if ($heroImage !== '' && !filter_var($heroImage, FILTER_VALIDATE_URL) && !str_starts_with($heroImage, '/uploads/')) {
            Http::json(['ok' => false, 'message' => 'Alamat background header artikel tidak valid.'], 422);
        }
        $heroImagesInput = $body['hero_images'] ?? [];
        if (is_string($heroImagesInput)) {
            $decodedHeroImages = json_decode($heroImagesInput, true);
            $heroImagesInput = is_array($decodedHeroImages) ? $decodedHeroImages : [];
        }
        if (!is_array($heroImagesInput)) {
            Http::json(['ok' => false, 'message' => 'Data slider background artikel tidak valid.'], 422);
        }
        $heroImages = [];
        foreach (array_slice($heroImagesInput, 0, 10) as $heroImageItem) {
            $url = trim((string) $heroImageItem);
            if ($url === '') continue;
            if (!filter_var($url, FILTER_VALIDATE_URL) && !str_starts_with($url, '/uploads/')) {
                Http::json(['ok' => false, 'message' => 'Salah satu background header tidak valid.'], 422);
            }
            if (!in_array($url, $heroImages, true)) $heroImages[] = $url;
        }
        if ($heroImage !== '' && !in_array($heroImage, $heroImages, true)) array_unshift($heroImages, $heroImage);
        $heroImages = array_slice($heroImages, 0, 10);
        if ($heroImage === '' && $heroImages !== []) $heroImage = $heroImages[0];
        $payload['hero_image'] = $heroImage;
        $payload['hero_images'] = json_encode($heroImages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        [$heroMediaType, $heroVideoUrl] = validateHeroVideo(
            (string) ($body['hero_media_type'] ?? 'images'),
            trim((string) ($body['hero_video_url'] ?? '')),
            'artikel'
        );
        $payload['hero_media_type'] = $heroMediaType;
        $payload['hero_video_url'] = $heroVideoUrl;
    } else {
        $payload['hero_title'] = mb_substr(trim((string) ($body['hero_title'] ?? '')), 0, 180);
        $payload['hero_subtitle'] = mb_substr(trim((string) ($body['hero_subtitle'] ?? '')), 0, 300);
        [$heroMediaType, $heroVideoUrl] = validateHeroVideo(
            (string) ($body['hero_media_type'] ?? 'images'),
            trim((string) ($body['hero_video_url'] ?? '')),
            'program'
        );
        $payload['hero_media_type'] = $heroMediaType;
        $payload['hero_video_url'] = $heroVideoUrl;
        $featuredOrder = trim((string) ($body['featured_order'] ?? ''));
        if ($featuredOrder !== '' && (!ctype_digit($featuredOrder) || (int) $featuredOrder > 9999)) {
            Http::json(['ok' => false, 'message' => 'Urutan program unggulan harus berupa angka 0 sampai 9999.'], 422);
        }
        $payload['featured_order'] = $featuredOrder === '' ? null : (int) $featuredOrder;
    }
    return $payload;
}

function publicationCategory(mixed $value): string
{
    $category = trim((string) $value);
    if ($category === '') return 'Umum';
    if (mb_strlen($category) > 100) {
        Http::json(['ok' => false, 'message' => 'Kategori maksimal 100 karakter.'], 422);
    }
    return $category;
}

function validateHeroVideo(string $type, string $url, string $contentLabel): array
{
    if (!in_array($type, ['images', 'video', 'youtube', 'drive'], true)) {
        Http::json(['ok' => false, 'message' => "Jenis media hero {$contentLabel} tidak valid."], 422);
    }
    if ($type === 'images') {
        $url = '';
    } elseif ($type === 'video') {
        if (!preg_match('#^/uploads/videos/[a-f0-9]{32}\.(?:mp4|webm)$#', $url)) {
            Http::json(['ok' => false, 'message' => "Upload video lokal terlebih dahulu sebelum menyimpan {$contentLabel}."], 422);
        }
    } elseif ($type === 'youtube' && youtubeVideoId($url) === null) {
        Http::json(['ok' => false, 'message' => 'Tautan YouTube tidak valid.'], 422);
    } elseif ($type === 'drive' && driveVideoId($url) === null) {
        Http::json(['ok' => false, 'message' => 'Tautan Google Drive tidak valid.'], 422);
    }
    return [$type, mb_substr($url, 0, 1000)];
}

function youtubeVideoId(string $url): ?string
{
    if (!filter_var($url, FILTER_VALIDATE_URL) || strtolower((string) parse_url($url, PHP_URL_SCHEME)) !== 'https') {
        return null;
    }
    $host = strtolower((string) parse_url($url, PHP_URL_HOST));
    $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
    $candidate = '';
    if (in_array($host, ['youtu.be', 'www.youtu.be'], true)) {
        $candidate = explode('/', $path)[0] ?? '';
    } elseif (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com'], true)) {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        if ($path === 'watch') {
            $candidate = (string) ($query['v'] ?? '');
        } elseif (preg_match('#^(?:embed|shorts|live)/([A-Za-z0-9_-]{11})#', $path, $match)) {
            $candidate = $match[1];
        }
    }
    return preg_match('/^[A-Za-z0-9_-]{11}$/', $candidate) ? $candidate : null;
}

function driveVideoId(string $url): ?string
{
    if (!filter_var($url, FILTER_VALIDATE_URL) || strtolower((string) parse_url($url, PHP_URL_SCHEME)) !== 'https') {
        return null;
    }
    $host = strtolower((string) parse_url($url, PHP_URL_HOST));
    if (!in_array($host, ['drive.google.com', 'www.drive.google.com'], true)) return null;
    $path = (string) parse_url($url, PHP_URL_PATH);
    $candidate = '';
    if (preg_match('#/file/d/([A-Za-z0-9_-]{10,})#', $path, $match)) {
        $candidate = $match[1];
    } else {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $candidate = (string) ($query['id'] ?? '');
    }
    return preg_match('/^[A-Za-z0-9_-]{10,}$/', $candidate) ? $candidate : null;
}

function publicationStatus(mixed $value): string
{
    $status = (string) $value;
    if (!in_array($status, ['draft', 'published'], true)) {
        Http::json(['ok' => false, 'message' => 'Status publikasi tidak valid.'], 422);
    }
    return $status;
}

function publicationDate(mixed $statusValue, mixed $dateValue): ?string
{
    $status = publicationStatus($statusValue);
    if ($status === 'draft') return null;
    $value = trim((string) $dateValue);
    if ($value === '') return gmdate('Y-m-d H:i:s');
    try {
        $local = new DateTimeImmutable($value, new DateTimeZone('Asia/Jakarta'));
        return $local->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    } catch (Throwable) {
        Http::json(['ok' => false, 'message' => 'Jadwal publikasi tidak valid.'], 422);
    }
}

function recordContentHistory(PDO $db, string $table, int $id, string $action, array $before, array $after): void
{
    $labels = ['posts' => 'artikel', 'programs' => 'program'];
    $verbs = ['created' => 'Membuat', 'updated' => 'Mengubah', 'deleted' => 'Menghapus'];
    $record = $after ?: $before;
    $summary = ($verbs[$action] ?? 'Mengubah') . ' ' . ($labels[$table] ?? 'konten') . ' “' . (string) ($record['title'] ?? ('#' . $id)) . '”';
    if ($action === 'updated' && ($before['status'] ?? null) !== ($after['status'] ?? null)) {
        $summary .= ' (status: ' . (string) ($before['status'] ?? '-') . ' → ' . (string) ($after['status'] ?? '-') . ')';
    }
    $statement = $db->prepare(
        'INSERT INTO content_history (content_type, content_id, action, admin_id, admin_email, summary)
         VALUES (:content_type, :content_id, :action, :admin_id, :admin_email, :summary)'
    );
    $statement->execute([
        'content_type' => $table,
        'content_id' => $id,
        'action' => $action,
        'admin_id' => Auth::id(),
        'admin_email' => Auth::email(),
        'summary' => mb_substr($summary, 0, 500),
    ]);
}

function institutionProfileKeys(): array
{
    return [
        'organization_name', 'parent_organization', 'legal_entity_name', 'deed_number',
        'ministry_number', 'tax_number', 'official_address', 'official_phone', 'official_email',
        'management_structure', 'donation_accounts', 'collection_reports',
        'beneficiary_documentation', 'official_disclaimer', 'privacy_contact',
    ];
}

function saveInstitutionProfile(array $body): never
{
    $keys = institutionProfileKeys();
    $longFields = ['management_structure', 'donation_accounts', 'collection_reports', 'beneficiary_documentation', 'official_disclaimer'];
    $values = [];
    foreach ($keys as $key) {
        $value = trim((string) ($body[$key] ?? ''));
        $limit = in_array($key, $longFields, true) ? 10000 : 1000;
        if (mb_strlen($value) > $limit) {
            Http::json(['ok' => false, 'message' => "Kolom {$key} terlalu panjang."], 422);
        }
        if (in_array($key, ['official_email', 'privacy_contact'], true) && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            Http::json(['ok' => false, 'message' => 'Alamat email lembaga tidak valid.'], 422);
        }
        $values[$key] = $value;
    }

    $db = Database::connection();
    try {
        $db->beginTransaction();
        $statement = $db->prepare(
            'INSERT INTO institution_profile (profile_key, profile_value, updated_by)
             VALUES (:profile_key, :profile_value, :updated_by)
             ON DUPLICATE KEY UPDATE profile_value = VALUES(profile_value), updated_by = VALUES(updated_by)'
        );
        foreach ($values as $key => $value) {
            $statement->execute(['profile_key' => $key, 'profile_value' => $value, 'updated_by' => Auth::id()]);
        }
        $db->commit();
        Http::json(['ok' => true, 'message' => 'Profil kredibilitas berhasil disimpan.']);
    } catch (Throwable $error) {
        if ($db->inTransaction()) $db->rollBack();
        throw $error;
    }
}

function handleUpload(): never
{
    if (!extension_loaded('gd') || !function_exists('imagewebp')) {
        Http::json(['ok' => false, 'message' => 'Pemrosesan gambar WebP belum aktif di server. Hubungi administrator hosting.'], 503);
    }
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
    $targetDir = dirname(__DIR__) . '/uploads';
    $keepOriginal = filter_var(Config::get('KEEP_UPLOAD_ORIGINAL', 'false'), FILTER_VALIDATE_BOOLEAN);
    try {
        $result = ImageProcessor::process($file['tmp_name'], $mime, $targetDir, $keepOriginal);
    } catch (InvalidArgumentException $error) {
        Http::json(['ok' => false, 'message' => $error->getMessage()], 422);
    }
    Http::json(['ok' => true] + $result, 201);
}

function handleVideoUpload(): never
{
    if (!isset($_FILES['video'])) {
        Http::json(['ok' => false, 'message' => 'File video tidak ditemukan.'], 422);
    }
    $file = $_FILES['video'];
    $uploadError = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($uploadError !== UPLOAD_ERR_OK) {
        $message = in_array($uploadError, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
            ? 'Ukuran video melewati batas upload server Hostinger.'
            : 'Upload video gagal. Silakan coba lagi.';
        Http::json(['ok' => false, 'message' => $message], 422);
    }
    if (!is_uploaded_file((string) $file['tmp_name'])) {
        Http::json(['ok' => false, 'message' => 'Upload video tidak sah.'], 422);
    }
    $size = (int) ($file['size'] ?? 0);
    if ($size < 1 || $size > 50 * 1024 * 1024) {
        Http::json(['ok' => false, 'message' => 'Ukuran video maksimal 50 MB.'], 422);
    }

    $format = detectUploadedVideoFormat((string) $file['tmp_name'], (string) ($file['name'] ?? ''));
    if ($format === null) {
        Http::json(['ok' => false, 'message' => 'Hanya video MP4 atau WebM yang valid yang diperbolehkan.'], 422);
    }

    $targetDirectory = dirname(__DIR__) . '/uploads/videos';
    if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0755, true) && !is_dir($targetDirectory)) {
        Http::json(['ok' => false, 'message' => 'Folder penyimpanan video tidak dapat dibuat.'], 500);
    }
    $filename = bin2hex(random_bytes(16)) . '.' . $format['extension'];
    $destination = $targetDirectory . '/' . $filename;
    if (!move_uploaded_file((string) $file['tmp_name'], $destination)) {
        Http::json(['ok' => false, 'message' => 'Video gagal disimpan di server.'], 500);
    }
    @chmod($destination, 0644);
    Http::json([
        'ok' => true,
        'url' => '/uploads/videos/' . $filename,
        'mime' => $format['mime'],
        'size' => $size,
    ], 201);
}

function detectUploadedVideoFormat(string $path, string $originalName): ?array
{
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path) ?: '';
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if ($extension === 'mp4' && in_array($mime, ['video/mp4', 'application/mp4'], true)) {
        return ['extension' => 'mp4', 'mime' => 'video/mp4'];
    }
    if ($extension === 'webm' && $mime === 'video/webm') {
        return ['extension' => 'webm', 'mime' => 'video/webm'];
    }

    $handle = @fopen($path, 'rb');
    if ($handle === false) return null;
    $header = fread($handle, 12);
    fclose($handle);
    if ($extension === 'mp4' && strlen($header) >= 8 && substr($header, 4, 4) === 'ftyp') {
        return ['extension' => 'mp4', 'mime' => 'video/mp4'];
    }
    if ($extension === 'webm' && str_starts_with(bin2hex($header), '1a45dfa3')) {
        return ['extension' => 'webm', 'mime' => 'video/webm'];
    }
    return null;
}

function serveOwnProfile(): never
{
    $profile = findAdminProfile(Auth::id());
    if (!$profile) {
        Auth::logout();
        Http::json(['ok' => false, 'message' => 'Akun admin tidak ditemukan. Silakan masuk kembali.'], 401);
    }

    Http::json([
        'ok' => true,
        'data' => publicAdminProfile($profile),
        'security' => accountSecuritySummary((int) $profile['id'], (string) $profile['email']),
    ]);
}

function updateOwnAccount(array $body): never
{
    $action = (string) ($body['action'] ?? '');
    if (!in_array($action, ['update_profile', 'change_password'], true)) {
        Http::json(['ok' => false, 'message' => 'Tindakan akun tidak valid.'], 422);
    }

    $profile = findAdminProfile(Auth::id(), true);
    if (!$profile) {
        Http::json(['ok' => false, 'message' => 'Akun admin tidak ditemukan.'], 404);
    }

    $currentPassword = (string) ($body['current_password'] ?? '');
    if ($currentPassword === '' || !password_verify($currentPassword, (string) $profile['password_hash'])) {
        Http::json(['ok' => false, 'message' => 'Kata sandi saat ini tidak benar.'], 422);
    }

    if ($action === 'update_profile') {
        updateOwnProfile($profile, $body);
    }
    changeOwnPassword($profile, $body);
}

function updateOwnProfile(array $profile, array $body): never
{
    $name = trim((string) ($body['display_name'] ?? ''));
    $email = strtolower(trim((string) ($body['email'] ?? '')));
    if (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
        Http::json(['ok' => false, 'message' => 'Nama harus berisi 2–120 karakter.'], 422);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
        Http::json(['ok' => false, 'message' => 'Email tidak valid.'], 422);
    }

    $duplicate = Database::connection()->prepare(
        'SELECT id FROM admins WHERE email = :email AND id <> :id LIMIT 1'
    );
    $duplicate->execute(['email' => $email, 'id' => (int) $profile['id']]);
    if ($duplicate->fetch()) {
        Http::json(['ok' => false, 'message' => 'Email tersebut sudah digunakan admin lain.'], 409);
    }

    try {
        $statement = Database::connection()->prepare(
            'UPDATE admins
             SET display_name = :display_name, email = :email, session_version = session_version + 1
             WHERE id = :id'
        );
        $statement->execute([
            'display_name' => $name,
            'email' => $email,
            'id' => (int) $profile['id'],
        ]);
    } catch (Throwable $error) {
        accountMigrationError($error);
    }

    $updated = findAdminProfile((int) $profile['id']);
    Auth::refreshSession(
        (int) $updated['id'],
        (string) $updated['email'],
        (string) $updated['role'],
        (int) $updated['session_version']
    );
    Auth::recordSecurityEvent('profile_updated', (string) $updated['email'], (int) $updated['id']);
    Http::json([
        'ok' => true,
        'message' => 'Nama dan email berhasil diperbarui.',
        'csrf' => Auth::csrf(),
        'data' => publicAdminProfile($updated),
    ]);
}

function changeOwnPassword(array $profile, array $body): never
{
    $password = (string) ($body['new_password'] ?? '');
    $confirmation = (string) ($body['new_password_confirmation'] ?? '');
    validateNewPassword($password, $confirmation);
    if (password_verify($password, (string) $profile['password_hash'])) {
        Http::json(['ok' => false, 'message' => 'Kata sandi baru harus berbeda dari kata sandi saat ini.'], 422);
    }

    try {
        $statement = Database::connection()->prepare(
            'UPDATE admins
             SET password_hash = :password_hash, session_version = session_version + 1
             WHERE id = :id'
        );
        $statement->execute([
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'id' => (int) $profile['id'],
        ]);
    } catch (Throwable $error) {
        accountMigrationError($error);
    }

    $updated = findAdminProfile((int) $profile['id']);
    session_regenerate_id(true);
    Auth::refreshSession(
        (int) $updated['id'],
        (string) $updated['email'],
        (string) $updated['role'],
        (int) $updated['session_version']
    );
    Auth::recordSecurityEvent('password_changed', (string) $updated['email'], (int) $updated['id']);
    Http::json([
        'ok' => true,
        'message' => 'Kata sandi berhasil diganti. Sesi akun di perangkat lain telah dihentikan.',
        'csrf' => Auth::csrf(),
    ]);
}

function resetAdminPassword(array $body): never
{
    $adminId = filter_var($body['admin_id'] ?? null, FILTER_VALIDATE_INT);
    if (!$adminId) {
        Http::json(['ok' => false, 'message' => 'Admin tujuan tidak valid.'], 422);
    }
    if ((int) $adminId === Auth::id()) {
        Http::json(['ok' => false, 'message' => 'Gunakan menu Profil Saya untuk mengganti kata sandi sendiri.'], 422);
    }

    $password = (string) ($body['new_password'] ?? '');
    $confirmation = (string) ($body['new_password_confirmation'] ?? '');
    validateNewPassword($password, $confirmation);
    $target = findAdminProfile((int) $adminId);
    if (!$target) {
        Http::json(['ok' => false, 'message' => 'Akun admin tidak ditemukan.'], 404);
    }

    try {
        $statement = Database::connection()->prepare(
            'UPDATE admins
             SET password_hash = :password_hash, session_version = session_version + 1
             WHERE id = :id'
        );
        $statement->execute([
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'id' => (int) $adminId,
        ]);
    } catch (Throwable $error) {
        accountMigrationError($error);
    }

    Auth::recordSecurityEvent('password_reset', (string) $target['email'], (int) $target['id']);
    Http::json([
        'ok' => true,
        'message' => 'Kata sandi admin berhasil direset. Semua sesi lama akun tersebut telah dihentikan.',
    ]);
}

function findAdminProfile(int $id, bool $withPassword = false): array|false
{
    $passwordColumn = $withPassword ? ', password_hash' : '';
    try {
        $statement = Database::connection()->prepare(
            "SELECT id, email, display_name, role, is_active, session_version,
                    last_login_at, created_at, updated_at{$passwordColumn}
             FROM admins WHERE id = :id LIMIT 1"
        );
        $statement->execute(['id' => $id]);
        return $statement->fetch();
    } catch (Throwable $error) {
        if ($withPassword) {
            accountMigrationError($error);
        }
        $statement = Database::connection()->prepare(
            'SELECT id, email, display_name, role, is_active, 1 AS session_version,
                    last_login_at, created_at, updated_at
             FROM admins WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        return $statement->fetch();
    }
}

function publicAdminProfile(array $profile): array
{
    return [
        'id' => (int) $profile['id'],
        'email' => (string) $profile['email'],
        'display_name' => (string) $profile['display_name'],
        'role' => (string) $profile['role'],
        'is_active' => (int) $profile['is_active'],
        'last_login_at' => $profile['last_login_at'],
        'created_at' => $profile['created_at'],
        'updated_at' => $profile['updated_at'],
    ];
}

function accountSecuritySummary(int $adminId, string $email): array
{
    try {
        $statement = Database::connection()->prepare(
            "SELECT
                SUM(CASE WHEN event_type = 'failure' THEN 1 ELSE 0 END) AS failed_attempts,
                SUM(CASE WHEN event_type = 'blocked' THEN 1 ELSE 0 END) AS blocked_attempts,
                MAX(CASE WHEN event_type IN ('failure', 'blocked') THEN created_at END) AS last_suspicious_at
             FROM login_security_events
             WHERE (admin_id = :admin_id OR (admin_id IS NULL AND email = :email))
               AND created_at >= UTC_TIMESTAMP() - INTERVAL 24 HOUR"
        );
        $statement->execute(['admin_id' => $adminId, 'email' => strtolower($email)]);
        $row = $statement->fetch() ?: [];
        $failures = (int) ($row['failed_attempts'] ?? 0);
        $blocked = (int) ($row['blocked_attempts'] ?? 0);
        return [
            'enabled' => true,
            'warning' => $failures >= 3 || $blocked > 0,
            'failed_attempts_24h' => $failures,
            'blocked_attempts_24h' => $blocked,
            'last_suspicious_at' => $row['last_suspicious_at'] ?? null,
        ];
    } catch (Throwable) {
        return [
            'enabled' => false,
            'warning' => false,
            'failed_attempts_24h' => 0,
            'blocked_attempts_24h' => 0,
            'last_suspicious_at' => null,
        ];
    }
}

function validateNewPassword(string $password, string $confirmation): void
{
    if (strlen($password) < 15 || strlen($password) > 128) {
        Http::json(['ok' => false, 'message' => 'Kata sandi baru harus berisi 15–128 karakter.'], 422);
    }
    if (!hash_equals($password, $confirmation)) {
        Http::json(['ok' => false, 'message' => 'Konfirmasi kata sandi baru tidak sama.'], 422);
    }
}

function accountMigrationError(Throwable $error): never
{
    error_log('[AdminAccount] ' . $error->getMessage());
    Http::json([
        'ok' => false,
        'message' => 'Fitur keamanan akun belum diaktifkan. Jalankan database/add_admin_security.sql melalui phpMyAdmin.',
    ], 503);
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
    if (strlen($password) < 15 || strlen($password) > 128) {
        Http::json(['ok' => false, 'message' => 'Password admin harus berisi 15–128 karakter.'], 422);
    }
    if (!in_array($role, ['super_admin', 'admin'], true)) {
        Http::json(['ok' => false, 'message' => 'Role admin tidak valid.'], 422);
    }

    $existing = Database::connection()->prepare('SELECT id FROM admins WHERE email = :email LIMIT 1');
    $existing->execute(['email' => $email]);
    if ($existing->fetch()) {
        Http::json([
            'ok' => false,
            'message' => 'Email sudah terdaftar. Gunakan tombol Reset Password pada daftar admin.',
        ], 409);
    }

    $stmt = Database::connection()->prepare(
        'INSERT INTO admins (email, password_hash, display_name, role, is_active)
         VALUES (:email, :password_hash, :display_name, :role, 1)'
    );
    $stmt->execute([
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'display_name' => $name,
        'role' => $role,
    ]);
    Http::json(['ok' => true, 'message' => 'Admin berhasil ditambahkan.'], 201);
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

function serveGoldPrice(): never
{
    $cacheDir = dirname(__DIR__, 2) . '/backend/storage/cache';
    $cacheFile = $cacheDir . '/gold-price.json';
    $cached = readGoldPriceCache($cacheFile);

    if ($cached !== null && (time() - (int) ($cached['fetched_at_unix'] ?? 0)) < 3600) {
        $cached['cached'] = true;
        Http::json(['ok' => true, 'data' => $cached]);
    }

    $gold = fetchRemoteJson('https://api.gold-api.com/price/XAU');
    $exchange = fetchRemoteJson('https://api.frankfurter.dev/v2/rate/USD/IDR');
    $goldUsdPerOunce = (float) ($gold['price'] ?? 0);
    $usdIdr = (float) ($exchange['rate'] ?? 0);

    if ($goldUsdPerOunce >= 500 && $goldUsdPerOunce <= 10000 && $usdIdr >= 5000 && $usdIdr <= 50000) {
        $pricePerGram = (int) round(($goldUsdPerOunce * $usdIdr) / 31.1034768, -3);
        $payload = [
            'price_per_gram' => $pricePerGram,
            'nishab_yearly' => $pricePerGram * 85,
            'nishab_monthly' => (int) round(($pricePerGram * 85) / 12),
            'gold_usd_per_ounce' => round($goldUsdPerOunce, 2),
            'usd_idr' => round($usdIdr, 4),
            'method' => 'spot_24k',
            'source' => 'Harga spot emas 24K dan kurs USD/IDR',
            'updated_at' => (string) ($gold['updatedAt'] ?? $exchange['date'] ?? date(DATE_ATOM)),
            'fetched_at' => date(DATE_ATOM),
            'fetched_at_unix' => time(),
            'cached' => false,
            'is_stale' => false,
            'is_fallback' => false,
        ];
        writeGoldPriceCache($cacheDir, $cacheFile, $payload);
        Http::json(['ok' => true, 'data' => $payload]);
    }

    if ($cached !== null && (time() - (int) ($cached['fetched_at_unix'] ?? 0)) < 604800) {
        $cached['cached'] = true;
        $cached['is_stale'] = true;
        Http::json(['ok' => true, 'data' => $cached]);
    }

    // Fallback resmi ketika sumber harga harian belum dapat diakses.
    $baznasNishabYearly = 91681728;
    Http::json([
        'ok' => true,
        'data' => [
            'price_per_gram' => (int) round($baznasNishabYearly / 85),
            'nishab_yearly' => $baznasNishabYearly,
            'nishab_monthly' => 7640144,
            'method' => 'baznas_2026',
            'source' => 'Acuan nisab zakat pendapatan BAZNAS RI 2026',
            'updated_at' => '2026-02-20T00:00:00+07:00',
            'fetched_at' => date(DATE_ATOM),
            'fetched_at_unix' => time(),
            'cached' => false,
            'is_stale' => false,
            'is_fallback' => true,
        ],
    ]);
}

function readGoldPriceCache(string $path): ?array
{
    if (!is_file($path) || filesize($path) > 16384) {
        return null;
    }
    $raw = file_get_contents($path);
    if ($raw === false) {
        return null;
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
}

function writeGoldPriceCache(string $directory, string $path, array $data): void
{
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        return;
    }
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        return;
    }
    $temporary = $path . '.' . bin2hex(random_bytes(4)) . '.tmp';
    if (file_put_contents($temporary, $json, LOCK_EX) !== false) {
        @rename($temporary, $path);
    }
    if (is_file($temporary)) {
        @unlink($temporary);
    }
}

function fetchRemoteJson(string $url): ?array
{
    $body = false;
    if (function_exists('curl_init')) {
        $curl = curl_init($url);
        if ($curl === false) {
            return null;
        }
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_TIMEOUT => 7,
            CURLOPT_USERAGENT => 'DompetDanaUmat/1.0',
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $body = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);
        if ($status !== 200) {
            return null;
        }
    } elseif (filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN)) {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 7,
                'ignore_errors' => false,
                'header' => "Accept: application/json\r\nUser-Agent: DompetDanaUmat/1.0\r\n",
            ],
        ]);
        $body = @file_get_contents($url, false, $context);
    }

    if (!is_string($body) || $body === '' || strlen($body) > 65536) {
        return null;
    }
    $data = json_decode($body, true);
    return is_array($data) ? $data : null;
}
