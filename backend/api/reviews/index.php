<?php
// backend/api/reviews/index.php

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/auth.php';

$method = $_SERVER['REQUEST_METHOD'];
$db = Database::getConnection();

if ($method === 'GET') {
    $productId = $_GET['product_id'] ?? null;
    $all = isset($_GET['all']) && $_GET['all'] == '1';

    if ($all) {
        // Admin overview: all reviews
        $stmt = $db->query("
            SELECT r.*, u.full_name AS user_name, u.avatar_url, p.name AS product_name, p.slug AS product_slug
            FROM reviews r
            LEFT JOIN users u ON r.user_id = u.id
            LEFT JOIN products p ON r.product_id = p.id
            ORDER BY r.created_at DESC
        ");
        $reviews = $stmt->fetchAll();

        $formatted = array_map(function ($r) {
            return [
                'id'         => (string)$r['id'],
                'product_id' => (string)$r['product_id'],
                'user_id'    => $r['user_id'],
                'rating'     => (int)$r['rating'],
                'comment'    => $r['comment'],
                'created_at' => $r['created_at'],
                'user'       => [
                    'full_name'  => $r['user_name'],
                    'avatar_url' => $r['avatar_url'],
                ],
                'product'    => [
                    'name' => $r['product_name'],
                    'slug' => $r['product_slug'],
                ],
            ];
        }, $reviews);

        jsonResponse($formatted);
    }

    if (!$productId) {
        errorResponse('Parameter product_id dibutuhkan.', 400);
    }

    $stmt = $db->prepare("
        SELECT r.*, u.full_name AS user_name, u.avatar_url
        FROM reviews r
        LEFT JOIN users u ON r.user_id = u.id
        WHERE r.product_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([(int)$productId]);
    $reviews = $stmt->fetchAll();

    $formatted = array_map(function ($r) {
        return [
            'id'         => (string)$r['id'],
            'product_id' => (string)$r['product_id'],
            'user_id'    => $r['user_id'],
            'rating'     => (int)$r['rating'],
            'comment'    => $r['comment'],
            'created_at' => $r['created_at'],
            'user'       => [
                'full_name'  => $r['user_name'],
                'avatar_url' => $r['avatar_url'],
            ],
        ];
    }, $reviews);

    jsonResponse($formatted);
}

if ($method === 'POST') {
    $user = requireAuth();
    $input = getJsonInput();

    $productId = (int)($input['product_id'] ?? 0);
    $rating = (int)($input['rating'] ?? 5);
    $comment = trim($input['comment'] ?? '');

    if ($productId <= 0 || $rating < 1 || $rating > 5) {
        errorResponse('Rating (1-5) dan Product ID wajib valid.', 422);
    }

    $stmt = $db->prepare("
        INSERT INTO reviews (product_id, user_id, rating, comment)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$productId, $user['id'], $rating, $comment ?: null]);

    jsonResponse(['id' => (string)$db->lastInsertId()], 201, 'Ulasan berhasil ditambahkan');
}

if ($method === 'DELETE') {
    requireAdmin();
    $input = getJsonInput();
    $reviewId = $input['id'] ?? ($_GET['id'] ?? null);

    if (!$reviewId) {
        errorResponse('ID review wajib disertakan.', 400);
    }

    $stmt = $db->prepare("DELETE FROM reviews WHERE id = ?");
    $stmt->execute([(int)$reviewId]);

    jsonResponse(['success' => true], 200, 'Ulasan berhasil dihapus');
}

errorResponse('Method Not Allowed', 405);

