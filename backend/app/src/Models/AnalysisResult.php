<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Immutable value object representing an analysis_results row.
 *
 * Each video gets one summary row after analysis completes, containing
 * aggregate metrics like total flash events and peak flash frequency.
 */
class AnalysisResult
{
    public function __construct(
        public readonly int $videoId,
        public readonly int $totalFramesAnalyzed,
        public readonly int $totalFlashEvents,
        public readonly float $highestFlashFrequency,
        public readonly float $averageMotionIntensity,
        public readonly int $effectiveSamplingRate,
    ) {}

    /** Build an AnalysisResult from a raw database row. */
    public static function fromRow(array $row): self
    {
        return new self(
            videoId: (int) $row['video_id'],
            totalFramesAnalyzed: (int) $row['total_frames_analyzed'],
            totalFlashEvents: (int) $row['total_flash_events'],
            highestFlashFrequency: (float) $row['highest_flash_frequency'],
            averageMotionIntensity: (float) $row['average_motion_intensity'],
            effectiveSamplingRate: (int) $row['effective_sampling_rate'],
        );
    }
}
