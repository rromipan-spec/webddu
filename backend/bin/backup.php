<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/src/Config.php';
require_once dirname(__DIR__) . '/src/Database.php';

const DEFAULT_RETENTION = 14;

try {
    Config::load(dirname(__DIR__) . '/config/.env');

    if (!class_exists(ZipArchive::class)) {
        throw new RuntimeException('Ekstensi PHP ZipArchive belum aktif. Aktifkan ekstensi zip di Hostinger.');
    }

    $backupDirectory = dirname(__DIR__) . '/storage/backups';
    ensureDirectory($backupDirectory, 0700);

    $lockHandle = fopen($backupDirectory . '/.backup.lock', 'c+');
    if ($lockHandle === false || !flock($lockHandle, LOCK_EX | LOCK_NB)) {
        throw new RuntimeException('Backup lain masih berjalan. Proses ini dibatalkan.');
    }

    $stamp = date('Ymd-His');
    $random = bin2hex(random_bytes(4));
    $temporaryDirectory = $backupDirectory . '/.tmp-' . $stamp . '-' . $random;
    $partialArchive = $backupDirectory . '/.ddu-backup-' . $stamp . '-' . $random . '.partial';
    $finalArchive = $backupDirectory . '/ddu-backup-' . $stamp . '.zip';
    $checksumFile = $finalArchive . '.sha256';

    ensureDirectory($temporaryDirectory, 0700);

    try {
        $databaseFile = $temporaryDirectory . '/database.sql';
        $databaseSummary = dumpDatabase(Database::connection(), $databaseFile);
        $uploadsDirectory = dirname(__DIR__, 2) . '/frontend/uploads';
        $uploadSummary = summarizeUploads($uploadsDirectory);

        $manifest = [
            'format_version' => 1,
            'created_at' => date(DATE_ATOM),
            'application' => 'Dompet Dana Umat',
            'database' => $databaseSummary,
            'uploads' => $uploadSummary,
            'database_sha256' => hash_file('sha256', $databaseFile),
        ];
        $manifestJson = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($manifestJson === false || file_put_contents($temporaryDirectory . '/manifest.json', $manifestJson . PHP_EOL, LOCK_EX) === false) {
            throw new RuntimeException('Manifest backup gagal dibuat.');
        }

        createArchive($partialArchive, $temporaryDirectory, $uploadsDirectory);
        if (!rename($partialArchive, $finalArchive)) {
            throw new RuntimeException('Arsip backup gagal diselesaikan.');
        }
        chmod($finalArchive, 0600);
        verifyArchive($finalArchive);

        $checksum = hash_file('sha256', $finalArchive);
        if ($checksum === false || file_put_contents($checksumFile, $checksum . '  ' . basename($finalArchive) . PHP_EOL, LOCK_EX) === false) {
            throw new RuntimeException('Checksum backup gagal dibuat.');
        }
        chmod($checksumFile, 0600);

        $retention = max(3, min(60, (int) Config::get('BACKUP_RETENTION', (string) DEFAULT_RETENTION)));
        $removed = pruneBackups($backupDirectory, $retention);

        echo sprintf(
            "Backup berhasil: %s | tabel: %d | baris: %d | uploads: %d file | versi lama dihapus: %d\n",
            basename($finalArchive),
            $databaseSummary['table_count'],
            $databaseSummary['row_count'],
            $uploadSummary['file_count'],
            $removed
        );
    } catch (Throwable $error) {
        if (is_file($finalArchive)) {
            unlink($finalArchive);
        }
        if (is_file($checksumFile)) {
            unlink($checksumFile);
        }
        throw $error;
    } finally {
        removeDirectory($temporaryDirectory);
        if (is_file($partialArchive)) {
            unlink($partialArchive);
        }
    }
} catch (Throwable $error) {
    fwrite(STDERR, '[BACKUP GAGAL] ' . $error->getMessage() . PHP_EOL);
    exit(1);
} finally {
    if (isset($lockHandle) && is_resource($lockHandle)) {
        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
    }
}

