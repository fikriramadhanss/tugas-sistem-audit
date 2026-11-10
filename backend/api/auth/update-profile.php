<?php
// backend/api/auth/update-profile.php

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/auth.php';

if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT'])) {
    errorResponse('Method Not Allowed', 405);
}

$user = requireAuth();
$input = getJsonInput();

$db = Database::getConnection();

$fields = [
    'full_name'   => $input['full_name'] ?? $user['full_name'],
    'phone'       => $input['phone'] ?? $user['phone'],
    'address'     => $input['address'] ?? $user['address'],
    'city'        => $input['city'] ?? $user['city'],
    'province'    => $input['province'] ?? $user['province'],
    'postal_code' => $input['postal_code'] ?? $user['postal_code'],
    'avatar_url'  => $input['avatar_url'] ?? $user['avatar_url'],
];

$stmt = $db->prepare("
    UPDATE users 
    SET full_name = ?, phone = ?, address = ?, city = ?, province = ?, postal_code = ?, avatar_url = ?
    WHERE id = ?
");
$stmt->execute([
    $fields['full_name'],
    $fields['phone'],
    $fields['address'],
    $fields['city'],
    $fields['province'],
    $fields['postal_code'],
    $fields['avatar_url'],
    $user['id']
]);

$fetchStmt = $db->prepare("SELECT id, email, full_name, phone, address, city, province, postal_code, avatar_url, role, created_at, updated_at FROM users WHERE id = ?");
$fetchStmt->execute([$user['id']]);
$updatedUser = $fetchStmt->fetch();

jsonResponse(['user' => $updatedUser], 200, 'Profil berhasil diperbarui');

