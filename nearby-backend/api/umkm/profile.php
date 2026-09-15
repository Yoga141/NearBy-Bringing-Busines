<?php
require_once __DIR__ . '/../../helpers/response.php';
set_cors_headers();
require_once __DIR__ . '/../../helpers/pdo.php';
require_once __DIR__ . '/../../helpers/auth.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $id = $_GET['id'] ?? null;
    if (!$id) json_response(['success' => false, 'message' => 'ID wajib diisi.'], 422);

    $stmt = $pdo->prepare("SELECT p.*, c.name AS category_name, l.name AS location_name
        FROM umkm_profiles p
        LEFT JOIN categories c ON c.id = p.category_id
        LEFT JOIN locations l ON l.id = p.location_id WHERE p.id = ?");
    $stmt->execute([$id]);
    $profile = $stmt->fetch();
    if (!$profile) json_response(['success' => false, 'message' => 'Tidak ditemukan.'], 404);

    $stmt = $pdo->prepare("SELECT id, photo_path, is_primary FROM umkm_photos WHERE umkm_id = ? ORDER BY is_primary DESC, id ASC");
    $stmt->execute([$id]);
    $profile['photos'] = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT id, name, price, description FROM umkm_products WHERE umkm_id = ? ORDER BY id DESC");
    $stmt->execute([$id]);
    $profile['products'] = $stmt->fetchAll();

    json_response(['success' => true, 'data' => $profile]);
}

if ($method === 'POST') {
    $user = require_auth($pdo, 'umkm_owner');
    $input = get_json_input();
    $businessName = trim($input['business_name'] ?? '');
    if (!$businessName) json_response(['success' => false, 'message' => 'Nama usaha wajib diisi.'], 422);

    $fields = [
        $businessName, $input['category_id'] ?: null, $input['location_id'] ?: null,
        trim($input['address_detail'] ?? ''), trim($input['description'] ?? ''),
        trim($input['instagram'] ?? ''), trim($input['facebook'] ?? ''),
        trim($input['whatsapp'] ?? ''), trim($input['tiktok'] ?? ''), trim($input['website'] ?? ''),
    ];

    $stmt = $pdo->prepare("SELECT id FROM umkm_profiles WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $existing = $stmt->fetch();

    if ($existing) {
        $stmt = $pdo->prepare("UPDATE umkm_profiles SET business_name=?, category_id=?, location_id=?,
            address_detail=?, description=?, instagram=?, facebook=?, whatsapp=?, tiktok=?, website=? WHERE id=?");
        $stmt->execute([...$fields, $existing['id']]);
        $profileId = $existing['id'];
    } else {
        $stmt = $pdo->prepare("INSERT INTO umkm_profiles
            (user_id, business_name, category_id, location_id, address_detail, description, instagram, facebook, whatsapp, tiktok, website)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user['id'], ...$fields]);
        $profileId = $pdo->lastInsertId();
    }

    json_response(['success' => true, 'message' => 'Profil tersimpan.', 'id' => $profileId]);
}

json_response(['success' => false], 405);
