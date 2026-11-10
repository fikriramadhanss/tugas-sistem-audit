<?php
// backend/api/orders/delete.php

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/auth.php';

if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'DELETE'])) {
    errorResponse('Method Not Allowed', 405);
}

requireAdmin();
$input = getJsonInput();
$orderId = $input['order_id'] ?? ($input['id'] ?? ($_GET['id'] ?? null));

if (!$orderId) {
    errorResponse('ID pesanan wajib disertakan.', 400);
}

$db = Database::getConnection();

// Delete order (relasi shipments & order_items otomatis cascade via DB foreign keys)
$stmt = $db->prepare("DELETE FROM orders WHERE id = ?");
$stmt->execute([$orderId]);

jsonResponse(['success' => true], 200, 'Pesanan berhasil dihapus');

