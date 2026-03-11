<?php

namespace App\Framework;

class AuthMiddleware
{
    /**
     * Authenticate using the Authorization header (standard for API calls).
     *
     * Extracts the Bearer token from the header, validates it, and
     * returns the authenticated user's ID. Sends a 401 response and
     * exits if the token is missing or invalid.
     */
    public static function authenticate(): int
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = self::extractBearerToken($header);

        if (!$token) {
            self::sendUnauthorized('Missing or invalid Authorization header');
        }

        return self::resolveUserIdFromToken($token);
    }

    /**
     * Authenticate using the Authorization header OR a ?token= query parameter.
     *
     * HTML <video> elements cannot set custom headers on their media requests,
     * so the video streaming endpoint needs to also accept the token as a
     * query parameter. Tries the header first, falls back to the query param.
     */
    public static function authenticateFromHeaderOrQueryParam(): int
    {
        $token = self::extractTokenFromHeaderOrQueryParam();

        if (!$token) {
            self::sendUnauthorized('Unauthorized');
        }

        return self::resolveUserIdFromToken($token);
    }

    /** Extract the raw Bearer token string from the Authorization header. */
    public static function extractToken(): string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = self::extractBearerToken($header);

        if (!$token) {
            self::sendUnauthorized('Missing or invalid Authorization header');
        }

        return $token;
    }

    // ──────────────────────────────────────────────
    //  Token extraction helpers
    // ──────────────────────────────────────────────

    private static function extractBearerToken(string $header): ?string
    {
        if (preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /** Try the Authorization header first, then fall back to ?token= query param. */
    private static function extractTokenFromHeaderOrQueryParam(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $headerToken = self::extractBearerToken($header);

        if ($headerToken) {
            return $headerToken;
        }

        $queryToken = $_GET['token'] ?? '';

        return $queryToken !== '' ? $queryToken : null;
    }

    // ──────────────────────────────────────────────
    //  Token validation
    // ──────────────────────────────────────────────

    /** Validate a token and return the owning user's ID, or send 401. */
    private static function resolveUserIdFromToken(string $token): int
    {
        $user = ServiceRegistry::authService()->getUserFromToken($token);

        if (!$user) {
            self::sendUnauthorized('Invalid or expired token');
        }

        return $user->id;
    }

    private static function sendUnauthorized(string $message): never
    {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => ['code' => 401, 'message' => $message]]);
        exit;
    }
}
