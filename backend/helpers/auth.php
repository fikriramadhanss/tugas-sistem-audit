<?php
// backend/helpers/auth.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/response.php';

const JWT_SECRET = 'parfum_secret_key_2026_super_secure_hash_x9182!';

function base64UrlEncode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64UrlDecode(string $data): string {
    return base64_decode(strtr($data, '-_', '+/'));
}

function generateJwt(array $payload, int $ttl = 604800): string {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload['iat'] = time();
    $payload['exp'] = time() + $ttl;
    $payloadJson = json_encode($payload);

    $base64Header = base64UrlEncode($header);
    $base64Payload = base64UrlEncode($payloadJson);

    $signature = hash_hmac('sha256', "{$base64Header}.{$base64Payload}", JWT_SECRET, true);
    $base64Signature = base64UrlEncode($signature);

    return "{$base64Header}.{$base64Payload}.{$base64Signature}";
}

function verifyJwt(string $token): ?array {
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return null;
    }

    [$base64Header, $base64Payload, $base64Signature] = $parts;

    $expectedSignature = base64UrlEncode(
        hash_hmac('sha256', "{$base64Header}.{$base64Payload}", JWT_SECRET, true)
    );

    if (!hash_equals($expectedSignature, $base64Signature)) {
        return null;
    }

    $payload = json_decode(base64UrlDecode($base64Payload), true);
    if (!$payload || !isset($payload['exp']) || $payload['exp'] < time()) {
        return null;
    }

    return $payload;
}

function getAuthorizationHeader(): ?string {
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        return trim($_SERVER['HTTP_AUTHORIZATION']);
    }
    if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        return trim($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
    }
    if (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        if (isset($headers['Authorization'])) {
            return trim($headers['Authorization']);
        }
        if (isset($headers['authorization'])) {
            return trim($headers['authorization']);
        }
    }
    return null;
}

function getBearerToken(): ?string {
    $header = getAuthorizationHeader();
    if ($header && preg_match('/Bearer\s+(\S+)/i', $header, $matches)) {
        return $matches[1];
    }
    return null;
}

function getAuthUser(): ?array {
    $token = getBearerToken();
    if (!$token) {
        return null;
    }

    $payload = verifyJwt($token);
    if (!$payload || !isset($payload['sub'])) {
        return null;
    }

    $userId = $payload['sub'];
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT id, email, full_name, phone, address, city, province, postal_code, avatar_url, role, created_at, updated_at FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function requireAuth(): array {
    $user = getAuthUser();
    if (!$user) {
        errorResponse('Sesi tidak valid atau telah berakhir. Silakan login kembali.', 401);
    }
    return $user;
}

function requireAdmin(): array {
    $user = requireAuth();
    if (!in_array($user['role'] ?? '', ['admin', 'superadmin'])) {
        errorResponse('Akses ditolak: Anda tidak memiliki izin administrator.', 403);
    }
    return $user;
}

