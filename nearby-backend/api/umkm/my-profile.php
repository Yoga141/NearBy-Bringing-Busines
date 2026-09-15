<?php
require_once __DIR__ . '/../../helpers/response.php';
set_cors_headers();
require_once __DIR__ . '/../../helpers/pdo.php';
require_once __DIR__ . '/../../helpers/auth.php';

$user = require_auth($pdo, 'umkm_owner');
$stmt = $pdo->prepare("SELECT * FROM umkm_profiles WHERE user_id = ?");
$stmt->execute([$user['id']]);
$profile = $stmt->fetch();

if ($profile) {
    $stmt = $pdo->prepare("SELECT id, photo_path, is_primary FROM umkm_photos WHERE umkm_id = ?");
    $stmt->execute([$profile['id']]);
    $profile['photos'] = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT id, name, price, description FROM umkm_products WHERE umkm_id = ?");
    $stmt->execute([$profile['id']]);
    $profile['products'] = $stmt->fetchAll();
}

json_response(['success' => true, 'data' => $profile]);
