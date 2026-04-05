<?php

declare(strict_types=1);

namespace App\Framework;

use App\Exceptions\UnauthorizedException;

/**
 * Stateless authentication middleware that validates bearer tokens.
 *
 * Extracts tokens from either the Authorization header or a query
 * parameter, resolves them to a user via AuthService, and caches
 * the authenticated user's role for downstream permission checks.
 *
 * All authentication methods are called inside controller methods that
 * are wrapped in BaseController::handleRequest(). Throwing
 * UnauthorizedException here lets handleRequest() catch it and map it
 * to a 401 JSON response â€” the same contract as all other domain errors.
 */
class AuthMiddleware
{
    /** Stores the authenticated user's role after token validation. */
    private static ?string $authenticatedUserRole = null;

    /**
     * Authenticate using the Authorization header (standard for API calls).
     *
     * Extracts the Bearer token from the header, validates it, and
     * returns the authenticated user's ID. Also stores the user's role
     * for later access via getAuthenticatedUserRole().
     *
     * @return int Authenticated user ID.
     */
    public static function authenticate(): int
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = self::extractBearerToken($header);

        if (!$token) {
            throw new UnauthorizedException('Missing or invalid Authorization header');
        }

        return self::resolveUserIdFromToken($token);
    }

    /**
     * Authenticate using the Authorization header OR a ?token= query parameter.
     *
     * HTML <video> elements cannot set custom headers on their media requests,
     * so the video streaming endpoint needs to also accept the token as a
     * query parameter. Tries the header first, falls back to the query param.
     *
     * @return int Authenticated user ID.
     */
    public static function authenticateFromHeaderOrQueryParam(): int
    {
        $token = self::extractTokenFromHeaderOrQueryParam();

        if (!$token) {
            throw new UnauthorizedException('Unauthorized');
        }

        return self::resolveUserIdFromToken($token);
    }

    /**
     * Get the role of the most recently authenticated user.
     *
     * @return string Most recently resolved authenticated role.
     */
    public static function getAuthenticatedUserRole(): string
    {
        return self::$authenticatedUserRole ?? 'viewer';
    }

    /**
     * Extract the raw Bearer token string from the Authorization header.
     *
     * @return string Raw bearer token value.
     */
    public static function extractToken(): string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = self::extractBearerToken($header);

        if (!$token) {
            throw new UnauthorizedException('Missing or invalid Authorization header');
        }

        return $token;
    }

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    //  Token extraction helpers
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * Extract a bearer token from an Authorization header value.
     *
     * @param string $header Raw Authorization header value.
     * @return string|null Extracted token or null when the header is invalid.
     */
    private static function extractBearerToken(string $header): ?string
    {
        if (preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Try the Authorization header first, then fall back to ?token= query param.
     *
     * @return string|null Extracted token or null when neither source contains one.
     */
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

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    //  Token validation

    /**
     * Validate a token and return the owning user's ID, or throw 401.
     *
     * @param string $token Raw JWT access token.
     * @return int Authenticated user ID.
     */
    private static function resolveUserIdFromToken(string $token): int
    {
        $user = ServiceRegistry::authService()->getUserFromToken($token);

        if (!$user) {
            throw new UnauthorizedException('Invalid or expired token');
        }

        self::$authenticatedUserRole = $user->role;

        return $user->id;
    }
}
