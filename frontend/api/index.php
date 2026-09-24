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

if ($resource === 'qr_upload' && $method === 'POST') {
    Auth::requireAdmin();
    Auth::verifyCsrf();
    handleQrUpload();
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
        if (!in_array((string) ($body['type'] ?? ''), [
            'visit', 'page_view', 'content_view', 'engaged_view', 'page_engagement', 'wa_click',
            'hero_cta_click', 'calculator_submit', 'contact_submit', 'web_vital', 'client_error',
        ], true)) {
            Http::json(['ok' => false, 'message' => 'Tipe statistik tidak valid.'], 422);
        }
        Analytics::record($body);
        Http::json(['ok' => true], 201);
    }
}

if ($resource === 'analytics' && $method === 'GET') {
    Auth::requireAdmin();
    $days = filter_var($_GET['days'] ?? 30, FILTER_VALIDATE_INT) ?: 30;
    Http::json(['ok' => true, 'data' => Analytics::report((int) $days)]);
}

if ($resource === 'system_health' && $method === 'GET') {
    Auth::requireAdmin();
    Http::json(['ok' => true, 'data' => SystemHealth::report()]);
}

if ($resource === 'admin_sessions') {
    Auth::requireAdmin();
    if ($method === 'GET') {
        $adminId = filter_var($_GET['admin_id'] ?? null, FILTER_VALIDATE_INT);
        if ($adminId && (int) $adminId !== Auth::id() && Auth::role() !== 'super_admin') {
            Http::json(['ok' => false, 'message' => 'Akses sesi admin ditolak.'], 403);
        }
        Http::json([
            'ok' => true,
            'data' => Auth::sessions($adminId ? (int) $adminId : null),
            'migration_required' => !adminSessionsTableAvailable(),
        ]);
    }
    if ($method === 'DELETE') {
        Auth::verifyCsrf();
        $sessionId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$sessionId || !Auth::revokeSession((int) $sessionId)) {
            Http::json(['ok' => false, 'message' => 'Sesi tidak ditemukan, sedang digunakan, atau tidak dapat dihentikan.'], 422);
        }
        Http::json(['ok' => true, 'message' => 'Sesi perangkat berhasil dihentikan.']);
    }
    Http::json(['ok' => false, 'message' => 'Metode tidak diizinkan.'], 405);
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

