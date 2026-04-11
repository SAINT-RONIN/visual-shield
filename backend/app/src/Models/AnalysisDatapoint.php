<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Immutable value object representing an analysis_datapoints row.
 *
 * Each row holds one second's worth of analysis metrics â€” flash frequency,
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

    // Builds an AnalysisDatapoint from a raw database row.
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

    // Converts to a camelCase array for the API response.
    public function toApiArray(): array
    {
        return [
            'timePoint' => $this->timePoint,
            'flashFrequency' => $this->flashFrequency,
            'motionIntensity' => $this->motionIntensity,
            'luminance' => $this->luminance,
            'flashDetected' => $this->flashDetected,
        ];
    }
}
