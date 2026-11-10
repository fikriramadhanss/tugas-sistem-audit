<?php
// backend/api/settings/index.php

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/auth.php';

$method = $_SERVER['REQUEST_METHOD'];
$db = Database::getConnection();

if ($method === 'GET') {
    $stmt = $db->query("SELECT `key`, `value` FROM store_settings");
    $rows = $stmt->fetchAll();

    $settings = [];
    foreach ($rows as $row) {
        $settings[$row['key']] = $row['value'];
    }

    jsonResponse($settings);
}

if ($method === 'POST') {
    requireAdmin();
    $input = getJsonInput();

    $allowedKeys = [
        'store_name',
        'store_description',
        'whatsapp',
        'email',
        'address',
        'open_hours',
        'instagram',
        'tiktok',
        'facebook',
        'twitter',
    ];

    $stmt = $db->prepare("
        INSERT INTO store_settings (`key`, `value`)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)
    ");

    foreach ($input as $k => $v) {
        if (in_array($k, $allowedKeys)) {
            $stmt->execute([$k, (string)$v]);
        }
    }

    jsonResponse(['success' => true], 200, 'Pengaturan toko berhasil disimpan');
}

errorResponse('Method Not Allowed', 405);

