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

    // The only place that knows the database column names — change here if columns are renamed.
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

    // Strips sensitive fields (passwordHash) before sending to the frontend.
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
