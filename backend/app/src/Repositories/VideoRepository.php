<?php

namespace App\Repositories;

use App\Framework\Database;
use PDO;

class VideoRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function create(int $userId, string $originalName, string $storedPath, int $fileSize, ?float $duration, int $samplingRate): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO videos (user_id, original_name, stored_path, file_size, duration_seconds, sampling_rate) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $originalName, $storedPath, $fileSize, $duration, $samplingRate]);

        return (int) $this->db->lastInsertId();
    }

    public function findAllByUserId(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM videos WHERE user_id = ? ORDER BY created_at DESC');
        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }

    public function findByIdAndUserId(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM videos WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);

        return $stmt->fetch() ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM videos WHERE id = ?');
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    public function updateStatus(int $id, string $status): void
    {
        $stmt = $this->db->prepare('UPDATE videos SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
    }

    public function updateEffectiveRate(int $id, int $effectiveRate): void
    {
        $stmt = $this->db->prepare('UPDATE videos SET effective_rate = ? WHERE id = ?');
        $stmt->execute([$effectiveRate, $id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM videos WHERE id = ?');
        $stmt->execute([$id]);
    }
}
