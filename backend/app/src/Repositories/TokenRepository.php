<?php

namespace App\Repositories;

use App\Framework\Database;
use PDO;

class TokenRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function store(int $userId, string $token, string $expiresAt): void
    {
        $stmt = $this->db->prepare('INSERT INTO auth_tokens (user_id, token, expires_at) VALUES (:userId, :token, :expiresAt)');
        $stmt->execute(['userId' => $userId, 'token' => $token, 'expiresAt' => $expiresAt]);
    }

    public function findValidToken(string $token): ?array
    {
        $stmt = $this->db->prepare('SELECT id, user_id, token, expires_at, created_at FROM auth_tokens WHERE token = :token AND expires_at > NOW()');
        $stmt->execute(['token' => $token]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function deleteByToken(string $token): void
    {
        $stmt = $this->db->prepare('DELETE FROM auth_tokens WHERE token = :token');
        $stmt->execute(['token' => $token]);
    }

    public function deleteExpiredTokens(): void
    {
        $this->db->exec('DELETE FROM auth_tokens WHERE expires_at <= NOW()');
    }
}
