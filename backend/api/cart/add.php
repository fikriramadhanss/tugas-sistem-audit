<?php
// backend/api/cart/add.php

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Method Not Allowed', 405);
}

$user = requireAuth();
$input = getJsonInput();

$productId = (int)($input['product_id'] ?? 0);
$quantity  = max(1, (int)($input['quantity'] ?? 1));

if ($productId <= 0) {
    errorResponse('Product ID tidak valid.', 422);
}

$db = Database::getConnection();

// Pastikan produk ada dan stok cukup
$pStmt = $db->prepare("SELECT id, name, stock, status FROM products WHERE id = ?");
$pStmt->execute([$productId]);
$product = $pStmt->fetch();

if (!$product) {
    errorResponse('Produk tidak ditemukan.', 404);
}

if ($product['status'] === 'out_of_stock' || $product['stock'] < 1) {
    errorResponse('Maaf, produk ini sedang habis.', 400);
}

// Cek apakah item sudah ada di keranjang
$checkStmt = $db->prepare("SELECT id, quantity FROM carts WHERE user_id = ? AND product_id = ?");
$checkStmt->execute([$user['id'], $productId]);
$existing = $checkStmt->fetch();

if ($existing) {
    $newQty = $existing['quantity'] + $quantity;
    if ($newQty > $product['stock']) {
        errorResponse("Stok tidak mencukupi. Maksimal stok: {$product['stock']}", 400);
    }
    $upStmt = $db->prepare("UPDATE carts SET quantity = ? WHERE id = ?");
    $upStmt->execute([$newQty, $existing['id']]);
} else {
    if ($quantity > $product['stock']) {
        errorResponse("Stok tidak mencukupi. Maksimal stok: {$product['stock']}", 400);
    }
    $inStmt = $db->prepare("INSERT INTO carts (user_id, product_id, quantity) VALUES (?, ?, ?)");
    $inStmt->execute([$user['id'], $productId, $quantity]);
}

jsonResponse(['success' => true], 200, 'Produk berhasil ditambahkan ke keranjang');

