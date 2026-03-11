<?php

namespace App\DTOs;

/**
 * Immutable value object holding the results of motion detection analysis.
 *
 * This is an output DTO — it carries data produced by MotionDetector
 * to AnalysisService for persistence. It is NOT a database model.
 */
class MotionAnalysisResult
{
    /**
     * @param float $averageIntensity     Mean motion intensity across all seconds of the video.
     * @param array $segments             Flagged time segments where motion exceeded the threshold.
     *                                    Each entry: {startTime, endTime, type, severity, metricValue}.
     * @param array $perSecondIntensities Average motion intensity per second across the entire video.
     *                                    Each entry: {second, intensity}.
     */
    public function __construct(
        public readonly float $averageIntensity,
        public readonly array $segments,
        public readonly array $perSecondIntensities,
    ) {}
}
