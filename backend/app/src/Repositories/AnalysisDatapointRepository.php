<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTOs\DatapointData;
use App\Models\AnalysisDatapoint;

/**
 * Data-access layer for the `analysis_datapoints` table.
 *
 * Purpose: Persists and retrieves per-second time-series data produced
 * during video analysis — flash frequency, motion intensity, luminance,
 * and a boolean flash-detected flag for every sampled time point.
 *
 * Why do I need it: The Chart.js visualisations on the report page need
 * fine-grained, ordered data to render the flash-frequency and motion-
 * intensity graphs. Storing these datapoints in a dedicated table (rather
 * than computing them on the fly) lets the frontend fetch them in a
 * single query. The batch-insert strategy (chunks of 50 inside a
 * transaction) keeps write performance acceptable even for long videos
 * that produce thousands of rows.
 */
class AnalysisDatapointRepository extends BaseRepository
{
    /** @var int Number of rows inserted per prepared statement to balance query size and round-trips. */
    private const BATCH_SIZE = 50;

    /**
     * Insert all datapoints for a video in batched chunks within a transaction.
     *
     * Called by AnalysisService after the frame-by-frame detector pass is
     * complete. The entire write is wrapped in a transaction so that a
     * failure mid-way does not leave partial data in the table.
     *
     * @param  int             $videoId    The video these datapoints belong to.
     * @param  DatapointData[] $datapoints Typed datapoint DTOs from AnalysisService.
     * @return void
     *
     * @throws \Throwable Re-throws any exception after rolling back.
     */
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

    /**
     * Retrieve all datapoints for a video, ordered chronologically.
     *
     * Used by ReportService to supply the time-series arrays that feed
     * the Chart.js line graphs on the report page.
     *
     * @param  int                 $videoId The video to query.
     * @return AnalysisDatapoint[] List of datapoints sorted by time ascending.
     */
    public function findByVideoId(int $videoId): array
    {
        $stmt = $this->db->prepare(
            'SELECT time_point, flash_frequency, motion_intensity, luminance, flash_detected
             FROM analysis_datapoints WHERE video_id = ? ORDER BY time_point ASC'
        );
        $stmt->execute([$videoId]);

        return $this->fetchAllHydrated($stmt, AnalysisDatapoint::fromRow(...));
    }

    /**
     * Delete all datapoints for a video.
     *
     * Called during re-analysis reset to clear stale time-series data
     * before the worker produces a fresh set.
     *
     * @param  int  $videoId The video whose datapoints should be removed.
     * @return void
     */
    public function deleteByVideoId(int $videoId): void
    {
        $stmt = $this->db->prepare('DELETE FROM analysis_datapoints WHERE video_id = ?');
        $stmt->execute([$videoId]);
    }

    /**
     * Build and execute a multi-row INSERT for a single chunk of datapoints.
     *
     * Constructs a prepared statement with dynamic placeholders so that
     * up to BATCH_SIZE rows are inserted in one round-trip to the database.
     *
     * @param  int             $videoId The owning video's ID (prepended to every row).
     * @param  DatapointData[] $chunk   Subset of datapoints (max BATCH_SIZE items).
     * @return void
     */
    private function insertChunk(int $videoId, array $chunk): void
    {
        $placeholders = [];
        $values = [];

        foreach ($chunk as $datapoint) {
            $placeholders[] = '(?, ?, ?, ?, ?, ?)';
            $values[] = $videoId;
            $values[] = $datapoint->timePoint;
            $values[] = $datapoint->flashFrequency;
            $values[] = $datapoint->motionIntensity;
            $values[] = $datapoint->luminance;
            $values[] = $datapoint->flashDetected ? 1 : 0;
        }

        $sql = 'INSERT INTO analysis_datapoints
                (video_id, time_point, flash_frequency, motion_intensity, luminance, flash_detected)
                VALUES ' . implode(', ', $placeholders);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
    }
}
