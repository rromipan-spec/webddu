<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/src/Config.php';
require_once dirname(__DIR__) . '/src/Database.php';
require_once dirname(__DIR__) . '/src/ImageProcessor.php';

try {
    Config::load(dirname(__DIR__) . '/config/.env');
    $database = Database::connection();
    $uploadRoot = resolveUploadRoot();
    $records = loadRecords($database);
    $urls = collectLocalUploadUrls($records);
    $map = [];
    $skipped = 0;

    foreach ($urls as $url) {
        $path = uploadUrlToPath($url, $uploadRoot);
        if ($path === null || !is_file($path)) {
            fwrite(STDERR, "[LEWATI] File tidak ditemukan: {$url}\n");
            $skipped++;
            continue;
        }
        $info = @getimagesize($path);
        $mime = (string) ($info['mime'] ?? '');
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            fwrite(STDERR, "[LEWATI] Format tidak didukung: {$url}\n");
            $skipped++;
            continue;
        }
        try {
            $result = ImageProcessor::process($path, $mime, $uploadRoot, false);
            $map[$url] = $result['variants'];
            echo "[OK] {$url}\n";
        } catch (Throwable $error) {
            fwrite(STDERR, "[LEWATI] {$url}: {$error->getMessage()}\n");
            $skipped++;
        }
    }

    if ($map === []) {
        echo "Tidak ada gambar lama yang perlu dimigrasikan.\n";
        exit(0);
    }

    $database->beginTransaction();
    try {
        foreach ($records['posts'] as $record) {
            updatePost($database, $record, $map);
        }
        foreach ($records['programs'] as $record) {
            updateProgram($database, $record, $map);
        }
        $database->commit();
    } catch (Throwable $error) {
        if ($database->inTransaction()) {
            $database->rollBack();
        }
        throw $error;
    }

    echo sprintf(
        "Migrasi selesai: %d gambar dioptimalkan, %d dilewati. File lama tetap disimpan.\n",
        count($map),
        $skipped
    );
} catch (Throwable $error) {
    fwrite(STDERR, '[OPTIMASI GAGAL] ' . $error->getMessage() . PHP_EOL);
    exit(1);
}

function loadRecords(PDO $database): array
{
    return [
        'posts' => $database->query(
            'SELECT id, image, gallery_images, hero_image, hero_images, content FROM posts'
        )->fetchAll(PDO::FETCH_ASSOC),
        'programs' => $database->query(
            'SELECT id, image, gallery_images, content FROM programs'
        )->fetchAll(PDO::FETCH_ASSOC),
    ];
}

function collectLocalUploadUrls(array $records): array
{
    $urls = [];
    foreach (array_merge($records['posts'], $records['programs']) as $record) {
        foreach ($record as $field => $value) {
            if ($field === 'id' || !is_string($value)) {
                continue;
            }
            preg_match_all('#/uploads/[A-Za-z0-9._/-]+#', $value, $matches);
            foreach ($matches[0] ?? [] as $url) {
                $url = rtrim($url, '.,;)');
                if (!preg_match('#^/uploads/[a-f0-9]{32}/(?:thumb|card|content|hero|social)\.(?:webp|jpg)$#i', $url)) {
                    $urls[$url] = true;
                }
            }
        }
    }
    return array_keys($urls);
}

function updatePost(PDO $database, array $record, array $map): void
{
    $values = [
        'image' => replaceManagedUrls((string) $record['image'], 'card', $map),
        'gallery_images' => replaceManagedUrls((string) $record['gallery_images'], 'card', $map),
        'hero_image' => replaceManagedUrls((string) $record['hero_image'], 'hero', $map),
        'hero_images' => replaceManagedUrls((string) $record['hero_images'], 'hero', $map),
        'content' => replaceManagedUrls((string) $record['content'], 'content', $map),
    ];
    if (
        $values['image'] === (string) $record['image']
        && $values['gallery_images'] === (string) $record['gallery_images']
        && $values['hero_image'] === (string) $record['hero_image']
        && $values['hero_images'] === (string) $record['hero_images']
        && $values['content'] === (string) $record['content']
    ) {
        return;
    }
    $statement = $database->prepare(
        'UPDATE posts SET image = :image, gallery_images = :gallery_images,
         hero_image = :hero_image, hero_images = :hero_images, content = :content WHERE id = :id'
    );
    $statement->execute(['id' => (int) $record['id']] + $values);
}

function updateProgram(PDO $database, array $record, array $map): void
{
    $values = [
        'image' => replaceManagedUrls((string) $record['image'], 'card', $map),
        'gallery_images' => replaceManagedUrls((string) $record['gallery_images'], 'card', $map),
        'content' => replaceManagedUrls((string) $record['content'], 'content', $map),
    ];
    if (
        $values['image'] === (string) $record['image']
        && $values['gallery_images'] === (string) $record['gallery_images']
        && $values['content'] === (string) $record['content']
    ) {
        return;
    }
    $statement = $database->prepare(
        'UPDATE programs SET image = :image, gallery_images = :gallery_images, content = :content WHERE id = :id'
    );
    $statement->execute(['id' => (int) $record['id']] + $values);
}

function replaceManagedUrls(string $value, string $variant, array $map): string
{
    foreach ($map as $oldUrl => $variants) {
        $replacement = (string) ($variants[$variant] ?? $variants['card'] ?? $oldUrl);
        $value = str_replace($oldUrl, $replacement, $value);
    }
    return $value;
}

function resolveUploadRoot(): string
{
    $domainRoot = dirname(__DIR__, 2);
    foreach ([$domainRoot . '/public_html/uploads', $domainRoot . '/frontend/uploads'] as $candidate) {
        if (is_dir($candidate)) {
            return realpath($candidate) ?: $candidate;
        }
    }
    throw new RuntimeException('Folder uploads tidak ditemukan.');
}

function uploadUrlToPath(string $url, string $uploadRoot): ?string
{
    $relative = substr($url, strlen('/uploads/'));
    if ($relative === false || $relative === '' || str_contains($relative, '..')) {
        return null;
    }
    $candidate = $uploadRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    $real = realpath($candidate);
    if ($real === false) {
        return null;
    }
    $root = rtrim(str_replace('\\', '/', realpath($uploadRoot) ?: $uploadRoot), '/');
    $normalized = str_replace('\\', '/', $real);
    return str_starts_with($normalized, $root . '/') ? $real : null;
}
