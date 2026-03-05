<?php

namespace App\Repositories;

use App\Framework\Database;
use PDO;

class AnalysisDatapointRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function createBatch(int $videoId, array $datapoints): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO analysis_datapoints (video_id, time_point, flash_frequency, motion_intensity, luminance, flash_detected) VALUES (?, ?, ?, ?, ?, ?)'
        );

        foreach ($datapoints as $dp) {
            $stmt->execute([
                $videoId,
                $dp['timePoint'],
                $dp['flashFrequency'] ?? 0,
                $dp['motionIntensity'] ?? 0,
                $dp['luminance'] ?? 0,
                $dp['flashDetected'] ?? false,
            ]);
        }
    }

    public function findByVideoId(int $videoId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM analysis_datapoints WHERE video_id = ? ORDER BY time_point ASC');
        $stmt->execute([$videoId]);

        return $stmt->fetchAll();
    }
}
