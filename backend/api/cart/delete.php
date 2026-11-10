<?php
// backend/api/cart/delete.php

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/auth.php';

if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'DELETE'])) {
    errorResponse('Method Not Allowed', 405);
}

$user = requireAuth();
$input = getJsonInput();
$cartId = $input['id'] ?? ($_GET['id'] ?? null);
$clearAll = !empty($input['clear_all']) || (isset($_GET['clear_all']) && $_GET['clear_all'] == '1');

$db = Database::getConnection();

if ($clearAll) {
    $stmt = $db->prepare("DELETE FROM carts WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    jsonResponse(['success' => true], 200, 'Keranjang berhasil dikosongkan');
}

if (!$cartId) {
    errorResponse('ID item keranjang diperlukan.', 400);
}

$stmt = $db->prepare("DELETE FROM carts WHERE id = ? AND user_id = ?");
$stmt->execute([(int)$cartId, $user['id']]);

jsonResponse(['success' => true], 200, 'Item berhasil dihapus dari keranjang');

