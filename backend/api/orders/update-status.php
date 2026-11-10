<?php
// backend/api/orders/update-status.php

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/auth.php';

if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'PATCH'])) {
    errorResponse('Method Not Allowed', 405);
}

requireAdmin();
$input = getJsonInput();

$orderId = $input['order_id'] ?? ($input['id'] ?? null);
$status  = $input['status'] ?? null;

$validStatuses = ['pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled'];
if (!$orderId || !in_array($status, $validStatuses)) {
    errorResponse('Status pesanan tidak valid.', 422);
}

$db = Database::getConnection();
$stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
$stmt->execute([$status, $orderId]);

jsonResponse(['success' => true, 'status' => $status], 200, 'Status pesanan berhasil diperbarui');

