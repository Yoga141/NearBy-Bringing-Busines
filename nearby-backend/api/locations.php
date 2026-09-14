<?php // categories.php (locations.php sama persis, tinggal ganti nama tabel)
require_once __DIR__ . '/../helpers/response.php';
set_cors_headers();
require_once __DIR__ . '/../config/database.php';
json_response(['success' => true, 'data' => $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll()]);
