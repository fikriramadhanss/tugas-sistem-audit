<?php
// backend/api/orders/index.php

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    errorResponse('Method Not Allowed', 405);
}

$user = requireAuth();
$db = Database::getConnection();

$status = $_GET['status'] ?? null;
$isAdmin = ($user['role'] === 'admin');
$viewAll = $isAdmin && isset($_GET['admin']) && $_GET['admin'] == '1';

$where = [];
$params = [];

if (!$viewAll) {
    // Regular customer: only view their own orders
    $where[] = "o.user_id = ?";
    $params[] = $user['id'];
}

if ($status) {
    $where[] = "o.status = ?";
    $params[] = $status;
}

$whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$sql = "
    SELECT 
        o.*,
        u.email AS user_email,
        u.full_name AS user_name,
        s.courier,
        s.tracking_number,
        s.shipped_at,
        s.delivered_at
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN shipments s ON o.id = s.order_id
    {$whereSql}
    ORDER BY o.created_at DESC
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Ambil items untuk setiap order
$orderIds = array_column($orders, 'id');
$itemsByOrder = [];

if (!empty($orderIds)) {
    $inPlaceholders = implode(',', array_fill(0, count($orderIds), '?'));
    $itemsStmt = $db->prepare("
        SELECT oi.*, p.slug AS product_slug, p.images
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id IN ({$inPlaceholders})
    ");
    $itemsStmt->execute($orderIds);
    $allOrderItems = $itemsStmt->fetchAll();

    foreach ($allOrderItems as $oi) {
        $itemsByOrder[$oi['order_id']][] = [
            'id'            => (string)$oi['id'],
            'order_id'      => $oi['order_id'],
            'product_id'    => $oi['product_id'] ? (string)$oi['product_id'] : null,
            'product_name'  => $oi['product_name'],
            'product_price' => (float)$oi['product_price'],
            'quantity'      => (int)$oi['quantity'],
            'subtotal'      => (float)$oi['subtotal'],
            'product_slug'  => $oi['product_slug'],
        ];
    }
}

$formatted = array_map(function ($o) use ($itemsByOrder) {
    $orderItems = $itemsByOrder[$o['id']] ?? [];

    $shipment = $o['courier'] || $o['tracking_number'] ? [
        'order_id'        => $o['id'],
        'courier'         => $o['courier'],
        'tracking_number' => $o['tracking_number'],
        'shipped_at'      => $o['shipped_at'],
        'delivered_at'    => $o['delivered_at'],
    ] : null;

    return [
        'id'                   => $o['id'],
        'user_id'              => $o['user_id'],
        'status'               => $o['status'],
        'total_amount'         => (float)$o['total_amount'],
        'shipping_name'        => $o['shipping_name'],
        'shipping_phone'       => $o['shipping_phone'],
        'shipping_address'     => $o['shipping_address'],
        'shipping_city'        => $o['shipping_city'],
        'shipping_province'    => $o['shipping_province'],
        'shipping_postal_code' => $o['shipping_postal_code'],
        'notes'                => $o['notes'],
        'created_at'           => $o['created_at'],
        'updated_at'           => $o['updated_at'],
        'user'                 => [
            'email'     => $o['user_email'],
            'full_name' => $o['user_name'],
        ],
        'order_items'          => $orderItems,
        'shipment'             => $shipment,
    ];
}, $orders);

jsonResponse($formatted);

