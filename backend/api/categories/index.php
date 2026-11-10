<?php
// backend/api/categories/index.php

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/auth.php';

$method = $_SERVER['REQUEST_METHOD'];
$db = Database::getConnection();

if ($method === 'GET') {
    $stmt = $db->query("
        SELECT c.*, COUNT(p.id) AS product_count
        FROM categories c
        LEFT JOIN products p ON c.id = p.category_id
        GROUP BY c.id
        ORDER BY c.name ASC
    ");
    $categories = $stmt->fetchAll();

    $formatted = array_map(function ($c) {
        return [
            'id'            => (string)$c['id'],
            'name'          => $c['name'],
            'slug'          => $c['slug'],
            'description'   => $c['description'],
            'image_url'     => $c['image_url'],
            'created_at'    => $c['created_at'],
            'product_count' => (int)$c['product_count'],
        ];
    }, $categories);

    jsonResponse($formatted);
}

if ($method === 'POST') {
    requireAdmin();
    $input = getJsonInput();

    $name = trim($input['name'] ?? '');
    if (empty($name)) {
        errorResponse('Nama kategori wajib diisi.', 422);
    }

    $slug = trim($input['slug'] ?? '');
    if (empty($slug)) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
    }

    $stmt = $db->prepare("
        INSERT INTO categories (name, slug, description, image_url)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
        $name,
        $slug,
        $input['description'] ?? null,
        $input['image_url'] ?? null,
    ]);

    $id = $db->lastInsertId();
    jsonResponse(['id' => (string)$id, 'name' => $name, 'slug' => $slug], 201, 'Kategori berhasil ditambahkan');
}

errorResponse('Method Not Allowed', 405);

