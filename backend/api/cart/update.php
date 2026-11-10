<?php
// backend/api/cart/update.php

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/auth.php';

if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'PATCH'])) {
    errorResponse('Method Not Allowed', 405);
}

$user = requireAuth();
$input = getJsonInput();

$cartId = (int)($input['id'] ?? 0);
$quantity = (int)($input['quantity'] ?? 0);

if ($cartId <= 0 || $quantity < 1) {
    errorResponse('Parameter id atau quantity tidak valid.', 422);
}

$db = Database::getConnection();

// Ambil info item dan stok produk
$stmt = $db->prepare("
    SELECT c.id, c.user_id, p.stock 
    FROM carts c
    JOIN products p ON c.product_id = p.id
    WHERE c.id = ? AND c.user_id = ?
");
$stmt->execute([$cartId, $user['id']]);
$item = $stmt->fetch();

if (!$item) {
    errorResponse('Item keranjang tidak ditemukan.', 404);
}

if ($quantity > $item['stock']) {
    errorResponse("Jumlah melebihi stok yang tersedia ({$item['stock']}).", 400);
}

$updateStmt = $db->prepare("UPDATE carts SET quantity = ? WHERE id = ?");
$updateStmt->execute([$quantity, $cartId]);

jsonResponse(['success' => true, 'quantity' => $quantity], 200, 'Jumlah berhasil diperbarui');

