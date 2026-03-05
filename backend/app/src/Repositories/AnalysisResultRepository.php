<?php

namespace App\Repositories;

use App\Framework\Database;
use PDO;

class AnalysisResultRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function create(
        int $videoId,
        int $totalFrames,
        int $totalFlashEvents,
        float $highestFlashFreq,
        float $avgMotionIntensity,
        int $effectiveRate,
    ): int {
        $stmt = $this->db->prepare(
            'INSERT INTO analysis_results
                (video_id, total_frames_analyzed, total_flash_events,
                 highest_flash_frequency, average_motion_intensity, effective_sampling_rate)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $videoId, $totalFrames, $totalFlashEvents,
            $highestFlashFreq, $avgMotionIntensity, $effectiveRate,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findByVideoId(int $videoId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT video_id, total_frames_analyzed, total_flash_events,
                    highest_flash_frequency, average_motion_intensity, effective_sampling_rate
             FROM analysis_results WHERE video_id = ?'
        );
        $stmt->execute([$videoId]);

        return $stmt->fetch() ?: null;
    }
}
