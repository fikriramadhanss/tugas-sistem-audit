<?php
// backend/api/categories/detail.php

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/auth.php';

$method = $_SERVER['REQUEST_METHOD'];
requireAdmin();

$db = Database::getConnection();
$input = getJsonInput();
$id = $input['id'] ?? ($_GET['id'] ?? null);

if (!$id) {
    errorResponse('ID kategori wajib disertakan.', 400);
}

if (in_array($method, ['PUT', 'POST']) && isset($input['name'])) {
    $name = trim($input['name']);
    $slug = trim($input['slug'] ?? strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-')));

    $stmt = $db->prepare("
        UPDATE categories 
        SET name = ?, slug = ?, description = ?, image_url = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $name,
        $slug,
        $input['description'] ?? null,
        $input['image_url'] ?? null,
        (int)$id,
    ]);

    jsonResponse(['success' => true], 200, 'Kategori berhasil diperbarui');
}

if ($method === 'DELETE' || (isset($input['_method']) && $input['_method'] === 'DELETE')) {
    $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([(int)$id]);
    jsonResponse(['success' => true], 200, 'Kategori berhasil dihapus');
}

errorResponse('Method Not Allowed', 405);

