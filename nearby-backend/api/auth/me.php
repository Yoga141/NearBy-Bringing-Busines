<?php // me.php
require_once __DIR__ . '/../../helpers/response.php';
set_cors_headers();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth.php';
json_response(['success' => true, 'user' => require_auth($pdo)]);
