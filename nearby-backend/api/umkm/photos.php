<?php
require_once __DIR__ . '/../../helpers/response.php';
set_cors_headers();
require_once __DIR__ . '/../../helpers/pdo.php';
require_once __DIR__ . '/../../helpers/auth.php';

$user = require_auth($pdo, 'umkm_owner');
$stmt = $pdo->prepare("SELECT id FROM umkm_profiles WHERE user_id = ?");
$stmt->execute([$user['id']]);
$profile = $stmt->fetch();
if (!$profile) json_response(['success' => false, 'message' => 'Lengkapi profil dulu.'], 422);
$umkmId = $profile['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_FILES['photo'])) json_response(['success' => false, 'message' => 'File tidak ada.'], 422);
    $file = $_FILES['photo'];

    if (!in_array($file['type'], ['image/jpeg', 'image/png', 'image/webp'])) {
        json_response(['success' => false, 'message' => 'Format harus JPG/PNG/WEBP.'], 422);
    }
    if ($file['size'] > 3 * 1024 * 1024) json_response(['success' => false, 'message' => 'Maksimal 3MB.'], 422);

    $dir = __DIR__ . '/../../uploads/umkm/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $filename = 'umkm_' . $umkmId . '_' . uniqid() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
    move_uploaded_file($file['tmp_name'], $dir . $filename);
    $relativePath = 'uploads/umkm/' . $filename;

    $stmt = $pdo->prepare("SELECT COUNT(*) t FROM umkm_photos WHERE umkm_id = ?");
    $stmt->execute([$umkmId]);
    $isPrimary = $stmt->fetch()['t'] == 0 ? 1 : 0;

    $stmt = $pdo->prepare("INSERT INTO umkm_photos (umkm_id, photo_path, is_primary) VALUES (?, ?, ?)");
    $stmt->execute([$umkmId, $relativePath, $isPrimary]);

    json_response(['success' => true, 'data' => ['id' => $pdo->lastInsertId(), 'photo_path' => $relativePath]], 201);
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $id = $_GET['id'] ?? null;
    $stmt = $pdo->prepare("SELECT * FROM umkm_photos WHERE id = ? AND umkm_id = ?");
    $stmt->execute([$id, $umkmId]);
    $photo = $stmt->fetch();
    if (!$photo) json_response(['success' => false, 'message' => 'Tidak ditemukan.'], 404);

    @unlink(__DIR__ . '/../../' . $photo['photo_path']);
    $stmt = $pdo->prepare("DELETE FROM umkm_photos WHERE id = ?");
    $stmt->execute([$id]);
    json_response(['success' => true]);
}

json_response(['success' => false], 405);
