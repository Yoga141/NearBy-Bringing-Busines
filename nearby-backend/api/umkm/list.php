<?php
require_once __DIR__ . '/../../helpers/response.php';
set_cors_headers();
require_once __DIR__ . '/../../config/database.php';

$sql = "SELECT p.id, p.business_name, p.description, c.name AS category_name, l.name AS location_name,
        (SELECT photo_path FROM umkm_photos WHERE umkm_id = p.id ORDER BY is_primary DESC, id ASC LIMIT 1) AS cover_photo
        FROM umkm_profiles p
        LEFT JOIN categories c ON c.id = p.category_id
        LEFT JOIN locations l ON l.id = p.location_id WHERE 1=1";
$params = [];

if (!empty($_GET['category_id'])) { $sql .= " AND p.category_id = ?"; $params[] = $_GET['category_id']; }
if (!empty($_GET['location_id'])) { $sql .= " AND p.location_id = ?"; $params[] = $_GET['location_id']; }
if (!empty($_GET['q'])) { $sql .= " AND p.business_name LIKE ?"; $params[] = "%{$_GET['q']}%"; }
$sql .= " ORDER BY p.updated_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
json_response(['success' => true, 'data' => $stmt->fetchAll()]);
