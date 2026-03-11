<?php

namespace App\Repositories;

use App\Framework\Database;
use App\Models\Token;
use PDO;

/**
 * Data-access layer for the `auth_tokens` table.
 *
 * Purpose: Manages bearer-token lifecycle — creation, validation, and
 * deletion — so that AuthService can authenticate requests without
 * embedding any SQL.
 *
 * Why do I need it: The application uses stateless bearer-token auth
 * (random_bytes, 24-hour expiry, stored in the DB). This repository
 * encapsulates every token-related query, making it straightforward to
 * enforce expiry rules, revoke tokens on logout, and periodically purge
 * stale rows.
 */
class TokenRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Persist a new bearer token for a given user.
     *
     * Called by AuthService after successful login to store the
     * randomly generated token alongside its expiry timestamp.
     *
     * @param  int    $userId    The owning user's primary key.
     * @param  string $token     The hex-encoded bearer token.
     * @param  string $expiresAt MySQL-compatible datetime string (24 h from now).
     * @return void
     */
    public function store(int $userId, string $token, string $expiresAt): void
    {
        $stmt = $this->db->prepare('INSERT INTO auth_tokens (user_id, token, expires_at) VALUES (:userId, :token, :expiresAt)');
        $stmt->execute(['userId' => $userId, 'token' => $token, 'expiresAt' => $expiresAt]);
    }

    /**
     * Retrieve a token row only if it has not yet expired.
     *
     * Used by the auth middleware on every protected request to resolve
     * the Bearer header to a user ID. The WHERE clause filters out
     * expired tokens so no extra PHP-side check is needed.
     *
     * @param  string     $token The bearer token from the Authorization header.
     * @return Token|null The token record or null if invalid/expired.
     */
    public function findValidToken(string $token): ?Token
    {
        $stmt = $this->db->prepare('SELECT id, user_id, token, expires_at, created_at FROM auth_tokens WHERE token = :token AND expires_at > NOW()');
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return Token::fromRow($row);
    }

    /**
     * Delete a specific token, effectively logging the user out.
     *
     * Called by AuthService on the /auth/logout endpoint to immediately
     * revoke the current session token.
     *
     * @param  string $token The bearer token to revoke.
     * @return void
     */
    public function deleteByToken(string $token): void
    {
        $stmt = $this->db->prepare('DELETE FROM auth_tokens WHERE token = :token');
        $stmt->execute(['token' => $token]);
    }

    /**
     * Purge all tokens whose expiry has passed.
     *
     * Intended for periodic housekeeping (e.g. cron or startup) to
     * prevent the auth_tokens table from growing unboundedly.
     *
     * @return void
     */
    public function deleteExpiredTokens(): void
    {
        $this->db->exec('DELETE FROM auth_tokens WHERE expires_at <= NOW()');
    }
}
