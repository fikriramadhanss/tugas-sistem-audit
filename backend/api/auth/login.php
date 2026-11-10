<?php
// backend/api/auth/login.php

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Method Not Allowed', 405);
}

$input = getJsonInput();
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if (empty($email) || empty($password)) {
    errorResponse('Email dan password wajib diisi.', 422);
}

$db = Database::getConnection();
$stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    errorResponse('Email atau password salah.', 401);
}

$token = generateJwt([
    'sub'   => $user['id'],
    'email' => $user['email'],
    'role'  => $user['role'],
]);

unset($user['password']);

jsonResponse([
    'token' => $token,
    'user'  => $user,
], 200, 'Login berhasil');

