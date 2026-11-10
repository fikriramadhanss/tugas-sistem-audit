<?php
// backend/api/orders/detail.php

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    errorResponse('Method Not Allowed', 405);
}

$user = requireAuth();
$orderId = $_GET['id'] ?? null;

if (!$orderId) {
    errorResponse('ID pesanan wajib disertakan.', 400);
}

$db = Database::getConnection();

$stmt = $db->prepare("
    SELECT o.*, u.email AS user_email, u.full_name AS user_name,
           s.courier, s.tracking_number, s.shipped_at, s.delivered_at
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN shipments s ON o.id = s.order_id
    WHERE o.id = ?
    LIMIT 1
");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    errorResponse('Pesanan tidak ditemukan.', 404);
}

// Otorisasi: hanya pemilik atau admin
if ($user['role'] !== 'admin' && $order['user_id'] !== $user['id']) {
    errorResponse('Akses ditolak: Anda tidak memiliki akses ke pesanan ini.', 403);
}

// Ambil order items
$itemStmt = $db->prepare("
    SELECT oi.*, p.slug AS product_slug, p.images
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$itemStmt->execute([$orderId]);
$items = $itemStmt->fetchAll();

$formattedItems = array_map(function ($oi) {
    $images = !empty($oi['images']) ? json_decode($oi['images'], true) : [];
    if (!is_array($images)) {
        $images = !empty($oi['images']) ? [$oi['images']] : [];
    }

    return [
        'id'            => (string)$oi['id'],
        'order_id'      => $oi['order_id'],
        'product_id'    => $oi['product_id'] ? (string)$oi['product_id'] : null,
        'product_name'  => $oi['product_name'],
        'product_price' => (float)$oi['product_price'],
        'quantity'      => (int)$oi['quantity'],
        'subtotal'      => (float)$oi['subtotal'],
        'product_slug'  => $oi['product_slug'],
        'images'        => $images,
    ];
}, $items);

$shipment = $order['courier'] || $order['tracking_number'] ? [
    'order_id'        => $order['id'],
    'courier'         => $order['courier'],
    'tracking_number' => $order['tracking_number'],
    'shipped_at'      => $order['shipped_at'],
    'delivered_at'    => $order['delivered_at'],
] : null;

jsonResponse([
    'id'                   => $order['id'],
    'user_id'              => $order['user_id'],
    'status'               => $order['status'],
    'total_amount'         => (float)$order['total_amount'],
    'shipping_name'        => $order['shipping_name'],
    'shipping_phone'       => $order['shipping_phone'],
    'shipping_address'     => $order['shipping_address'],
    'shipping_city'        => $order['shipping_city'],
    'shipping_province'    => $order['shipping_province'],
    'shipping_postal_code' => $order['shipping_postal_code'],
    'notes'                => $order['notes'],
    'created_at'           => $order['created_at'],
    'updated_at'           => $order['updated_at'],
    'user'                 => [
        'email'     => $order['user_email'],
        'full_name' => $order['user_name'],
    ],
    'order_items'          => $formattedItems,
    'shipment'             => $shipment,
]);

