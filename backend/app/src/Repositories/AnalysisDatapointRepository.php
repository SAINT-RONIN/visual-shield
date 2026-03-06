<?php

namespace App\Repositories;

use App\Framework\Database;
use PDO;

class AnalysisDatapointRepository
{
    private const BATCH_SIZE = 50;

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function createBatch(int $videoId, array $datapoints): void
    {
        if (empty($datapoints)) {
            return;
        }

        $this->db->beginTransaction();

        try {
            foreach (array_chunk($datapoints, self::BATCH_SIZE) as $chunk) {
                $this->insertChunk($videoId, $chunk);
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function findByVideoId(int $videoId): array
    {
        $stmt = $this->db->prepare(
            'SELECT time_point, flash_frequency, motion_intensity, luminance, flash_detected
             FROM analysis_datapoints WHERE video_id = ? ORDER BY time_point ASC'
        );
        $stmt->execute([$videoId]);

        return $stmt->fetchAll();
    }

    private function insertChunk(int $videoId, array $chunk): void
    {
        $placeholders = [];
        $values = [];

        foreach ($chunk as $dp) {
            $placeholders[] = '(?, ?, ?, ?, ?, ?)';
            $values[] = $videoId;
            $values[] = $dp['timePoint'];
            $values[] = $dp['flashFrequency'] ?? 0;
            $values[] = $dp['motionIntensity'] ?? 0;
            $values[] = $dp['luminance'] ?? 0;
            $values[] = $dp['flashDetected'] ? 1 : 0;
        }

        $sql = 'INSERT INTO analysis_datapoints
                (video_id, time_point, flash_frequency, motion_intensity, luminance, flash_detected)
                VALUES ' . implode(', ', $placeholders);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
    }
}
