<?php
// backend/api/checkout/place-order.php

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Method Not Allowed', 405);
}

$user = requireAuth();
$input = getJsonInput();

$shippingName     = trim($input['shipping_name'] ?? '');
$shippingPhone    = trim($input['shipping_phone'] ?? '');
$shippingAddress  = trim($input['shipping_address'] ?? '');
$shippingCity     = trim($input['shipping_city'] ?? '');
$shippingProvince = trim($input['shipping_province'] ?? '');
$shippingPostal   = trim($input['shipping_postal_code'] ?? '');
$notes            = trim($input['notes'] ?? '');
$shippingCost     = max(0, (float)($input['shipping_cost'] ?? 0));

if (empty($shippingName) || empty($shippingPhone) || empty($shippingAddress) || empty($shippingCity)) {
    errorResponse('Data pengiriman (nama, telepon, alamat, kota) wajib diisi lengkap.', 422);
}

$db = Database::getConnection();

// 1. Ambil cart items langsung dari database
$cartStmt = $db->prepare("
    SELECT c.id AS cart_id, c.quantity, p.id AS product_id, p.name, p.price, p.discount_percent, p.stock, p.status
    FROM carts c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
");
$cartStmt->execute([$user['id']]);
$cartItems = $cartStmt->fetchAll();

if (empty($cartItems)) {
    errorResponse('Keranjang belanja Anda kosong.', 400);
}

// 2. Validasi stok untuk setiap item
foreach ($cartItems as $item) {
    if ($item['status'] === 'out_of_stock' || $item['stock'] < $item['quantity']) {
        errorResponse("Stok untuk produk '{$item['name']}' tidak mencukupi (tersedia: {$item['stock']}).", 400);
    }
}

// 3. Hitung total di server
$itemsTotal = 0;
$orderItemsData = [];

foreach ($cartItems as $item) {
    $price = (float)$item['price'];
    $discount = (int)$item['discount_percent'];
    $finalPrice = $discount > 0 ? $price * (1 - $discount / 100) : $price;
    $subtotal = $finalPrice * $item['quantity'];

    $itemsTotal += $subtotal;
    $orderItemsData[] = [
        'product_id'    => $item['product_id'],
        'product_name'  => $item['name'],
        'product_price' => $finalPrice,
        'quantity'      => (int)$item['quantity'],
        'subtotal'      => $subtotal,
    ];
}

$grandTotal = $itemsTotal + $shippingCost;
$orderId = 'ORD-' . strtoupper(bin2hex(random_bytes(4))) . '-' . date('Ymd');

// 4. Database Transaction
try {
    $db->beginTransaction();

    // Insert order
    $orderStmt = $db->prepare("
        INSERT INTO orders (
            id, user_id, status, total_amount, shipping_name, shipping_phone,
            shipping_address, shipping_city, shipping_province, shipping_postal_code, notes
        ) VALUES (?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $orderStmt->execute([
        $orderId,
        $user['id'],
        $grandTotal,
        $shippingName,
        $shippingPhone,
        $shippingAddress,
        $shippingCity,
        $shippingProvince,
        $shippingPostal,
        $notes ?: null,
    ]);

    // Insert order items & kurangi stok
    $itemInsertStmt = $db->prepare("
        INSERT INTO order_items (order_id, product_id, product_name, product_price, quantity, subtotal)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stockUpdateStmt = $db->prepare("
        UPDATE products 
        SET stock = stock - ?, status = CASE WHEN stock - ? <= 0 THEN 'out_of_stock' ELSE status END
        WHERE id = ?
    ");

    foreach ($orderItemsData as $oi) {
        $itemInsertStmt->execute([
            $orderId,
            $oi['product_id'],
            $oi['product_name'],
            $oi['product_price'],
            $oi['quantity'],
            $oi['subtotal'],
        ]);

        $stockUpdateStmt->execute([
            $oi['quantity'],
            $oi['quantity'],
            $oi['product_id'],
        ]);
    }

    // Bersihkan cart user
    $clearCartStmt = $db->prepare("DELETE FROM carts WHERE user_id = ?");
    $clearCartStmt->execute([$user['id']]);

    $db->commit();

    jsonResponse([
        'order_id'     => $orderId,
        'total_amount' => $grandTotal,
        'items'        => $orderItemsData,
        'shipping'     => [
            'name'    => $shippingName,
            'phone'   => $shippingPhone,
            'address' => $shippingAddress,
            'city'    => $shippingCity,
        ],
    ], 201, 'Pesanan berhasil dibuat');

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    errorResponse('Gagal memproses pesanan: ' . $e->getMessage(), 500);
}

