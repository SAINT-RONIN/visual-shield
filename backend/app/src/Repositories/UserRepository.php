<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\UserRepositoryInterface;
use App\DTOs\UserFilterDTO;
use App\Models\User;
use PDO;

/**
 * Data-access layer for the `users` table.
 *
 * Every method that reads a user returns a typed User object (or null)
 * instead of a raw associative array. This gives the rest of the app
 * IDE autocompletion and type safety for user data.
 */
class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    /**
     * Look up a user by their unique username.
     *
     * Used during login (to verify the password) and registration
     * (to check for duplicates).
     */
    /**
     * @param string $username
     * @return ?User
     */
    public function findByUsername(string $username): ?User
    {
        $stmt = $this->db->prepare(
            'SELECT id, username, password_hash, display_name, role, created_at, updated_at
             FROM users WHERE username = :username'
        );
        $stmt->execute(['username' => $username]);

        return $this->fetchOneOrNull($stmt, User::fromRow(...));
    }

    /**
     * Look up a user by their primary key.
     *
     * Used after token validation to load the authenticated user's profile.
     */
    /**
     * @param int $id
     * @return ?User
     */
    public function findById(int $id): ?User
    {
        $stmt = $this->db->prepare(
            'SELECT id, username, password_hash, display_name, role, created_at, updated_at
             FROM users WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);

        return $this->fetchOneOrNull($stmt, User::fromRow(...));
    }

    /**
     * Insert a new user record and return the generated ID.
     *
     * Called during registration after password hashing.
     */
    /**
     * @param string $username
     * @param string $passwordHash
     * @param ?string $displayName
     * @param string $role
     * @return int
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
    /**
     * @param int $id
     * @param ?string $displayName
     * @return void
     */
    public function updateProfile(int $id, ?string $displayName): void
    {
        $stmt = $this->db->prepare(
            'UPDATE users SET display_name = :displayName WHERE id = :id'
        );
        $stmt->execute(['displayName' => $displayName, 'id' => $id]);
    }

    /** Count total number of users in the database. */
    /**
     * @return int
     */
    public function countAll(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) FROM users');

        return (int) $stmt->fetchColumn();
    }

    /** @return User[] */
    /**
     * @param UserFilterDTO $filters
     * @return array
     */
    public function findAll(UserFilterDTO $filters): array
    {
        $sql = 'SELECT id, username, password_hash, display_name, role, created_at, updated_at
                FROM users
                WHERE 1 = 1';
        $params = [];

        if ($filters->role !== null) {
            $sql .= ' AND role = :role';
            $params['role'] = $filters->role;
        }

        if ($filters->search !== null) {
            $sql .= ' AND (username LIKE :searchUsername OR display_name LIKE :searchDisplayName)';
            $params['searchUsername'] = '%' . $filters->search . '%';
            $params['searchDisplayName'] = '%' . $filters->search . '%';
        }

        $allowedSorts = ['created_at', 'username', 'role'];
        $allowedOrders = ['asc', 'desc'];
        $sortCol = in_array($filters->sort, $allowedSorts, true) ? $filters->sort : 'created_at';
        $orderDir = in_array($filters->order, $allowedOrders, true) ? strtoupper($filters->order) : 'ASC';

        $sql .= " ORDER BY {$sortCol} {$orderDir}";
        $sql .= ' LIMIT :limit OFFSET :offset';

        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue('limit', $filters->limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $filters->offset, PDO::PARAM_INT);
        $stmt->execute();

        return $this->fetchAllHydrated($stmt, User::fromRow(...));
    }

    /** Count users matching the current admin filters. */
    /**
     * @param UserFilterDTO $filters
     * @return int
     */
    public function countAllFiltered(UserFilterDTO $filters): int
    {
        $sql = 'SELECT COUNT(*) FROM users WHERE 1 = 1';
        $params = [];

        if ($filters->role !== null) {
            $sql .= ' AND role = :role';
            $params['role'] = $filters->role;
        }

        if ($filters->search !== null) {
            $sql .= ' AND (username LIKE :searchUsername OR display_name LIKE :searchDisplayName)';
            $params['searchUsername'] = '%' . $filters->search . '%';
            $params['searchDisplayName'] = '%' . $filters->search . '%';
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /** Update a user's role and return the updated user, or null if the ID does not exist. */
    /**
     * @param int $id
     * @param string $role
     * @return ?User
     */
    public function updateRole(int $id, string $role): ?User
    {
        $stmt = $this->db->prepare('UPDATE users SET role = :role WHERE id = :id');
        $stmt->execute(['role' => $role, 'id' => $id]);

        return $this->findById($id);
    }
}
