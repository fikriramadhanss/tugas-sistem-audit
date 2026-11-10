<?php
// backend/api/auth/register.php

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Method Not Allowed', 405);
}

$input = getJsonInput();
$email = trim(filter_var($input['email'] ?? '', FILTER_SANITIZE_EMAIL));
$password = $input['password'] ?? '';
$fullName = trim($input['full_name'] ?? '');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    errorResponse('Format email tidak valid.', 422);
}

if (strlen($password) < 6) {
    errorResponse('Password minimal 6 karakter.', 422);
}

$db = Database::getConnection();

// Cek apakah email sudah terdaftar
$checkStmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
$checkStmt->execute([$email]);
if ($checkStmt->fetch()) {
    errorResponse('Email sudah terdaftar. Silakan gunakan email lain atau login.', 409);
}

$userId = 'usr-' . bin2hex(random_bytes(8));
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

$insertStmt = $db->prepare("
    INSERT INTO users (id, email, password, full_name, role)
    VALUES (?, ?, ?, ?, 'user')
");
$insertStmt->execute([$userId, $email, $hashedPassword, $fullName ?: null]);

// Ambil data user baru
$getStmt = $db->prepare("SELECT id, email, full_name, phone, address, city, province, postal_code, avatar_url, role, created_at FROM users WHERE id = ?");
$getStmt->execute([$userId]);
$newUser = $getStmt->fetch();

$token = generateJwt([
    'sub'   => $newUser['id'],
    'email' => $newUser['email'],
    'role'  => $newUser['role'],
]);

jsonResponse([
    'token' => $token,
    'user'  => $newUser,
], 201, 'Registrasi berhasil');

