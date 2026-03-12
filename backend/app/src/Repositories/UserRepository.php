<?php

declare(strict_types=1);

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
            'SELECT id, username, password_hash, display_name, role, created_at, updated_at
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
            'SELECT id, username, password_hash, display_name, role, created_at, updated_at
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
    public function create(string $username, string $passwordHash, ?string $displayName, string $role = 'viewer'): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (username, password_hash, display_name, role)
             VALUES (:username, :passwordHash, :displayName, :role)'
        );
        $stmt->execute([
            'username' => $username,
            'passwordHash' => $passwordHash,
            'displayName' => $displayName,
            'role' => $role,
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

    /** Count total number of users in the database. */
    public function countAll(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) FROM users');

        return (int) $stmt->fetchColumn();
    }

    /**
     * Fetch all users (admin use only).
     *
     * @return User[]
     */
    public function findAll(): array
    {
        $stmt = $this->db->query(
            'SELECT id, username, password_hash, display_name, role, created_at, updated_at
             FROM users ORDER BY created_at ASC'
        );

        return array_map(fn(array $row) => User::fromRow($row), $stmt->fetchAll());
    }

    /** Update a user's role and return the updated user, or null if the ID does not exist. */
    public function updateRole(int $id, string $role): ?User
    {
        $stmt = $this->db->prepare('UPDATE users SET role = :role WHERE id = :id');
        $stmt->execute(['role' => $role, 'id' => $id]);

        return $this->findById($id);
    }
}
