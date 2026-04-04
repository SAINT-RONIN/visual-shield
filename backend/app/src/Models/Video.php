<?php

declare(strict_types=1);

namespace App\Models;

use App\Utils\RiskLevel;

/**
 * Immutable value object representing a video record.
 *
 * Some queries JOIN analysis data onto the video row (for the dashboard
 * and risk badges). Those enriched fields are nullable â€” they're only
 * present when the query includes the JOIN. The base fields from the
 * videos table are always present.
 */
class Video
{
    /**
     * @param // â”€â”€ Base fields (always present) â”€â”€ int $id
     * @param int $userId
     * @param string $originalName
     * @param string $storedPath
     * @param int $fileSize
     * @param ?float $durationSeconds
     * @param int $samplingRate
     * @param ?int $effectiveRate
     * @param string $status
     * @param int $progress
     * @param ?string $progressMessage
     * @param ?string $errorMessage
     * @param string $createdAt
     * @param string $updatedAt
     * @param // â”€â”€ Enriched fields (only present when JOINed with analysis data) â”€â”€ ?float $highestFlashFrequency
     * @param ?float $averageMotionIntensity
     * @param ?int $highSegments
     * @param ?int $mediumSegments
     * @param ?int $totalSegments
     * @return void
     */
    public function __construct(
        // â”€â”€ Base fields (always present) â”€â”€
        public readonly int $id,
        public readonly int $userId,
        public readonly string $originalName,
        public readonly string $storedPath,
        public readonly int $fileSize,
        public readonly ?float $durationSeconds,
        public readonly int $samplingRate,
        public readonly ?int $effectiveRate,
        public readonly string $status,
        public readonly int $progress,
        public readonly ?string $progressMessage,
        public readonly ?string $errorMessage,
        public readonly string $createdAt,
        public readonly string $updatedAt,

        // â”€â”€ Enriched fields (only present when JOINed with analysis data) â”€â”€
        public readonly ?float $highestFlashFrequency = null,
        public readonly ?float $averageMotionIntensity = null,
        public readonly ?int $highSegments = null,
        public readonly ?int $mediumSegments = null,
        public readonly ?int $totalSegments = null,
    ) {}

    /**
     * Build a Video from a raw database row.
     *
     * This is the only place that knows about the database column names.
     * Handles both base rows (from findByIdAndUserId) and enriched rows
     * (from findById/findAllByUserId with JOINed analysis metrics).
     */
    /**
     * @param array $row
     * @return self
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            userId: (int) $row['user_id'],
            originalName: $row['original_name'],
            storedPath: $row['stored_path'],
            fileSize: (int) $row['file_size'],
            durationSeconds: isset($row['duration_seconds']) ? (float) $row['duration_seconds'] : null,
            samplingRate: (int) $row['sampling_rate'],
            effectiveRate: isset($row['effective_rate']) ? (int) $row['effective_rate'] : null,
            status: $row['status'],
            progress: (int) ($row['progress'] ?? 0),
            progressMessage: $row['progress_message'] ?? null,
            errorMessage: $row['error_message'] ?? null,
            createdAt: $row['created_at'],
            updatedAt: $row['updated_at'],

            // Enriched fields â€” present only when the query JOINs analysis_results
            highestFlashFrequency: isset($row['highest_flash_frequency']) ? (float) $row['highest_flash_frequency'] : null,
            averageMotionIntensity: isset($row['average_motion_intensity']) ? (float) $row['average_motion_intensity'] : null,
            highSegments: isset($row['high_segments']) ? (int) $row['high_segments'] : null,
            mediumSegments: isset($row['medium_segments']) ? (int) $row['medium_segments'] : null,
            totalSegments: isset($row['total_segments']) ? (int) $row['total_segments'] : null,
        );
    }

    /**
     * Convert to a clean, camelCase array for the API response.
     *
     * Strips internal fields (userId, storedPath) that the frontend
     * doesn't need. Includes a computed riskLevel for completed videos.
     */
    /**
     * @return array
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'originalName' => $this->originalName,
            'fileSize' => $this->fileSize,
            'duration' => $this->durationSeconds,
            'status' => $this->status,
            'samplingRate' => $this->samplingRate,
            'effectiveRate' => $this->effectiveRate,
            'progress' => $this->progress,
            'progressMessage' => $this->progressMessage,
            'errorMessage' => $this->errorMessage,
            'riskLevel' => $this->status === 'completed' && $this->hasEnrichedAnalysisData()
                ? $this->determineRiskLevel()
                : null,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }

    /**
     * Determine the effective sampling rate for analysis.
     *
     * If a previous analysis already computed an adjusted rate (to keep
     * total frames under the cap), use that. Otherwise use the user's
     * originally requested rate.
     */
    /**
     * @return int
     */
    public function getEffectiveSamplingRate(): int
    {
        return $this->effectiveRate ?? $this->samplingRate;
    }

    /**
     * Return true only when the analysis JOIN data is actually present.
     *
     * All three fields being null means the Video was loaded without the
     * analysis JOIN (e.g. findByIdAndUserId). In that case we must not
     * call determineRiskLevel(), which would silently return 'safe' by
     * substituting 0 for every null â€” a misleading result.
     */
    /**
     * @return bool
     */
    private function hasEnrichedAnalysisData(): bool
    {
        return $this->highestFlashFrequency !== null
            || $this->averageMotionIntensity !== null
            || $this->totalSegments !== null;
    }

    /** Delegate risk calculation to the shared RiskLevel utility. */
    /**
     * @return string
     */
    private function determineRiskLevel(): string
    {
        return RiskLevel::determine(
            $this->highestFlashFrequency ?? 0.0,
            $this->averageMotionIntensity ?? 0.0,
            $this->highSegments ?? 0,
            $this->mediumSegments ?? 0,
            $this->totalSegments ?? 0,
        );
    }
}