if ($resource === 'homepage') {
    if ($method === 'GET') {
        serveHomepageSettings();
    }
    if ($method === 'POST') {
        Auth::requireAdmin();
        Auth::verifyCsrf();
        saveHomepageSettings(Http::body());
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
        if ($row && $table === 'programs') $row = withProgramSections($row);
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
        if ($row && $table === 'programs') $row = withProgramSections($row);
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

function programSectionsAvailable(): bool
{
    static $available = null;
    if ($available !== null) return $available;
    try {
        $statement = Database::connection()->query("SHOW TABLES LIKE 'program_sections'");
        $available = (bool) $statement->fetchColumn();
    } catch (Throwable) {
        $available = false;
    }
    return $available;
}

function withProgramSections(array $program): array
{
    $program['sections'] = [];
    $program['sections_migration_required'] = !programSectionsAvailable();
    if ($program['sections_migration_required']) return $program;

    $statement = Database::connection()->prepare(
        'SELECT section_key, section_type, sort_order, is_visible, section_data
         FROM program_sections WHERE program_id = :program_id
         ORDER BY sort_order ASC, id ASC'
    );
    $statement->execute(['program_id' => (int) $program['id']]);
    foreach ($statement->fetchAll() as $row) {
        $data = json_decode((string) $row['section_data'], true);
        $program['sections'][] = [
            'key' => (string) $row['section_key'],
            'type' => (string) $row['section_type'],
            'visible' => (bool) $row['is_visible'],
            'data' => is_array($data) ? $data : [],
        ];
    }
    return $program;
}

function replaceProgramSections(PDO $db, int $programId, array $sections): void
{
    if (!programSectionsAvailable()) {
        Http::json([
            'ok' => false,
            'message' => 'Section Builder belum diaktifkan. Jalankan database/add_program_section_builder.sql melalui phpMyAdmin.',
        ], 409);
    }
    $delete = $db->prepare('DELETE FROM program_sections WHERE program_id = :program_id');
    $delete->execute(['program_id' => $programId]);
    if ($sections === []) return;

    $insert = $db->prepare(
        'INSERT INTO program_sections
         (program_id, section_key, section_type, sort_order, is_visible, section_data)
         VALUES (:program_id, :section_key, :section_type, :sort_order, :is_visible, :section_data)'
    );
    foreach ($sections as $index => $section) {
        $insert->execute([
            'program_id' => $programId,
            'section_key' => $section['key'],
            'section_type' => $section['type'],
            'sort_order' => $index,
            'is_visible' => $section['visible'] ? 1 : 0,
            'section_data' => json_encode($section['data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }
}

function validateProgramSections(mixed $input): array
{
    if (is_string($input)) $input = json_decode($input, true);
    if (!is_array($input)) {
        Http::json(['ok' => false, 'message' => 'Susunan section program tidak valid.'], 422);
    }
    if (count($input) > 50) {
        Http::json(['ok' => false, 'message' => 'Maksimal 50 section dalam satu program.'], 422);
    }

    $allowedTypes = ['hero', 'content', 'progress', 'gallery', 'impact', 'cta', 'faq'];
    $sections = [];
    $keys = [];
    foreach ($input as $index => $rawSection) {
        if (!is_array($rawSection)) continue;
        $type = strtolower(trim((string) ($rawSection['type'] ?? '')));
        if (!in_array($type, $allowedTypes, true)) {
            Http::json(['ok' => false, 'message' => 'Jenis section program tidak dikenali.'], 422);
        }
        $key = strtolower(trim((string) ($rawSection['key'] ?? '')));
        if (!preg_match('/^[a-z0-9][a-z0-9_-]{5,63}$/', $key) || isset($keys[$key])) {
            $key = 'section-' . ($index + 1) . '-' . substr(hash('sha256', $type . '-' . $index), 0, 10);
        }
        $keys[$key] = true;
        $rawData = is_array($rawSection['data'] ?? null) ? $rawSection['data'] : [];
        $sections[] = [
            'key' => $key,
            'type' => $type,
            'visible' => filter_var($rawSection['visible'] ?? true, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? true,
            'data' => validateProgramSectionData($type, $rawData),
        ];
    }
    return $sections;
}

function validateProgramSectionData(string $type, array $data): array
{
    $result = [
        'eyebrow' => sectionText($data['eyebrow'] ?? '', 80),
        'title' => sectionText($data['title'] ?? '', 220),
        'subtitle' => sectionText($data['subtitle'] ?? '', 500),
        'body' => sectionText($data['body'] ?? '', 12000, true),
        'theme' => sectionChoice($data['theme'] ?? 'light', ['light', 'pale', 'blue', 'deep', 'warm'], 'light'),
        'width' => sectionChoice($data['width'] ?? 'boxed', ['narrow', 'boxed', 'full'], 'boxed'),
        'alignment' => sectionChoice($data['alignment'] ?? 'left', ['left', 'center', 'right'], 'left'),
        'spacing' => sectionChoice($data['spacing'] ?? 'normal', ['compact', 'normal', 'spacious'], 'normal'),
    ];

    if ($type === 'hero') {
        $result += [
            'media_type' => sectionChoice($data['media_type'] ?? 'image', ['image', 'video', 'youtube', 'drive'], 'image'),
            'media_url' => sectionMediaUrl($data['media_url'] ?? ''),
            'mobile_media_url' => sectionMediaUrl($data['mobile_media_url'] ?? ''),
            'poster_url' => sectionMediaUrl($data['poster_url'] ?? ''),
            'media_alt' => sectionText($data['media_alt'] ?? '', 180),
            'overlay' => max(0, min(80, (int) ($data['overlay'] ?? 25))),
            'height' => sectionChoice($data['height'] ?? 'screen', ['compact', 'medium', 'screen'], 'screen'),
            'button_label' => sectionText($data['button_label'] ?? '', 80),
            'button_url' => sectionLink($data['button_url'] ?? ''),
            'whole_link' => sectionLink($data['whole_link'] ?? ''),
        ];
    } elseif ($type === 'content') {
        $result += [
            'media_type' => sectionChoice($data['media_type'] ?? 'none', ['none', 'image', 'video', 'youtube', 'drive'], 'none'),
            'media_url' => sectionMediaUrl($data['media_url'] ?? ''),
            'media_alt' => sectionText($data['media_alt'] ?? '', 180),
            'caption' => sectionText($data['caption'] ?? '', 300),
            'media_position' => sectionChoice($data['media_position'] ?? 'top', ['top', 'bottom', 'left', 'right', 'background'], 'top'),
            'media_ratio' => sectionChoice($data['media_ratio'] ?? 'landscape', ['natural', 'landscape', 'square', 'portrait'], 'landscape'),
            'media_link' => sectionLink($data['media_link'] ?? ''),
        ];
    } elseif ($type === 'progress') {
        $target = sectionMoney($data['target'] ?? 0);
        $collected = sectionMoney($data['collected'] ?? 0);
        $result += [
            'target' => $target,
            'collected' => $collected,
            'donors' => max(0, min(100000000, (int) ($data['donors'] ?? 0))),
            'deadline' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($data['deadline'] ?? '')) ? (string) $data['deadline'] : '',
            'show_amounts' => sectionBool($data['show_amounts'] ?? true),
            'show_percentage' => sectionBool($data['show_percentage'] ?? true),
            'button_label' => sectionText($data['button_label'] ?? '', 80),
            'button_url' => sectionLink($data['button_url'] ?? ''),
        ];
    } elseif ($type === 'gallery') {
        $result['layout'] = sectionChoice($data['layout'] ?? 'grid-2', ['single', 'grid-2', 'grid-3', 'featured', 'mosaic', 'carousel'], 'grid-2');
        $result['items'] = validateProgramSectionItems($data['items'] ?? [], 'gallery');
    } elseif ($type === 'impact') {
        $result['columns'] = max(2, min(4, (int) ($data['columns'] ?? 3)));
        $result['items'] = validateProgramSectionItems($data['items'] ?? [], 'impact');
    } elseif ($type === 'cta') {
        $wa = preg_replace('/\D+/', '', (string) ($data['whatsapp_number'] ?? ''));
        if ($wa !== '' && (strlen($wa) < 8 || strlen($wa) > 16)) {
            Http::json(['ok' => false, 'message' => 'Nomor WhatsApp pada section CTA tidak valid.'], 422);
        }
        $result += [
            'whatsapp_number' => $wa,
            'whatsapp_message' => sectionText($data['whatsapp_message'] ?? '', 500, true),
            'qr_image' => sectionMediaUrl($data['qr_image'] ?? ''),
            'button_label' => sectionText($data['button_label'] ?? '', 80),
            'button_url' => sectionLink($data['button_url'] ?? ''),
            'show_qr' => sectionBool($data['show_qr'] ?? true),
            'show_whatsapp' => sectionBool($data['show_whatsapp'] ?? true),
        ];
    } elseif ($type === 'faq') {
        $result['items'] = validateProgramSectionItems($data['items'] ?? [], 'faq');
    }
    return $result;
}

function validateProgramSectionItems(mixed $items, string $type): array
{
    if (!is_array($items)) return [];
    $limit = $type === 'gallery' ? 24 : 20;
    $result = [];
    foreach (array_slice($items, 0, $limit) as $item) {
        if (!is_array($item)) continue;
        if ($type === 'gallery') {
            $url = sectionMediaUrl($item['url'] ?? '');
            if ($url === '') continue;
            $result[] = [
                'type' => sectionChoice($item['type'] ?? 'image', ['image', 'video', 'youtube', 'drive'], 'image'),
                'url' => $url,
                'alt' => sectionText($item['alt'] ?? '', 180),
                'caption' => sectionText($item['caption'] ?? '', 300),
                'link' => sectionLink($item['link'] ?? ''),
            ];
        } elseif ($type === 'impact') {
            $value = sectionText($item['value'] ?? '', 80);
            $label = sectionText($item['label'] ?? '', 180);
            if ($value === '' && $label === '') continue;
            $result[] = ['value' => $value, 'label' => $label, 'note' => sectionText($item['note'] ?? '', 300)];
        } else {
            $question = sectionText($item['question'] ?? '', 300);
            $answer = sectionText($item['answer'] ?? '', 3000, true);
            if ($question === '' && $answer === '') continue;
            $result[] = ['question' => $question, 'answer' => $answer];
        }
    }
    return $result;
}

function sectionText(mixed $value, int $limit, bool $multiline = false): string
{
    $text = strip_tags((string) $value);
    $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text) ?? '';
    $text = $multiline ? trim($text) : trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    return mb_substr($text, 0, $limit);
}

function sectionChoice(mixed $value, array $allowed, string $fallback): string
{
    $choice = strtolower(trim((string) $value));
    return in_array($choice, $allowed, true) ? $choice : $fallback;
}

function sectionBool(mixed $value): bool
{
    return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? false;
}

function sectionMoney(mixed $value): float
{
    if (!is_numeric($value)) return 0;
    return max(0, min(999999999999999.99, round((float) $value, 2)));
}

function sectionMediaUrl(mixed $value): string
{
    $url = mb_substr(trim((string) $value), 0, 1000);
    if ($url === '') return '';
    if (str_starts_with($url, '/uploads/') || (str_starts_with(strtolower($url), 'https://') && filter_var($url, FILTER_VALIDATE_URL))) return $url;
    Http::json(['ok' => false, 'message' => 'Salah satu media section menggunakan alamat yang tidak valid.'], 422);
}

function sectionLink(mixed $value): string
{
    $url = mb_substr(trim((string) $value), 0, 1000);
    if ($url === '') return '';
    if (preg_match('~^(https://|/|#)~i', $url)) return $url;
    Http::json(['ok' => false, 'message' => 'Salah satu tautan section tidak valid.'], 422);
}

function writeResource(string $table, array $body): never
{
    $id = isset($body['id']) && $body['id'] !== '' ? filter_var($body['id'], FILTER_VALIDATE_INT) : null;
    $fields = validatePayload($table, $body);
    $sections = $table === 'programs' && array_key_exists('sections', $body)
        ? validateProgramSections($body['sections'])
        : null;
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
            if ($table === 'programs' && $sections !== null) replaceProgramSections($db, (int) $id, $sections);
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
        if ($table === 'programs' && $sections !== null) replaceProgramSections($db, $newId, $sections);
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
    $donationQrImage = trim((string) ($body['donation_qr_image'] ?? ''));
    if ($donationQrImage !== '' && !preg_match('#^/uploads/qrcodes/[a-f0-9]{32}\.png$#', $donationQrImage)) {
        Http::json(['ok' => false, 'message' => 'Gambar QR/barcode donasi tidak valid. Upload ulang melalui panel admin.'], 422);
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
        'donation_qr_image' => $donationQrImage,
        'seo_title' => mb_substr(trim((string) ($body['seo_title'] ?? '')), 0, 70),
        'seo_description' => mb_substr(trim((string) ($body['seo_description'] ?? '')), 0, 170),
        'social_image' => $socialImage,
        'image_alt' => mb_substr(trim((string) ($body['image_alt'] ?? '')), 0, 180),
        'category' => publicationCategory($body['category'] ?? ''),
        'status' => publicationStatus($body['status'] ?? ''),
        'published_at' => publicationDate($body['status'] ?? '', $body['published_at'] ?? ''),
    ];

    if ($table === 'posts') {
        $payload['author_name'] = mb_substr(trim((string) ($body['author_name'] ?? '')), 0, 120);
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

function homepageSettingsDefaults(): array
{
    return [
        'kicker' => 'Profil',
        'title' => 'Dompet Dana Umat Daarul Uluum',
        'description' => 'Menjadi lembaga amil zakat yang amanah, profesional, dan terpercaya dalam mengelola dana umat untuk mewujudkan kesejahteraan masyarakat.',
        'button_label' => 'Selengkapnya →',
        'button_url' => 'about.html',
        'show_button' => true,
        'desktop_images' => [
            'https://lh3.googleusercontent.com/d/1kuC0kI5fPd_FA0emvuSlRcFSpXQb0KGE',
            'https://lh3.googleusercontent.com//d/1YgCHGRGZVYz-gpj4umxp4sxx7jIPPMR_',
            'https://lh3.googleusercontent.com/d/1ZEtIlPw4eOKxu5izFi197otsnkPHrdRf',
        ],
        'desktop_links' => ['about.html', 'about.html', 'about.html'],
        'desktop_button_labels' => ['Selengkapnya →', 'Selengkapnya →', 'Selengkapnya →'],
        'desktop_show_buttons' => [true, true, true],
        'mobile_images' => [],
        'mobile_links' => [],
        'mobile_button_labels' => [],
        'mobile_show_buttons' => [],
    ];
}

function homepageProfileKeys(): array
{
    return [
        'kicker' => 'homepage_hero_kicker',
        'title' => 'homepage_hero_title',
        'description' => 'homepage_hero_description',
        'button_label' => 'homepage_hero_button_label',
        'button_url' => 'homepage_hero_button_url',
        'show_button' => 'homepage_hero_show_button',
        'desktop_images' => 'homepage_hero_desktop_images',
        'desktop_links' => 'homepage_hero_desktop_links',
        'desktop_button_labels' => 'homepage_hero_desktop_button_labels',
        'desktop_show_buttons' => 'homepage_hero_desktop_show_buttons',
        'mobile_images' => 'homepage_hero_mobile_images',
        'mobile_links' => 'homepage_hero_mobile_links',
        'mobile_button_labels' => 'homepage_hero_mobile_button_labels',
        'mobile_show_buttons' => 'homepage_hero_mobile_show_buttons',
    ];
}

function serveHomepageSettings(): never
{
    $settings = homepageSettingsDefaults();
    $keys = homepageProfileKeys();
    $rows = Database::connection()->query(
        "SELECT profile_key, profile_value, updated_at
         FROM institution_profile
         WHERE profile_key LIKE 'homepage_hero_%'"
    )->fetchAll();
    $stored = [];
    $updatedAt = null;
    foreach ($rows as $row) {
        $stored[(string) $row['profile_key']] = (string) $row['profile_value'];
        if ($updatedAt === null || (string) $row['updated_at'] > $updatedAt) {
            $updatedAt = (string) $row['updated_at'];
        }
    }
    foreach ($keys as $name => $profileKey) {
        if (!array_key_exists($profileKey, $stored)) continue;
        if (in_array($name, [
            'desktop_images',
            'desktop_links',
            'desktop_button_labels',
            'desktop_show_buttons',
            'mobile_images',
            'mobile_links',
            'mobile_button_labels',
            'mobile_show_buttons',
        ], true)) {
            $decoded = json_decode($stored[$profileKey], true);
            if (is_array($decoded)) $settings[$name] = array_values(array_slice($decoded, 0, 3));
            continue;
        }
        if ($name === 'show_button') {
            $settings[$name] = filter_var($stored[$profileKey], FILTER_VALIDATE_BOOLEAN);
            continue;
        }
        $settings[$name] = $stored[$profileKey];
    }
    if (!array_key_exists($keys['desktop_links'], $stored)) {
        $settings['desktop_links'] = array_fill(0, count($settings['desktop_images']), (string) $settings['button_url']);
    }
    if (!array_key_exists($keys['mobile_links'], $stored)) {
        $settings['mobile_links'] = array_fill(0, count($settings['mobile_images']), (string) $settings['button_url']);
    }
    if (!array_key_exists($keys['desktop_button_labels'], $stored)) {
        $settings['desktop_button_labels'] = array_fill(0, count($settings['desktop_images']), (string) $settings['button_label']);
    }
    if (!array_key_exists($keys['mobile_button_labels'], $stored)) {
        $settings['mobile_button_labels'] = array_fill(0, count($settings['mobile_images']), (string) $settings['button_label']);
    }
    if (!array_key_exists($keys['desktop_show_buttons'], $stored)) {
        $settings['desktop_show_buttons'] = array_fill(0, count($settings['desktop_images']), (bool) $settings['show_button']);
    }
    if (!array_key_exists($keys['mobile_show_buttons'], $stored)) {
        $settings['mobile_show_buttons'] = array_fill(0, count($settings['mobile_images']), (bool) $settings['show_button']);
    }
    $settings['desktop_links'] = array_pad(
        array_slice($settings['desktop_links'], 0, count($settings['desktop_images'])),
        count($settings['desktop_images']),
        ''
    );
    $settings['mobile_links'] = array_pad(
        array_slice($settings['mobile_links'], 0, count($settings['mobile_images'])),
        count($settings['mobile_images']),
        ''
    );
    foreach (['desktop', 'mobile'] as $device) {
        $imageCount = count($settings["{$device}_images"]);
        $settings["{$device}_button_labels"] = array_pad(
            array_slice($settings["{$device}_button_labels"], 0, $imageCount),
            $imageCount,
            ''
        );
        $settings["{$device}_show_buttons"] = array_map(
            static fn(mixed $value): bool => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            array_pad(array_slice($settings["{$device}_show_buttons"], 0, $imageCount), $imageCount, true)
        );
    }
    Http::json(['ok' => true, 'data' => $settings, 'updated_at' => $updatedAt]);
}

function validateHomepageImages(mixed $input, string $label): array
{
    if (is_string($input)) {
        $decoded = json_decode($input, true);
        $input = is_array($decoded) ? $decoded : [];
    }
    if (!is_array($input)) {
        Http::json(['ok' => false, 'message' => "Daftar foto {$label} tidak valid."], 422);
    }
    $images = [];
    foreach (array_slice($input, 0, 3) as $item) {
        $url = trim((string) $item);
        if ($url === '') continue;
        $isLocalUpload = preg_match('#^/uploads/[a-f0-9]{32}/(?:hero|hero_mobile)\.webp$#i', $url) === 1;
        $isRemoteImage = filter_var($url, FILTER_VALIDATE_URL)
            && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
        if (!$isLocalUpload && !$isRemoteImage) {
            Http::json(['ok' => false, 'message' => "Tautan foto {$label} tidak valid."], 422);
        }
        if (!in_array($url, $images, true)) $images[] = $url;
    }
    return $images;
}

function isValidHomepageDestination(string $url): bool
{
    if ($url === '') return true;
    // Gunakan delimiter ~ karena karakter # juga sah sebagai anchor URL (contoh: /#programs).
    $isRelativeUrl = preg_match('~^(?!//)(?:[a-z0-9][a-z0-9._/-]*|/[^\s]*)?(?:#[a-z0-9_-]+)?$~i', $url) === 1;
    $isRemoteUrl = filter_var($url, FILTER_VALIDATE_URL)
        && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
    return $isRelativeUrl || $isRemoteUrl;
}

function validateHomepageLinks(mixed $input, int $imageCount, string $label): array
{
    if (is_string($input)) {
        $decoded = json_decode($input, true);
        $input = is_array($decoded) ? $decoded : [];
    }
    if (!is_array($input)) {
        Http::json(['ok' => false, 'message' => "Daftar tujuan foto {$label} tidak valid."], 422);
    }
    $links = [];
    for ($index = 0; $index < $imageCount; $index++) {
        $url = trim((string) ($input[$index] ?? ''));
        if (!isValidHomepageDestination($url)) {
            Http::json(['ok' => false, 'message' => 'Tujuan foto ' . ($index + 1) . " ({$label}) tidak valid."], 422);
        }
        $links[] = $url;
    }
    return $links;
}

function validateHomepageButtonLabels(mixed $input, int $imageCount, string $label): array
{
    if (!is_array($input)) {
        Http::json(['ok' => false, 'message' => "Daftar tulisan tombol foto {$label} tidak valid."], 422);
    }
    $labels = [];
    for ($index = 0; $index < $imageCount; $index++) {
        $value = trim((string) ($input[$index] ?? ''));
        if (mb_strlen($value) > 80) {
            Http::json(['ok' => false, 'message' => 'Tulisan tombol foto ' . ($index + 1) . " ({$label}) maksimal 80 karakter."], 422);
        }
        $labels[] = $value;
    }
    return $labels;
}

function validateHomepageButtonModes(mixed $input, int $imageCount, string $label): array
{
    if (!is_array($input)) {
        Http::json(['ok' => false, 'message' => "Pilihan tombol foto {$label} tidak valid."], 422);
    }
    return array_map(
        static fn(mixed $value): bool => filter_var($value, FILTER_VALIDATE_BOOLEAN),
        array_slice(array_pad($input, $imageCount, true), 0, $imageCount)
    );
}

function saveHomepageSettings(array $body): never
{
    $limits = ['kicker' => 60, 'title' => 180, 'description' => 500, 'button_label' => 80];
    $values = [];
    foreach ($limits as $key => $limit) {
        $value = trim((string) ($body[$key] ?? ''));
        if (mb_strlen($value) > $limit) {
            Http::json(['ok' => false, 'message' => "Kolom {$key} maksimal {$limit} karakter."], 422);
        }
        $values[$key] = $value;
    }

    $buttonUrl = trim((string) ($body['button_url'] ?? ''));
    if (!isValidHomepageDestination($buttonUrl)) {
        Http::json(['ok' => false, 'message' => 'Tautan tombol hero tidak valid.'], 422);
    }
    $values['desktop_images'] = validateHomepageImages($body['desktop_images'] ?? [], 'desktop');
    $values['mobile_images'] = validateHomepageImages($body['mobile_images'] ?? [], 'mobile');
    if ($values['desktop_images'] === []) {
        Http::json(['ok' => false, 'message' => 'Tambahkan minimal satu foto hero desktop.'], 422);
    }
    $usesPerImageLinks = array_key_exists('desktop_links', $body) || array_key_exists('mobile_links', $body);
    $desktopLinkInput = $usesPerImageLinks
        ? ($body['desktop_links'] ?? [])
        : array_fill(0, count($values['desktop_images']), $buttonUrl);
    $mobileLinkInput = $usesPerImageLinks
        ? ($body['mobile_links'] ?? [])
        : array_fill(0, count($values['mobile_images']), $buttonUrl);
    $values['desktop_links'] = validateHomepageLinks($desktopLinkInput, count($values['desktop_images']), 'desktop');
    $values['mobile_links'] = validateHomepageLinks($mobileLinkInput, count($values['mobile_images']), 'mobile');
    $showButton = array_key_exists('show_button', $body)
        ? filter_var($body['show_button'], FILTER_VALIDATE_BOOLEAN)
        : true;
    $usesPerImageButtons = array_key_exists('desktop_button_labels', $body)
        || array_key_exists('desktop_show_buttons', $body)
        || array_key_exists('mobile_button_labels', $body)
        || array_key_exists('mobile_show_buttons', $body);
    foreach (['desktop', 'mobile'] as $device) {
        $imageCount = count($values["{$device}_images"]);
        $labelInput = $usesPerImageButtons
            ? ($body["{$device}_button_labels"] ?? [])
            : array_fill(0, $imageCount, $values['button_label']);
        $modeInput = $usesPerImageButtons
            ? ($body["{$device}_show_buttons"] ?? [])
            : array_fill(0, $imageCount, $showButton);
        $values["{$device}_button_labels"] = validateHomepageButtonLabels($labelInput, $imageCount, $device);
        $values["{$device}_show_buttons"] = validateHomepageButtonModes($modeInput, $imageCount, $device);
        for ($index = 0; $index < $imageCount; $index++) {
            if (!$values["{$device}_show_buttons"][$index]) continue;
            $hasDestination = $values["{$device}_links"][$index] !== '';
            $hasButtonLabel = $values["{$device}_button_labels"][$index] !== '';
            if ($hasDestination !== $hasButtonLabel) {
                Http::json([
                    'ok' => false,
                    'message' => 'Tujuan dan tulisan tombol foto ' . ($index + 1) . " ({$device}) harus diisi bersamaan.",
                ], 422);
            }
        }
    }
    $allLinks = array_values(array_filter(array_merge($values['desktop_links'], $values['mobile_links'])));
    $values['button_url'] = $allLinks[0] ?? '';
    $values['button_label'] = $values['desktop_button_labels'][0] ?? ($values['mobile_button_labels'][0] ?? '');
    $values['show_button'] = $values['desktop_show_buttons'][0] ?? ($values['mobile_show_buttons'][0] ?? true);

    $profileKeys = homepageProfileKeys();
    $db = Database::connection();
    try {
        $db->beginTransaction();
        $statement = $db->prepare(
            'INSERT INTO institution_profile (profile_key, profile_value, updated_by)
             VALUES (:profile_key, :profile_value, :updated_by)
             ON DUPLICATE KEY UPDATE profile_value = VALUES(profile_value), updated_by = VALUES(updated_by)'
        );
        foreach ($values as $key => $value) {
            $storedValue = is_array($value)
                ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : (is_bool($value) ? ($value ? '1' : '0') : $value);
            $statement->execute([
                'profile_key' => $profileKeys[$key],
                'profile_value' => $storedValue,
                'updated_by' => Auth::id(),
            ]);
        }
        $db->commit();
        Http::json(['ok' => true, 'message' => 'Hero homepage berhasil disimpan.', 'data' => $values]);
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

function handleQrUpload(): never
{
    if (!extension_loaded('gd') || !function_exists('imagecreatefrompng')) {
        Http::json(['ok' => false, 'message' => 'Pemrosesan PNG belum aktif di server.'], 503);
    }
    if (!isset($_FILES['qr']) || !is_uploaded_file((string) ($_FILES['qr']['tmp_name'] ?? ''))) {
        Http::json(['ok' => false, 'message' => 'File QR/barcode tidak ditemukan.'], 422);
    }
    $file = $_FILES['qr'];
    if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || (int) ($file['size'] ?? 0) > 2 * 1024 * 1024) {
        Http::json(['ok' => false, 'message' => 'Upload QR gagal atau ukuran melebihi 2 MB.'], 422);
    }
    $info = @getimagesize((string) $file['tmp_name']);
    $width = (int) ($info[0] ?? 0);
    $height = (int) ($info[1] ?? 0);
    if (($info['mime'] ?? '') !== 'image/png' || $width < 150 || $height < 150 || $width > 3000 || $height > 3000) {
        Http::json(['ok' => false, 'message' => 'QR harus berupa PNG dengan resolusi 150 sampai 3000 piksel.'], 422);
    }
    $image = @imagecreatefrompng((string) $file['tmp_name']);
    if ($image === false) {
        Http::json(['ok' => false, 'message' => 'Isi file PNG QR rusak atau tidak valid.'], 422);
    }
    imagealphablending($image, false);
    imagesavealpha($image, true);
    $targetDirectory = dirname(__DIR__) . '/uploads/qrcodes';
    if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0755, true) && !is_dir($targetDirectory)) {
        imagedestroy($image);
        Http::json(['ok' => false, 'message' => 'Folder QR tidak dapat dibuat.'], 500);
    }
    $filename = bin2hex(random_bytes(16)) . '.png';
    $destination = $targetDirectory . '/' . $filename;
    $saved = imagepng($image, $destination, 6);
    imagedestroy($image);
    if (!$saved) {
        Http::json(['ok' => false, 'message' => 'QR gagal disimpan di server.'], 500);
    }
    @chmod($destination, 0644);
    Http::json([
        'ok' => true,
        'url' => '/uploads/qrcodes/' . $filename,
        'width' => $width,
        'height' => $height,
    ], 201);
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

    Auth::revokeAllSessions((int) $profile['id']);
    session_regenerate_id(true);
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

    Auth::revokeAllSessions((int) $profile['id']);
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

    Auth::revokeAllSessions((int) $target['id']);
    Auth::recordSecurityEvent('password_reset', (string) $target['email'], (int) $target['id']);
    Http::json([
        'ok' => true,
        'message' => 'Kata sandi admin berhasil direset. Semua sesi lama akun tersebut telah dihentikan.',
    ]);
}

function adminSessionsTableAvailable(): bool
{
    try {
        $statement = Database::connection()->query("SHOW COLUMNS FROM admin_sessions LIKE 'ip_hint'");
        return (bool) $statement->fetchColumn();
    } catch (Throwable) {
        return false;
    }
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
            'message' => 'Email sudah terdaftar. Gunakan tombol Ganti Password pada daftar admin.',
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
    Auth::revokeAllSessions((int) $id);
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
