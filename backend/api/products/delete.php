<?php
// backend/api/products/delete.php

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/auth.php';

if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'DELETE'])) {
    errorResponse('Method Not Allowed', 405);
}

requireAdmin();
$input = getJsonInput();
$id = $input['id'] ?? ($_GET['id'] ?? null);

if (!$id) {
    errorResponse('ID produk wajib disertakan.', 400);
}

$db = Database::getConnection();
$stmt = $db->prepare("DELETE FROM products WHERE id = ?");
$stmt->execute([(int)$id]);

jsonResponse(['success' => true], 200, 'Produk berhasil dihapus');

