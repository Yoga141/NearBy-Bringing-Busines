<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

// Mencegah Symfony menghapus awalan '/api' dari URI jika diakses via symlink /api.
// Jika file ini dipanggil dari folder /api/, SCRIPT_NAME akan bernilai /api/index.php.
// Symfony Request akan mengira /api adalah base path dan menghapusnya dari request,
// sehingga URI /api/register berubah menjadi /register (menyebabkan 404 NotFound).
if (isset($_SERVER['SCRIPT_NAME']) && str_starts_with($_SERVER['SCRIPT_NAME'], '/api/')) {
    $_SERVER['SCRIPT_NAME'] = '/index.php';
}

$app->handleRequest(Request::capture());
