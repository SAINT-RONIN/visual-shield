<?php

namespace App\Repositories;

use App\Framework\Database;
use PDO;

class FlaggedSegmentRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function createBatch(int $videoId, array $segments): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO flagged_segments (video_id, start_time, end_time, segment_type, severity, metric_value) VALUES (?, ?, ?, ?, ?, ?)'
        );

        foreach ($segments as $segment) {
            $stmt->execute([
                $videoId,
                $segment['startTime'],
                $segment['endTime'],
                $segment['type'],
                $segment['severity'],
                $segment['metricValue'] ?? null,
            ]);
        }
    }

    public function findByVideoId(int $videoId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM flagged_segments WHERE video_id = ? ORDER BY start_time ASC');
        $stmt->execute([$videoId]);

        return $stmt->fetchAll();
    }
}
