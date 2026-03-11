<?php

declare(strict_types=1);

namespace App\Utils;

use App\Config\AnalysisConfig;

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
        $isHighRisk = $highSegments > 0
            || $flashFrequency > AnalysisConfig::FLASH_SEVERITY_HIGH
            || $motionIntensity > AnalysisConfig::MOTION_SEVERITY_HIGH;

        if ($isHighRisk) {
            return 'high';
        }

        $isMediumRisk = $mediumSegments > 0
            || $flashFrequency > AnalysisConfig::FLASH_SEVERITY_MEDIUM
            || $motionIntensity > AnalysisConfig::MOTION_SEVERITY_MEDIUM;

        if ($isMediumRisk) {
            return 'medium';
        }

        $isLowRisk = $totalSegments > 0
            || $flashFrequency > AnalysisConfig::FLASH_FREQUENCY_DANGER
            || $motionIntensity > AnalysisConfig::MOTION_THRESHOLD;

        if ($isLowRisk) {
            return 'low';
        }

        return 'safe';
    }

    // ──────────────────────────────────────────────
    //  Per-metric risk colors
    // ──────────────────────────────────────────────

    /** Risk color for the total number of flash events detected. */
    public static function colorForFlashCount(int $count): string
    {
        if ($count > AnalysisConfig::FLASH_COUNT_DANGER) {
            return 'danger';
        }

        if ($count > AnalysisConfig::FLASH_COUNT_WARNING) {
            return 'warning';
        }

        return 'safe';
    }

    /** Risk color for the peak flash frequency in Hz. */
    public static function colorForFlashFrequency(float $hz): string
    {
        if ($hz > AnalysisConfig::FLASH_SEVERITY_HIGH) {
            return 'danger';
        }

        if ($hz > AnalysisConfig::FLASH_FREQUENCY_DANGER) {
            return 'warning';
        }

        return 'safe';
    }

    /** Risk color for average motion intensity (0-255 scale). */
    public static function colorForMotionIntensity(float $intensity): string
    {
        if ($intensity > AnalysisConfig::MOTION_SEVERITY_HIGH) {
            return 'danger';
        }

        if ($intensity > AnalysisConfig::MOTION_THRESHOLD) {
            return 'warning';
        }

        return 'safe';
    }
}
