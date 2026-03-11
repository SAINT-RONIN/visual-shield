<?php

namespace App\Utils;

/**
 * Determines the overall risk level from analysis metrics.
 *
 * Used by both the Video model (dashboard badge) and ReportDTO (report page).
 * Centralised here so the thresholds are defined in exactly one place.
 *
 * Returns 'high', 'medium', 'low', or 'safe'.
 */
class RiskLevel
{
    /**
     * Determine risk level from flash frequency, motion intensity, and segment counts.
     *
     * @param float $flashFrequency    Peak flashes per second.
     * @param float $motionIntensity   Average motion intensity (0-255 scale).
     * @param int   $highSegments      Number of high-severity segments.
     * @param int   $mediumSegments    Number of medium-severity segments.
     * @param int   $totalSegments     Total number of flagged segments.
     */
    public static function determine(
        float $flashFrequency,
        float $motionIntensity,
        int $highSegments,
        int $mediumSegments,
        int $totalSegments,
    ): string {
        if ($highSegments > 0 || $flashFrequency > 10 || $motionIntensity > 120) {
            return 'high';
        }

        if ($mediumSegments > 0 || $flashFrequency > 5 || $motionIntensity > 60) {
            return 'medium';
        }

        if ($totalSegments > 0 || $flashFrequency > 3 || $motionIntensity > 30) {
            return 'low';
        }

        return 'safe';
    }
}
