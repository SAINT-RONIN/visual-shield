<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Immutable value object representing a stored JWT session row.
 */
class Token
{
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly string $token,
        public readonly string $expiresAt,
        public readonly string $createdAt,
    ) {}

    /** Build a Token from a raw database row. */
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
