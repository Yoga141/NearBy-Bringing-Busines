<?php // logout.php
require_once __DIR__ . '/../../helpers/response.php';
set_cors_headers();
require_once __DIR__ . '/../../helpers/pdo.php';

$headers = getallheaders();
if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'] ?? '', $m)) {
    $stmt = $pdo->prepare("DELETE FROM auth_tokens WHERE token = ?");
    $stmt->execute([$m[1]]);
}
json_response(['success' => true]);
