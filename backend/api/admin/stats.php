<?php
// backend/api/admin/stats.php

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    errorResponse('Method Not Allowed', 405);
}

// Hanya admin / superadmin
requireAdmin();

$db = Database::getConnection();

// Total produk
$prodCount = (int)$db->query("SELECT COUNT(*) FROM products")->fetchColumn();

// Total order & pending order & total pendapatan (delivered / paid)
$orderCount = (int)$db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$pendingCount = (int)$db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
$revenue = (float)$db->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status IN ('delivered', 'paid', 'shipped')")->fetchColumn();

// Total user
$userCount = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();

// 5 pesanan terbaru
$recentStmt = $db->query("
    SELECT o.id, o.total_amount, o.status, o.created_at, u.full_name AS user_name, u.email AS user_email
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
    LIMIT 5
");
$recentOrders = $recentStmt->fetchAll();

$formattedRecent = array_map(function ($o) {
    return [
        'id'           => $o['id'],
        'total_amount' => (float)$o['total_amount'],
        'status'       => $o['status'],
        'created_at'   => $o['created_at'],
        'profiles'     => [
            'full_name' => $o['user_name'] ?: $o['user_email'],
        ],
    ];
}, $recentOrders);

jsonResponse([
    'total_products' => $prodCount,
    'total_orders'   => $orderCount,
    'pending_orders' => $pendingCount,
    'total_revenue'  => $revenue,
    'total_users'    => $userCount,
    'recent_orders'  => $formattedRecent,
]);

