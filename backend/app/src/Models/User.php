<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Immutable value object representing a user account.
 *
 * Wraps the raw database row into a typed object so the rest of the app
 * gets IDE autocompletion, type safety, and a clear contract for what
 * fields a user has â€” instead of guessing at array keys.
 *
 * The passwordHash is intentionally included so AuthService can verify
 * credentials, but toApiArray() strips it out before sending to the frontend.
 */
class User
{
    /**
     * @param int $id
     * @param string $username
     * @param string $passwordHash
     * @param ?string $displayName
     * @param string $role
     * @param string $createdAt
     * @param string $updatedAt
     * @return void
     */
    public function __construct(
        public readonly int $id,
        public readonly string $username,
        public readonly string $passwordHash,
        public readonly ?string $displayName,
        public readonly string $role,
        public readonly bool $isActive,
        public readonly string $createdAt,
        public readonly string $updatedAt,
    ) {}

    /**
     * Build a User from a raw database row (associative array).
     *
     * This is the only place that knows about the database column names.
     * If column names ever change, only this method needs updating.
     */
    /**
     * @param array $row
     * @return self
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            username: $row['username'],
            passwordHash: $row['password_hash'],
            displayName: $row['display_name'],
            role: $row['role'] ?? 'viewer',
            isActive: (bool) ($row['is_active'] ?? true),
            createdAt: $row['created_at'],
            updatedAt: $row['updated_at'],
        );
    }

    /**
     * Convert to a clean, camelCase array for the API response.
     *
     * Strips sensitive fields (passwordHash) so they never accidentally
     * leak to the frontend.
     */
    /**
     * @return array
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'displayName' => $this->displayName,
            'role' => $this->role,
            'isActive' => $this->isActive,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }
}
