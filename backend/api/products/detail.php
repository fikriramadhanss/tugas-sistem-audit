<?php
// backend/api/products/detail.php

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    errorResponse('Method Not Allowed', 405);
}

$slug = $_GET['slug'] ?? null;
$id = $_GET['id'] ?? null;

if (!$slug && !$id) {
    errorResponse('Parameter slug atau id dibutuhkan.', 400);
}

$db = Database::getConnection();

if ($slug) {
    $stmt = $db->prepare("
        SELECT p.*, c.name AS category_name, c.slug AS category_slug, c.description AS category_description
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.slug = ?
        LIMIT 1
    ");
    $stmt->execute([$slug]);
} else {
    $stmt = $db->prepare("
        SELECT p.*, c.name AS category_name, c.slug AS category_slug, c.description AS category_description
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.id = ?
        LIMIT 1
    ");
    $stmt->execute([(int)$id]);
}

$p = $stmt->fetch();

if (!$p) {
    errorResponse('Produk tidak ditemukan.', 404);
}

// Ambil review untuk produk ini
$revStmt = $db->prepare("
    SELECT r.id, r.product_id, r.user_id, r.rating, r.comment, r.created_at,
           u.full_name, u.avatar_url
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.product_id = ?
    ORDER BY r.created_at DESC
");
$revStmt->execute([$p['id']]);
$reviews = $revStmt->fetchAll();

$formattedReviews = array_map(function ($r) {
    return [
        'id'         => (string)$r['id'],
        'product_id' => (string)$r['product_id'],
        'user_id'    => (string)$r['user_id'],
        'rating'     => (int)$r['rating'],
        'comment'    => $r['comment'],
        'created_at' => $r['created_at'],
        'user'       => [
            'full_name'  => $r['full_name'],
            'avatar_url' => $r['avatar_url'],
        ],
    ];
}, $reviews);

$images = !empty($p['images']) ? json_decode($p['images'], true) : [];
if (!is_array($images)) {
    $images = !empty($p['images']) ? [$p['images']] : [];
}

$avgRating = count($reviews) > 0 ? array_sum(array_column($reviews, 'rating')) / count($reviews) : null;

$product = [
    'id'               => (string)$p['id'],
    'name'             => $p['name'],
    'slug'             => $p['slug'],
    'description'      => $p['description'],
    'price'            => (float)$p['price'],
    'discount_percent' => (int)$p['discount_percent'],
    'stock'            => (int)$p['stock'],
    'brand'            => $p['brand'],
    'aroma_notes'      => $p['aroma_notes'],
    'volume'           => $p['volume'],
    'status'           => $p['status'],
    'category_id'      => $p['category_id'] ? (string)$p['category_id'] : null,
    'images'           => $images,
    'is_featured'      => (bool)$p['is_featured'],
    'created_at'       => $p['created_at'],
    'updated_at'       => $p['updated_at'],
    'category'         => $p['category_id'] ? [
        'id'          => (string)$p['category_id'],
        'name'        => $p['category_name'],
        'slug'        => $p['category_slug'],
        'description' => $p['category_description'],
    ] : null,
    'avg_rating'       => $avgRating !== null ? round($avgRating, 1) : null,
    'review_count'     => count($reviews),
    'reviews'          => $formattedReviews,
];

jsonResponse($product);

