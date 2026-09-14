<?php
require_once __DIR__ . '/../../helpers/response.php';
set_cors_headers();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['success' => false], 405);

$input = get_json_input();
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([trim($input['email'] ?? '')]);
$user = $stmt->fetch();

if (!$user || !password_verify($input['password'] ?? '', $user['password'])) {
    json_response(['success' => false, 'message' => 'Email atau password salah.'], 401);
}

$token = create_session_token($pdo, $user['id']);
json_response([
    'success' => true, 'token' => $token,
    'user' => ['id' => $user['id'], 'name' => $user['name'], 'email' => $user['email'], 'role' => $user['role']],
]);
