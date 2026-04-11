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
    public function __construct(
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

        public readonly ?float $highestFlashFrequency = null,
        public readonly ?float $averageMotionIntensity = null,
        public readonly ?int $highSegments = null,
        public readonly ?int $mediumSegments = null,
        public readonly ?int $totalSegments = null,

        // Enriched fields — present only when the query JOINs the users table (admin listing)
        public readonly ?string $uploaderUsername = null,
        public readonly ?string $uploaderDisplayName = null,
    ) {}

    // The only place that knows the column names; handles both base and enriched (JOINed) rows.
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

            // Enriched fields — present only when the query JOINs the users table
            uploaderUsername: $row['uploader_username'] ?? null,
            uploaderDisplayName: $row['uploader_display_name'] ?? null,
        );
    }

    // Strips internal fields (userId, storedPath); includes computed riskLevel for completed videos.
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
            'uploaderUsername' => $this->uploaderUsername,
            'uploaderDisplayName' => $this->uploaderDisplayName,
        ];
    }

    // Uses the previously computed adjusted rate if present, otherwise the user's requested rate.
    public function getEffectiveSamplingRate(): int
    {
        return $this->effectiveRate ?? $this->samplingRate;
    }

    // All three null means loaded without the JOIN — must not call determineRiskLevel() in that case.
    private function hasEnrichedAnalysisData(): bool
    {
        return $this->highestFlashFrequency !== null
            || $this->averageMotionIntensity !== null
            || $this->totalSegments !== null;
    }

    // Delegates risk calculation to the shared RiskLevel utility.
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
