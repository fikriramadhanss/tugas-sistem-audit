<?php
// backend/api/products/index.php

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/auth.php';

$method = $_SERVER['REQUEST_METHOD'];
$db = Database::getConnection();

if ($method === 'GET') {
    $featured = isset($_GET['featured']) ? (int)$_GET['featured'] : null;
    $categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;
    $categorySlug = isset($_GET['category']) ? trim($_GET['category']) : null;
    $search = isset($_GET['search']) ? trim($_GET['search']) : null;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null;
    $sort = $_GET['sort'] ?? 'newest';

    $where = [];
    $params = [];

    if ($featured !== null) {
        $where[] = "p.is_featured = ?";
        $params[] = $featured;
    }

    if ($categoryId) {
        $where[] = "p.category_id = ?";
        $params[] = $categoryId;
    }

    if ($categorySlug) {
        $where[] = "c.slug = ?";
        $params[] = $categorySlug;
    }

    if ($search) {
        $where[] = "(p.name LIKE ? OR p.brand LIKE ? OR p.description LIKE ?)";
        $searchTerm = "%{$search}%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

    $orderSql = "ORDER BY p.created_at DESC";
    if ($sort === 'price_asc') {
        $orderSql = "ORDER BY p.price ASC";
    } elseif ($sort === 'price_desc') {
        $orderSql = "ORDER BY p.price DESC";
    } elseif ($sort === 'name_asc') {
        $orderSql = "ORDER BY p.name ASC";
    }

    $limitSql = $limit ? "LIMIT {$limit}" : "";

    $sql = "
        SELECT 
            p.*,
            c.name AS category_name,
            c.slug AS category_slug,
            c.description AS category_description,
            (SELECT COUNT(*) FROM reviews r WHERE r.product_id = p.id) AS review_count,
            (SELECT AVG(r.rating) FROM reviews r WHERE r.product_id = p.id) AS avg_rating
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        {$whereSql}
        {$orderSql}
        {$limitSql}
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();

    // Format fields (decode JSON images, numeric casting, format category object)
    $formatted = array_map(function ($p) {
        $images = !empty($p['images']) ? json_decode($p['images'], true) : [];
        if (!is_array($images)) {
            $images = !empty($p['images']) ? [$p['images']] : [];
        }

        return [
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
            'avg_rating'       => $p['avg_rating'] !== null ? round((float)$p['avg_rating'], 1) : null,
            'review_count'     => (int)$p['review_count'],
            'reviews'          => $p['review_count'] > 0 ? array_fill(0, (int)$p['review_count'], ['rating' => round((float)$p['avg_rating'])]) : [],
        ];
    }, $products);

    jsonResponse($formatted);
}

if ($method === 'POST') {
    requireAdmin();
    $input = getJsonInput();

    $name = trim($input['name'] ?? '');
    if (empty($name)) {
        errorResponse('Nama produk wajib diisi.', 422);
    }

    $slug = trim($input['slug'] ?? '');
    if (empty($slug)) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
    }

    // Pastikan slug unik
    $checkSlug = $db->prepare("SELECT id FROM products WHERE slug = ? LIMIT 1");
    $checkSlug->execute([$slug]);
    if ($checkSlug->fetch()) {
        $slug .= '-' . time();
    }

    $images = $input['images'] ?? [];
    if (is_string($images)) {
        $images = [$images];
    }

    $stmt = $db->prepare("
        INSERT INTO products (
            name, slug, description, price, discount_percent, stock,
            brand, aroma_notes, volume, status, category_id, images, is_featured
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $name,
        $slug,
        $input['description'] ?? null,
        (float)($input['price'] ?? 0),
        (int)($input['discount_percent'] ?? 0),
        (int)($input['stock'] ?? 0),
        $input['brand'] ?? null,
        $input['aroma_notes'] ?? null,
        $input['volume'] ?? null,
        $input['status'] ?? 'ready',
        !empty($input['category_id']) ? (int)$input['category_id'] : null,
        json_encode($images, JSON_UNESCAPED_SLASHES),
        !empty($input['is_featured']) ? 1 : 0,
    ]);

    $newId = $db->lastInsertId();
    jsonResponse(['id' => $newId, 'slug' => $slug], 201, 'Produk berhasil ditambahkan');
}

errorResponse('Method Not Allowed', 405);

