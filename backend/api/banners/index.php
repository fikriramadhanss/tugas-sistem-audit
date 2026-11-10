<?php
// backend/api/banners/index.php

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/auth.php';

$method = $_SERVER['REQUEST_METHOD'];
$db = Database::getConnection();

if ($method === 'GET') {
    $stmt = $db->query("SELECT * FROM banners ORDER BY sort_order ASC, id ASC");
    $banners = $stmt->fetchAll();

    $formatted = array_map(function ($b) {
        return [
            'id'         => (string)$b['id'],
            'image_url'  => $b['image_url'],
            'sort_order' => (int)$b['sort_order'],
            'created_at' => $b['created_at'],
        ];
    }, $banners);

    jsonResponse($formatted);
}

if ($method === 'POST') {
    requireAdmin();
    $input = getJsonInput();

    $imageUrl = trim($input['image_url'] ?? '');
    if (empty($imageUrl)) {
        errorResponse('Image URL atau base64 banner wajib diisi.', 422);
    }

    $sortOrder = isset($input['sort_order']) ? (int)$input['sort_order'] : 0;

    $stmt = $db->prepare("INSERT INTO banners (image_url, sort_order) VALUES (?, ?)");
    $stmt->execute([$imageUrl, $sortOrder]);

    jsonResponse(['id' => (string)$db->lastInsertId()], 201, 'Banner berhasil ditambahkan');
}

if ($method === 'DELETE') {
    requireAdmin();
    $input = getJsonInput();
    $id = $input['id'] ?? ($_GET['id'] ?? null);

    if (!$id) {
        errorResponse('ID banner wajib disertakan.', 400);
    }

    $stmt = $db->prepare("DELETE FROM banners WHERE id = ?");
    $stmt->execute([(int)$id]);

    jsonResponse(['success' => true], 200, 'Banner berhasil dihapus');
}

errorResponse('Method Not Allowed', 405);

