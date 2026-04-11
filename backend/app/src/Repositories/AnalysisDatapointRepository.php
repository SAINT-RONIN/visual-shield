<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Repositories\Interfaces\AnalysisDatapointRepositoryInterface;
use App\DTOs\DatapointData;
use App\Models\AnalysisDatapoint;

/**
 * Data-access layer for the `analysis_datapoints` table.
 *
 * Purpose: Persists and retrieves per-second time-series data produced
 * during video analysis â€” flash frequency, motion intensity, luminance,
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
class AnalysisDatapointRepository extends BaseRepository implements AnalysisDatapointRepositoryInterface
{
    /** @var int Number of rows inserted per prepared statement to balance query size and round-trips. */
    private const BATCH_SIZE = 50;

    // Batched chunks inside a transaction — rolls back and re-throws on any failure.
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

    // Ordered chronologically; feeds the Chart.js time-series graphs on the report page.
    /** @return AnalysisDatapoint[] */
    public function findByVideoId(int $videoId): array
    {
        $stmt = $this->db->prepare(
            'SELECT time_point, flash_frequency, motion_intensity, luminance, flash_detected
             FROM analysis_datapoints WHERE video_id = ? ORDER BY time_point ASC'
        );
        $stmt->execute([$videoId]);

        return $this->fetchAllHydrated($stmt, AnalysisDatapoint::fromRow(...));
    }

    // Called during re-analysis reset to clear stale time-series data before fresh results are produced.
    public function deleteByVideoId(int $videoId): void
    {
        $stmt = $this->db->prepare('DELETE FROM analysis_datapoints WHERE video_id = ?');
        $stmt->execute([$videoId]);
    }

    // Dynamic placeholders; inserts up to BATCH_SIZE rows in one round-trip.
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
