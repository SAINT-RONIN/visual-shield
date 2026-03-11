<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Immutable value object representing an analysis_datapoints row.
 *
 * Each row holds one second's worth of analysis metrics — flash frequency,
 * motion intensity, luminance, and whether a flash was detected. Used
 * by the Chart.js visualisations on the report page.
 */
class AnalysisDatapoint
{
    public function __construct(
        public readonly float $timePoint,
        public readonly float $flashFrequency,
        public readonly float $motionIntensity,
        public readonly float $luminance,
        public readonly bool $flashDetected,
    ) {}

    /** Build an AnalysisDatapoint from a raw database row. */
    public static function fromRow(array $row): self
    {
        return new self(
            timePoint: (float) $row['time_point'],
            flashFrequency: (float) $row['flash_frequency'],
            motionIntensity: (float) $row['motion_intensity'],
            luminance: (float) $row['luminance'],
            flashDetected: (bool) $row['flash_detected'],
        );
    }
}
