<?php
// backend/api/users/toggle-role.php

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Method Not Allowed', 405);
}

$currentAdmin = requireAdmin();
$input = getJsonInput();

$userId = $input['user_id'] ?? null;
$newRole = $input['role'] ?? null;

if (!$userId || !in_array($newRole, ['user', 'admin', 'superadmin'])) {
    errorResponse('Parameter user_id dan role (user|admin|superadmin) tidak valid.', 422);
}

if ($userId === $currentAdmin['id']) {
    errorResponse('Anda tidak dapat mengubah role akun Anda sendiri.', 400);
}

$db = Database::getConnection();
$stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
$stmt->execute([$newRole, $userId]);

jsonResponse(['success' => true, 'role' => $newRole], 200, 'Role pengguna berhasil diperbarui');

