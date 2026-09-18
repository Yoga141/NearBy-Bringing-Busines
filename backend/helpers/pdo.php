<?php

/**
 * PDO connection for the standalone endpoints under `api/`.
 *
 * Those files are plain PHP and never boot Laravel, so they can't use
 * `config/database.php` (which just returns Laravel's config array - requiring
 * it was the reason `$pdo` was always undefined). This file is the missing
 * piece: it defines `$pdo` and nothing else.
 *
 * The schema it expects is `database/schema.sql` (MySQL: users, auth_tokens,
 * umkm_profiles, umkm_photos, umkm_products, categories, locations) - import
 * that first, as the README's "Cara Menjalankan" describes. Note this is a
 * different data model from the Laravel migrations, which the Vue frontend
 * uses via `routes/api.php`.
 *
 * Credentials come from .env when present, otherwise from the defaults below,
 * which match schema.sql on a stock XAMPP/Laragon install.
 */

require_once __DIR__ . '/response.php';

/**
 * Minimal .env reader - Laravel's `env()` isn't available here.
 *
 * @return array<string, string>
 */
$readEnv = static function (string $path): array {
    if (! is_readable($path)) {
        return [];
    }

    $values = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || ! str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $value = trim($value);
        // Strip surrounding quotes, the way dotenv does.
        if (strlen($value) >= 2 && ($value[0] === '"' || $value[0] === "'") && $value[-1] === $value[0]) {
            $value = substr($value, 1, -1);
        }
        $values[trim($key)] = $value;
    }

    return $values;
};

$env = $readEnv(__DIR__ . '/../.env');

/** Falls back to the default when the key is absent *or* present but blank. */
$cfg = static function (string $key, string $default) use ($env): string {
    $value = $env[$key] ?? getenv($key) ?: '';

    return $value !== '' && $value !== false ? (string) $value : $default;
};

$host = $cfg('DB_HOST', '127.0.0.1');
$port = $cfg('DB_PORT', '3306');
$database = $cfg('DB_DATABASE', 'nearby_balikpapan');
$username = $cfg('DB_USERNAME', 'root');
$password = $env['DB_PASSWORD'] ?? '';

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            // The endpoints read columns as $row['name'], so associative rows
            // must be the default fetch style.
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ],
    );
} catch (PDOException $e) {
    // A bare PDOException here would surface the DSN and credentials to the
    // client; answer with the same JSON envelope the endpoints use instead.
    error_log('[nearby] koneksi database gagal: ' . $e->getMessage());
    json_response([
        'success' => false,
        'message' => 'Tidak dapat terhubung ke database. Periksa konfigurasi di .env dan pastikan database sudah diimpor.',
    ], 500);
}
