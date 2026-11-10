<?php
// backend/api/users/index.php

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    errorResponse('Method Not Allowed', 405);
}

requireAdmin();
$db = Database::getConnection();

$stmt = $db->query("
    SELECT id, email, full_name, phone, address, city, province, postal_code, avatar_url, role, created_at, updated_at
    FROM users
    ORDER BY created_at DESC
");
$users = $stmt->fetchAll();

jsonResponse($users);

