<?php
// backend/api/orders/shipment.php

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Method Not Allowed', 405);
}

requireAdmin();
$input = getJsonInput();

$orderId = trim($input['order_id'] ?? '');
$courier = trim($input['courier'] ?? '');
$trackingNumber = trim($input['tracking_number'] ?? '');

if (empty($orderId) || empty($trackingNumber)) {
    errorResponse('Order ID dan nomor resi wajib diisi.', 422);
}

$db = Database::getConnection();

// Upsert shipment
$stmt = $db->prepare("
    INSERT INTO shipments (order_id, courier, tracking_number, shipped_at)
    VALUES (?, ?, ?, NOW())
    ON DUPLICATE KEY UPDATE 
        courier = VALUES(courier),
        tracking_number = VALUES(tracking_number),
        shipped_at = NOW()
");
$stmt->execute([$orderId, $courier, $trackingNumber]);

// Update order status menjadi shipped jika masih pending/processing
$upOrder = $db->prepare("UPDATE orders SET status = 'shipped' WHERE id = ? AND status IN ('pending', 'paid', 'processing')");
$upOrder->execute([$orderId]);

jsonResponse(['success' => true], 200, 'Nomor resi berhasil disimpan');

