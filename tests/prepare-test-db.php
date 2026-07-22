<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/backend/src/Config.php';
require_once dirname(__DIR__) . '/backend/src/Database.php';

Config::load(dirname(__DIR__) . '/backend/config/.env');

$environment = Config::get('APP_ENV', 'production');
$databaseName = Config::get('DB_NAME');
if ($environment !== 'testing' || !str_contains(strtolower($databaseName), 'test')) {
    throw new RuntimeException('Fixture ditolak: hanya boleh dijalankan pada database testing.');
}

$schemaPath = dirname(__DIR__) . '/database/schema.sql';
$schema = file_get_contents($schemaPath);
if (!is_string($schema) || trim($schema) === '') {
    throw new RuntimeException('database/schema.sql tidak dapat dibaca.');
}

$database = Database::connection();
$statements = preg_split('/;\s*(?:\r?\n|$)/', $schema) ?: [];
foreach ($statements as $statement) {
    $statement = trim($statement);
    if ($statement !== '') {
        $database->exec($statement);
    }
}

$seed = $database->prepare(
    'INSERT INTO admins (email, password_hash, display_name, role, is_active)
     VALUES (:email, :password_hash, :display_name, :role, 1)
     ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), role = VALUES(role), is_active = 1'
);
$seed->execute([
    'email' => 'integration-test@ddu.invalid',
    'password_hash' => password_hash('DduIntegrationTest!2026', PASSWORD_DEFAULT),
    'display_name' => 'Integration Test',
    'role' => 'super_admin',
]);

echo "Database testing siap.\n";
