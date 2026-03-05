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
        if (empty($segments)) {
            return;
        }

        $placeholders = [];
        $values = [];

        foreach ($segments as $segment) {
            $placeholders[] = '(?, ?, ?, ?, ?, ?)';
            $values[] = $videoId;
            $values[] = $segment['startTime'];
            $values[] = $segment['endTime'];
            $values[] = $segment['type'];
            $values[] = $segment['severity'];
            $values[] = $segment['metricValue'] ?? null;
        }

        $sql = 'INSERT INTO flagged_segments
                (video_id, start_time, end_time, segment_type, severity, metric_value)
                VALUES ' . implode(', ', $placeholders);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
    }

    public function findByVideoId(int $videoId): array
    {
        $stmt = $this->db->prepare(
            'SELECT start_time, end_time, segment_type, severity, metric_value
             FROM flagged_segments WHERE video_id = ? ORDER BY start_time ASC'
        );
        $stmt->execute([$videoId]);

        return $stmt->fetchAll();
    }
}
