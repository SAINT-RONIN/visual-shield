<?php

namespace App\Framework;

use App\Services\AuthService;
use App\Repositories\UserRepository;
use App\Repositories\TokenRepository;

class AuthMiddleware
{
    public static function authenticate(): int
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = self::extractBearerToken($header);

        if (!$token) {
            self::sendUnauthorized('Missing or invalid Authorization header');
        }

        $authService = new AuthService(new UserRepository(), new TokenRepository());
        $user = $authService->getUserFromToken($token);

        if (!$user) {
            self::sendUnauthorized('Invalid or expired token');
        }

        return (int) $user['id'];
    }

    public static function extractToken(): string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = self::extractBearerToken($header);

        if (!$token) {
            self::sendUnauthorized('Missing or invalid Authorization header');
        }

        return $token;
    }

    private static function extractBearerToken(string $header): ?string
    {
        if (preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return $matches[1];
        }
        return null;
    }

    private static function sendUnauthorized(string $message): never
    {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => ['code' => 401, 'message' => $message]]);
        exit;
    }
}
