<?php

namespace App\Repositories;

use App\Framework\Database;
use PDO;

class UserRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare('SELECT id, username, password_hash, display_name, created_at, updated_at FROM users WHERE username = :username');
        $stmt->execute(['username' => $username]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT id, username, password_hash, display_name, created_at, updated_at FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function create(string $username, string $passwordHash, ?string $displayName): int
    {
        $stmt = $this->db->prepare('INSERT INTO users (username, password_hash, display_name) VALUES (:username, :passwordHash, :displayName)');
        $stmt->execute(['username' => $username, 'passwordHash' => $passwordHash, 'displayName' => $displayName]);
        return (int) $this->db->lastInsertId();
    }

    public function updateProfile(int $id, ?string $displayName): void
    {
        $stmt = $this->db->prepare('UPDATE users SET display_name = :displayName WHERE id = :id');
        $stmt->execute(['displayName' => $displayName, 'id' => $id]);
    }
}
