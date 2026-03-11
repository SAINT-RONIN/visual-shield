<?php

namespace App\Repositories;

use App\Framework\Database;
use App\Models\User;
use PDO;

/**
 * Data-access layer for the `users` table.
 *
 * Every method that reads a user returns a typed User object (or null)
 * instead of a raw associative array. This gives the rest of the app
 * IDE autocompletion and type safety for user data.
 */
class UserRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Look up a user by their unique username.
     *
     * Used during login (to verify the password) and registration
     * (to check for duplicates).
     */
    public function findByUsername(string $username): ?User
    {
        $stmt = $this->db->prepare(
            'SELECT id, username, password_hash, display_name, created_at, updated_at
             FROM users WHERE username = :username'
        );
        $stmt->execute(['username' => $username]);
        $row = $stmt->fetch();

        return $row ? User::fromRow($row) : null;
    }

    /**
     * Look up a user by their primary key.
     *
     * Used after token validation to load the authenticated user's profile.
     */
    public function findById(int $id): ?User
    {
        $stmt = $this->db->prepare(
            'SELECT id, username, password_hash, display_name, created_at, updated_at
             FROM users WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? User::fromRow($row) : null;
    }

    /**
     * Insert a new user record and return the generated ID.
     *
     * Called during registration after password hashing.
     */
    public function create(string $username, string $passwordHash, ?string $displayName): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (username, password_hash, display_name)
             VALUES (:username, :passwordHash, :displayName)'
        );
        $stmt->execute([
            'username' => $username,
            'passwordHash' => $passwordHash,
            'displayName' => $displayName,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Update a user's display name.
     *
     * Lets users change their visible name without affecting login credentials.
     */
    public function updateProfile(int $id, ?string $displayName): void
    {
        $stmt = $this->db->prepare(
            'UPDATE users SET display_name = :displayName WHERE id = :id'
        );
        $stmt->execute(['displayName' => $displayName, 'id' => $id]);
    }
}
