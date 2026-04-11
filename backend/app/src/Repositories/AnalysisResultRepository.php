<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Repositories\Interfaces\AnalysisResultRepositoryInterface;
use App\Models\AnalysisResult;

/**
 * Data-access layer for the `analysis_results` table.
 *
 * Purpose: Stores and retrieves the single high-level summary row that
 * each completed video analysis produces â€” total frames analysed, peak
 * flash frequency, average motion intensity, and effective sampling rate.
 *
 * Why do I need it: AnalysisService writes to this repository at the end
 * of a successful analysis run, and ReportService / VideoRepository read
 * from it to populate dashboard cards and the detailed report view.
 * Keeping summary metrics in their own table avoids expensive aggregation
 * over the much larger analysis_datapoints table on every page load.
 */
class AnalysisResultRepository extends BaseRepository implements AnalysisResultRepositoryInterface
{

    // Called once by AnalysisService after all frames have been processed; returns the new row ID.
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

    // Returns null if analysis has not yet completed for the given video.
    public function findByVideoId(int $videoId): ?AnalysisResult
    {
        $stmt = $this->db->prepare(
            'SELECT video_id, total_frames_analyzed, total_flash_events,
                    highest_flash_frequency, average_motion_intensity, effective_sampling_rate
             FROM analysis_results WHERE video_id = ?'
        );
        $stmt->execute([$videoId]);

        return $this->fetchOneOrNull($stmt, AnalysisResult::fromRow(...));
    }

    // Called during re-analysis reset to remove stale results before the worker produces new ones.
    public function deleteByVideoId(int $videoId): void
    {
        $stmt = $this->db->prepare('DELETE FROM analysis_results WHERE video_id = ?');
        $stmt->execute([$videoId]);
    }
}
