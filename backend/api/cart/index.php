<?php
// backend/api/cart/index.php

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    errorResponse('Method Not Allowed', 405);
}

$user = requireAuth();
$db = Database::getConnection();

$stmt = $db->prepare("
    SELECT 
        c.id, c.user_id, c.product_id, c.quantity, c.created_at,
        p.name AS product_name, p.slug AS product_slug, p.price AS product_price,
        p.discount_percent, p.stock, p.status, p.brand, p.volume, p.images
    FROM carts c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
    ORDER BY c.created_at DESC
");
$stmt->execute([$user['id']]);
$items = $stmt->fetchAll();

$formatted = array_map(function ($item) {
    $images = !empty($item['images']) ? json_decode($item['images'], true) : [];
    if (!is_array($images)) {
        $images = !empty($item['images']) ? [$item['images']] : [];
    }

    return [
        'id'         => (string)$item['id'],
        'user_id'    => $item['user_id'],
        'product_id' => (string)$item['product_id'],
        'quantity'   => (int)$item['quantity'],
        'created_at' => $item['created_at'],
        'product'    => [
            'id'               => (string)$item['product_id'],
            'name'             => $item['product_name'],
            'slug'             => $item['product_slug'],
            'price'            => (float)$item['product_price'],
            'discount_percent' => (int)$item['discount_percent'],
            'stock'            => (int)$item['stock'],
            'status'           => $item['status'],
            'brand'            => $item['brand'],
            'volume'           => $item['volume'],
            'images'           => $images,
        ],
    ];
}, $items);

jsonResponse($formatted);

