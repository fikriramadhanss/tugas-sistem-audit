<?php
// backend/api/products/update.php

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/auth.php';

if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT'])) {
    errorResponse('Method Not Allowed', 405);
}

requireAdmin();
$input = getJsonInput();

$id = $input['id'] ?? ($_GET['id'] ?? null);
if (!$id) {
    errorResponse('ID produk wajib disertakan.', 400);
}

$db = Database::getConnection();

// Cek produk ada
$check = $db->prepare("SELECT * FROM products WHERE id = ?");
$check->execute([(int)$id]);
$current = $check->fetch();
if (!$current) {
    errorResponse('Produk tidak ditemukan.', 404);
}

$images = $input['images'] ?? json_decode($current['images'] ?: '[]', true);
if (is_string($images)) {
    $images = [$images];
}

$stmt = $db->prepare("
    UPDATE products SET
        name = ?,
        slug = ?,
        description = ?,
        price = ?,
        discount_percent = ?,
        stock = ?,
        brand = ?,
        aroma_notes = ?,
        volume = ?,
        status = ?,
        category_id = ?,
        images = ?,
        is_featured = ?
    WHERE id = ?
");

$stmt->execute([
    $input['name'] ?? $current['name'],
    $input['slug'] ?? $current['slug'],
    $input['description'] ?? $current['description'],
    isset($input['price']) ? (float)$input['price'] : (float)$current['price'],
    isset($input['discount_percent']) ? (int)$input['discount_percent'] : (int)$current['discount_percent'],
    isset($input['stock']) ? (int)$input['stock'] : (int)$current['stock'],
    $input['brand'] ?? $current['brand'],
    $input['aroma_notes'] ?? $current['aroma_notes'],
    $input['volume'] ?? $current['volume'],
    $input['status'] ?? $current['status'],
    isset($input['category_id']) && $input['category_id'] !== '' ? (int)$input['category_id'] : null,
    json_encode($images, JSON_UNESCAPED_SLASHES),
    isset($input['is_featured']) ? ((bool)$input['is_featured'] ? 1 : 0) : (int)$current['is_featured'],
    (int)$id,
]);

jsonResponse(['success' => true], 200, 'Produk berhasil diperbarui');

