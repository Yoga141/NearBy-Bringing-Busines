<?php
require_once __DIR__ . '/../../helpers/response.php';
set_cors_headers();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth.php';

$user = require_auth($pdo, 'umkm_owner');
$stmt = $pdo->prepare("SELECT id FROM umkm_profiles WHERE user_id = ?");
$stmt->execute([$user['id']]);
$profile = $stmt->fetch();
if (!$profile) json_response(['success' => false, 'message' => 'Lengkapi profil dulu.'], 422);
$umkmId = $profile['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = get_json_input();
    $name = trim($input['name'] ?? '');
    if (!$name) json_response(['success' => false, 'message' => 'Nama produk wajib diisi.'], 422);

    if (!empty($input['id'])) {
        $stmt = $pdo->prepare("UPDATE umkm_products SET name=?, price=?, description=? WHERE id=? AND umkm_id=?");
        $stmt->execute([$name, $input['price'] ?? 0, trim($input['description'] ?? ''), $input['id'], $umkmId]);
        json_response(['success' => true, 'id' => $input['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO umkm_products (umkm_id, name, price, description) VALUES (?, ?, ?, ?)");
        $stmt->execute([$umkmId, $name, $input['price'] ?? 0, trim($input['description'] ?? '')]);
        json_response(['success' => true, 'id' => $pdo->lastInsertId()], 201);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $stmt = $pdo->prepare("DELETE FROM umkm_products WHERE id = ? AND umkm_id = ?");
    $stmt->execute([$_GET['id'] ?? 0, $umkmId]);
    json_response(['success' => true]);
}

json_response(['success' => false], 405);
