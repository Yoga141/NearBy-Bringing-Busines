<?php
require_once __DIR__ . '/response.php';

function create_session_token($pdo, $userId) {
    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));
    $stmt = $pdo->prepare("INSERT INTO auth_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $token, $expiresAt]);
    return $token;
}

function get_authenticated_user($pdo) {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) return null;

    $stmt = $pdo->prepare("
        SELECT u.id, u.name, u.email, u.role
        FROM auth_tokens t JOIN users u ON u.id = t.user_id
        WHERE t.token = ? AND t.expires_at > NOW()
    ");
    $stmt->execute([$matches[1]]);
    return $stmt->fetch() ?: null;
}

function require_auth($pdo, $requiredRole = null) {
    $user = get_authenticated_user($pdo);
    if (!$user) json_response(['success' => false, 'message' => 'Belum login.'], 401);
    if ($requiredRole && $user['role'] !== $requiredRole) {
        json_response(['success' => false, 'message' => 'Akses ditolak.'], 403);
    }
    return $user;
}
