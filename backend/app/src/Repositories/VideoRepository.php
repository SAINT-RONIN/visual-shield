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
        $stmt = $this->db->prepare(
            'SELECT v.*, ar.highest_flash_frequency, ar.average_motion_intensity,
                    (SELECT COUNT(*) FROM flagged_segments fs WHERE fs.video_id = v.id AND fs.severity = \'high\') as high_segments,
                    (SELECT COUNT(*) FROM flagged_segments fs WHERE fs.video_id = v.id AND fs.severity = \'medium\') as medium_segments,
                    (SELECT COUNT(*) FROM flagged_segments fs WHERE fs.video_id = v.id) as total_segments
             FROM videos v
             LEFT JOIN analysis_results ar ON ar.video_id = v.id
             WHERE v.user_id = ? ORDER BY v.created_at DESC'
        );
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
        $stmt = $this->db->prepare(
            'SELECT v.*, ar.highest_flash_frequency, ar.average_motion_intensity,
                    (SELECT COUNT(*) FROM flagged_segments fs WHERE fs.video_id = v.id AND fs.severity = \'high\') as high_segments,
                    (SELECT COUNT(*) FROM flagged_segments fs WHERE fs.video_id = v.id AND fs.severity = \'medium\') as medium_segments,
                    (SELECT COUNT(*) FROM flagged_segments fs WHERE fs.video_id = v.id) as total_segments
             FROM videos v
             LEFT JOIN analysis_results ar ON ar.video_id = v.id
             WHERE v.id = ?'
        );
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

    public function updateProgress(int $id, int $progress, string $message): void
    {
        $stmt = $this->db->prepare('UPDATE videos SET progress = ?, progress_message = ? WHERE id = ?');
        $stmt->execute([$progress, $message, $id]);
    }

    public function updateError(int $id, string $message): void
    {
        $stmt = $this->db->prepare('UPDATE videos SET error_message = ? WHERE id = ?');
        $stmt->execute([$message, $id]);
    }

    public function resetForReanalysis(int $id, int $samplingRate): void
    {
        $stmt = $this->db->prepare(
            'UPDATE videos SET status = \'queued\', progress = 0, progress_message = NULL, error_message = NULL, sampling_rate = ? WHERE id = ?'
        );
        $stmt->execute([$samplingRate, $id]);
    }

    public function findNextQueued(): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM videos WHERE status = 'queued' ORDER BY created_at ASC LIMIT 1");
        $stmt->execute();

        return $stmt->fetch() ?: null;
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM videos WHERE id = ?');
        $stmt->execute([$id]);
    }
}
