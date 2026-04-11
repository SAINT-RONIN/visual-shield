<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Repositories\Interfaces\TokenRepositoryInterface;
use App\Models\Token;

/**
 * Data-access layer for the `auth_tokens` table.
 *
 * Stores active JWT session IDs (`jti` claims) with expiry timestamps so
 * access tokens can still be revoked on logout.
 */
class TokenRepository extends BaseRepository implements TokenRepositoryInterface
{
    // Persists a new active JWT session ID for a given user.
    public function store(int $userId, string $token, string $expiresAt): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO auth_tokens (user_id, token, expires_at) VALUES (:userId, :token, :expiresAt)'
        );
        $stmt->execute(['userId' => $userId, 'token' => $token, 'expiresAt' => $expiresAt]);
    }

    // Retrieves a stored JWT session only if it has not yet expired.
    public function findValidToken(string $token): ?Token
    {
        $stmt = $this->db->prepare(
            'SELECT id, user_id, token, expires_at, created_at
             FROM auth_tokens
             WHERE token = :token AND expires_at > NOW()'
        );
        $stmt->execute(['token' => $token]);

        return $this->fetchOneOrNull($stmt, Token::fromRow(...));
    }

    // Deletes a specific active JWT session.
    public function deleteByToken(string $token): void
    {
        $stmt = $this->db->prepare('DELETE FROM auth_tokens WHERE token = :token');
        $stmt->execute(['token' => $token]);
    }

    // Purges expired JWT sessions as light housekeeping.
    public function deleteExpiredTokens(): void
    {
        $this->db->exec('DELETE FROM auth_tokens WHERE expires_at <= NOW()');
    }
}