function dumpDatabase(PDO $database, string $destination): array
{
    $handle = fopen($destination, 'wb');
    if ($handle === false) {
        throw new RuntimeException('File database.sql tidak dapat dibuat.');
    }

    $tableCount = 0;
    $rowCount = 0;

    try {
        writeAll($handle, "-- Backup MySQL Dompet Dana Umat\n");
        writeAll($handle, '-- Dibuat: ' . date(DATE_ATOM) . "\n\n");
        writeAll($handle, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\nSET UNIQUE_CHECKS=0;\n\n");

        $database->exec('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        $database->beginTransaction();

        $tables = $database->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_NUM);
        foreach ($tables as $tableRow) {
            $table = (string) ($tableRow[0] ?? '');
            if ($table === '') {
                continue;
            }
            $identifier = quoteIdentifier($table);
            $createRow = $database->query('SHOW CREATE TABLE ' . $identifier)->fetch(PDO::FETCH_NUM);
            $createSql = (string) ($createRow[1] ?? '');
            if ($createSql === '') {
                throw new RuntimeException('Struktur tabel ' . $table . ' tidak dapat dibaca.');
            }

            writeAll($handle, "-- Tabel: {$table}\nDROP TABLE IF EXISTS {$identifier};\n{$createSql};\n\n");
            $statement = $database->query('SELECT * FROM ' . $identifier);
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $columns = array_map('quoteIdentifier', array_keys($row));
                $values = array_map(static function (mixed $value) use ($database): string {
                    if ($value === null) {
                        return 'NULL';
                    }
                    $quoted = $database->quote((string) $value);
                    if ($quoted === false) {
                        throw new RuntimeException('Salah satu nilai database gagal diproses.');
                    }
                    return $quoted;
                }, array_values($row));
                writeAll(
                    $handle,
                    'INSERT INTO ' . $identifier . ' (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ");\n"
                );
                $rowCount++;
            }
            writeAll($handle, "\n");
            $tableCount++;
        }

        $database->commit();
        writeAll($handle, "SET UNIQUE_CHECKS=1;\nSET FOREIGN_KEY_CHECKS=1;\n");
    } catch (Throwable $error) {
        if ($database->inTransaction()) {
            $database->rollBack();
        }
        throw $error;
    } finally {
        fclose($handle);
    }

    chmod($destination, 0600);
    return ['table_count' => $tableCount, 'row_count' => $rowCount];
}

function createArchive(string $archivePath, string $temporaryDirectory, string $uploadsDirectory): void
{
    $zip = new ZipArchive();
    if ($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('Arsip ZIP tidak dapat dibuat.');
    }

    try {
        if (!$zip->addFile($temporaryDirectory . '/database.sql', 'database.sql')) {
            throw new RuntimeException('database.sql gagal dimasukkan ke arsip.');
        }
        if (!$zip->addFile($temporaryDirectory . '/manifest.json', 'manifest.json')) {
            throw new RuntimeException('manifest.json gagal dimasukkan ke arsip.');
        }

        if (is_dir($uploadsDirectory)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($uploadsDirectory, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($iterator as $file) {
                if (!$file instanceof SplFileInfo || !$file->isFile()) {
                    continue;
                }
                $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($uploadsDirectory) + 1));
                if (!$zip->addFile($file->getPathname(), 'uploads/' . $relative)) {
                    throw new RuntimeException('Gambar ' . $relative . ' gagal dimasukkan ke arsip.');
                }
            }
        }
    } finally {
        if (!$zip->close()) {
            throw new RuntimeException('Arsip ZIP gagal ditutup dengan benar.');
        }
    }
}

function summarizeUploads(string $directory): array
{
    $fileCount = 0;
    $totalBytes = 0;
    if (!is_dir($directory)) {
        return ['file_count' => 0, 'total_bytes' => 0];
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    foreach ($iterator as $file) {
        if ($file instanceof SplFileInfo && $file->isFile()) {
            $fileCount++;
            $totalBytes += $file->getSize();
        }
    }
    return ['file_count' => $fileCount, 'total_bytes' => $totalBytes];
}

function verifyArchive(string $archivePath): void
{
    $zip = new ZipArchive();
    if ($zip->open($archivePath, ZipArchive::CHECKCONS) !== true) {
        throw new RuntimeException('Pemeriksaan integritas arsip ZIP gagal.');
    }
    try {
        if ($zip->locateName('database.sql') === false || $zip->locateName('manifest.json') === false) {
            throw new RuntimeException('Arsip backup tidak memiliki database.sql atau manifest.json.');
        }
    } finally {
        $zip->close();
    }
}

function pruneBackups(string $directory, int $retention): int
{
    $archives = glob($directory . '/ddu-backup-*.zip') ?: [];
    usort($archives, static fn(string $left, string $right): int => filemtime($right) <=> filemtime($left));
    $removed = 0;
    foreach (array_slice($archives, $retention) as $archive) {
        if (unlink($archive)) {
            $removed++;
        }
        $checksum = $archive . '.sha256';
        if (is_file($checksum)) {
            unlink($checksum);
        }
    }
    return $removed;
}

function ensureDirectory(string $directory, int $permissions): void
{
    if (!is_dir($directory) && !mkdir($directory, $permissions, true) && !is_dir($directory)) {
        throw new RuntimeException('Folder backup tidak dapat dibuat.');
    }
    chmod($directory, $permissions);
}

function quoteIdentifier(string $identifier): string
{
    return '`' . str_replace('`', '``', $identifier) . '`';
}

function writeAll($handle, string $contents): void
{
    $length = strlen($contents);
    $written = 0;
    while ($written < $length) {
        $result = fwrite($handle, substr($contents, $written));
        if ($result === false || $result === 0) {
            throw new RuntimeException('Penulisan database.sql gagal.');
        }
        $written += $result;
    }
}

function removeDirectory(string $directory): void
{
    if (!is_dir($directory)) {
        return;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iterator as $item) {
        if ($item->isDir()) {
            rmdir($item->getPathname());
        } else {
            unlink($item->getPathname());
        }
    }
    rmdir($directory);
}
