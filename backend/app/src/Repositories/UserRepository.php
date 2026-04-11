<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Repositories\Interfaces\UserRepositoryInterface;
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
    // Used during login (password verification) and registration (duplicate check).
    public function findByUsername(string $username): ?User
    {
        $stmt = $this->db->prepare(
            'SELECT id, username, password_hash, display_name, role, is_active, created_at, updated_at
             FROM users WHERE username = :username'
        );
        $stmt->execute(['username' => $username]);

        return $this->fetchOneOrNull($stmt, User::fromRow(...));
    }

    // Used after token validation to load the authenticated user's profile.
    public function findById(int $id): ?User
    {
        $stmt = $this->db->prepare(
            'SELECT id, username, password_hash, display_name, role, is_active, created_at, updated_at
             FROM users WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);

        return $this->fetchOneOrNull($stmt, User::fromRow(...));
    }

    // Called during registration after password hashing; returns the generated ID.
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

    // Updates display name without affecting login credentials.
    public function updateProfile(int $id, ?string $displayName): void
    {
        $stmt = $this->db->prepare(
            'UPDATE users SET display_name = :displayName WHERE id = :id'
        );
        $stmt->execute(['displayName' => $displayName, 'id' => $id]);
    }

    // Counts total number of users in the database.
    public function countAll(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) FROM users');

        return (int) $stmt->fetchColumn();
    }

    // Counts how many users currently have the given role.
    public function countByRole(string $role): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM users WHERE role = :role');
        $stmt->execute(['role' => $role]);

        return (int) $stmt->fetchColumn();
    }

    /** @return User[] */
    public function findAll(UserFilterDTO $filters): array
    {
        $sql = 'SELECT id, username, password_hash, display_name, role, is_active, created_at, updated_at
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

    // Counts users matching the current admin filters.
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

    // Updates a user's role; returns the updated user, or null if the ID does not exist.
    public function updateRole(int $id, string $role): ?User
    {
        $stmt = $this->db->prepare('UPDATE users SET role = :role WHERE id = :id');
        $stmt->execute(['role' => $role, 'id' => $id]);

        return $this->findById($id);
    }

    // Updates a user's active status; returns the updated user, or null if the ID does not exist.
    public function updateStatus(int $id, bool $isActive): ?User
    {
        $stmt = $this->db->prepare('UPDATE users SET is_active = :isActive WHERE id = :id');
        $stmt->execute(['isActive' => (int) $isActive, 'id' => $id]);

        return $this->findById($id);
    }

    // Counts active users with the given role.
    public function countActiveByRole(string $role): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM users WHERE role = :role AND is_active = 1');
        $stmt->execute(['role' => $role]);

        return (int) $stmt->fetchColumn();
    }
}
