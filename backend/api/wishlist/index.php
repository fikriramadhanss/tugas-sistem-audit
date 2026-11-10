<?php
// backend/api/wishlist/index.php

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/auth.php';

$user = requireAuth();
$method = $_SERVER['REQUEST_METHOD'];
$db = Database::getConnection();

if ($method === 'GET') {
    $stmt = $db->prepare("
        SELECT 
            w.id, w.user_id, w.product_id, w.created_at,
            p.name AS product_name, p.slug AS product_slug, p.price AS product_price,
            p.discount_percent, p.stock, p.status, p.brand, p.volume, p.images
        FROM wishlist w
        JOIN products p ON w.product_id = p.id
        WHERE w.user_id = ?
        ORDER BY w.created_at DESC
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
}

if ($method === 'POST') {
    $input = getJsonInput();
    $productId = (int)($input['product_id'] ?? 0);

    if ($productId <= 0) {
        errorResponse('Product ID tidak valid.', 422);
    }

    // Cek apakah sudah ada di wishlist
    $check = $db->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $check->execute([$user['id'], $productId]);
    $existing = $check->fetch();

    if ($existing) {
        // Toggle: remove if already exists
        $del = $db->prepare("DELETE FROM wishlist WHERE id = ?");
        $del->execute([$existing['id']]);
        jsonResponse(['added' => false, 'message' => 'Dihapus dari wishlist']);
    } else {
        $add = $db->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
        $add->execute([$user['id'], $productId]);
        jsonResponse(['added' => true, 'message' => 'Ditambahkan ke wishlist'], 201);
    }
}

if ($method === 'DELETE') {
    $input = getJsonInput();
    $productId = $input['product_id'] ?? ($_GET['product_id'] ?? null);

    if (!$productId) {
        errorResponse('Product ID dibutuhkan.', 400);
    }

    $stmt = $db->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user['id'], (int)$productId]);

    jsonResponse(['success' => true], 200, 'Berhasil dihapus dari wishlist');
}

errorResponse('Method Not Allowed', 405);

