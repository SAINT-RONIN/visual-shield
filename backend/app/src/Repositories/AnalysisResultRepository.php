<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Framework\Database;
use App\Models\AnalysisResult;
use PDO;

/**
 * Data-access layer for the `analysis_results` table.
 *
 * Purpose: Stores and retrieves the single high-level summary row that
 * each completed video analysis produces — total frames analysed, peak
 * flash frequency, average motion intensity, and effective sampling rate.
 *
 * Why do I need it: AnalysisService writes to this repository at the end
 * of a successful analysis run, and ReportService / VideoRepository read
 * from it to populate dashboard cards and the detailed report view.
 * Keeping summary metrics in their own table avoids expensive aggregation
 * over the much larger analysis_datapoints table on every page load.
 */
class AnalysisResultRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Insert a new analysis-summary row for a video.
     *
     * Called once by AnalysisService after all frames have been processed
     * and the detectors have returned their aggregate metrics.
     *
     * @param  int   $videoId            The video this result belongs to.
     * @param  int   $totalFrames        Number of frames that were analysed.
     * @param  int   $totalFlashEvents   Count of detected flash events.
     * @param  float $highestFlashFreq   Peak flash frequency in Hz.
     * @param  float $avgMotionIntensity Average motion intensity (0-100 scale).
     * @param  int   $effectiveRate      Actual sampling rate used (fps).
     * @return int   The auto-incremented ID of the new row.
     */
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

    /**
     * Retrieve the analysis summary for a given video.
     *
     * Used by ReportService to build the report payload and by
     * VideoRepository's dashboard queries (via JOIN) to show key
     * metrics on each video card.
     *
     * @param  int                $videoId The video to look up.
     * @return AnalysisResult|null The summary, or null if analysis has not completed.
     */
    public function findByVideoId(int $videoId): ?AnalysisResult
    {
        $stmt = $this->db->prepare(
            'SELECT video_id, total_frames_analyzed, total_flash_events,
                    highest_flash_frequency, average_motion_intensity, effective_sampling_rate
             FROM analysis_results WHERE video_id = ?'
        );
        $stmt->execute([$videoId]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return AnalysisResult::fromRow($row);
    }

    /**
     * Delete the analysis summary for a video.
     *
     * Called during re-analysis reset so that stale results are removed
     * before the worker produces new ones.
     *
     * @param  int  $videoId The video whose result should be deleted.
     * @return void
     */
    public function deleteByVideoId(int $videoId): void
    {
        $stmt = $this->db->prepare('DELETE FROM analysis_results WHERE video_id = ?');
        $stmt->execute([$videoId]);
    }
}
