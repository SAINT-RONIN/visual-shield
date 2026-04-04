<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Immutable value object representing a stored JWT session row.
 */
class Token
{
    /**
     * @param int $id
     * @param int $userId
     * @param string $token
     * @param string $expiresAt
     * @param string $createdAt
     * @return void
     */
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly string $token,
        public readonly string $expiresAt,
        public readonly string $createdAt,
    ) {}

    /** Build a Token from a raw database row. */
    /**
     * @param array $row
     * @return self
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            userId: (int) $row['user_id'],
            token: $row['token'],
            expiresAt: $row['expires_at'],
            createdAt: $row['created_at'],
        );
    }
}
