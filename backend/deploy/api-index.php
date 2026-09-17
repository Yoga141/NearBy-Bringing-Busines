<?php

/**
 * Front controller khusus produksi untuk /home/nearby/public_html/api/.
 *
 * Kenapa file ini ada:
 * .cpanel.yml menyalin isi `backend/public/` ke `public_html/api/`,
 * sementara aplikasinya sendiri tinggal di `/home/nearby/nearby-backend/`.
 * Akibatnya `public/index.php` bawaan Laravel — yang mencari vendor dan
 * bootstrap lewat `__DIR__.'/../'` — ikut berpindah dan jadi menunjuk ke
 * `/home/nearby/public_html/`, tempat yang tidak berisi apa-apa. PHP fatal
 * di baris `require` sebelum Laravel sempat boot, dan seluruh endpoint API
 * balas HTTP 500 dengan body kosong.
 *
 * File ini menyelesaikannya dengan menunjuk path aplikasi secara absolut,
 * lalu disalin menimpa index.php hasil copy pada task deploy terakhir.
 * Karena ikut ter-commit, perbaikannya tidak akan tertimpa lagi tiap deploy —
 * berbeda dengan menambal index.php langsung di server.
 *
 * `public/index.php` sengaja dibiarkan apa adanya supaya `php artisan serve`
 * dan Vite di komputer lokal tetap berjalan normal.
 */

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/**
 * Lokasi aplikasi Laravel di server. Samakan dengan tujuan task
 * `cp -R backend/* ...` di .cpanel.yml kalau suatu saat dipindah.
 */
$appBase = '/home/nearby/nearby-backend';

if (! is_file($appBase.'/vendor/autoload.php')) {
    // Tanpa ini, kegagalan path cuma tampil sebagai 500 kosong yang sulit
    // dilacak — persis masalah yang file ini perbaiki.
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'message' => 'Aplikasi tidak ditemukan di server. Periksa path pada deploy/api-index.php.',
    ]);

    exit(1);
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $appBase.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $appBase.'/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once $appBase.'/bootstrap/app.php';

// Direktori inilah yang benar-benar disajikan web server, bukan
// `backend/public`, jadi `public_path()` diarahkan ke sini.
$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
