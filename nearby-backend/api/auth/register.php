<?php
require_once __DIR__ . '/../../helpers/response.php';
set_cors_headers();
require_once __DIR__ . '/../../helpers/pdo.php';
require_once __DIR__ . '/../../helpers/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['success' => false], 405);

$input = get_json_input();
$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$role = in_array($input['role'] ?? '', ['user', 'umkm_owner']) ? $input['role'] : 'user';

if (!$name || !$email || !$password) json_response(['success' => false, 'message' => 'Data belum lengkap.'], 422);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_response(['success' => false, 'message' => 'Email tidak valid.'], 422);
if (strlen($password) < 6) json_response(['success' => false, 'message' => 'Password minimal 6 karakter.'], 422);

$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) json_response(['success' => false, 'message' => 'Email sudah terdaftar.'], 409);

$stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
$stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
$userId = $pdo->lastInsertId();
$token = create_session_token($pdo, $userId);

json_response([
    'success' => true, 'token' => $token,
    'user' => ['id' => $userId, 'name' => $name, 'email' => $email, 'role' => $role],
], 201);
