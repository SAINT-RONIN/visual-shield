<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Immutable value object holding the results of motion detection analysis.
 *
 * This is an output DTO â€” it carries data produced by MotionDetector
 * to AnalysisService for persistence. It is NOT a database model.
 */
class MotionAnalysisResult
{
    /**
     * @param float $averageIntensity
     * @param array $segments
     * @param array $perSecondIntensities
     * @return void
     */
    public function __construct(
        public readonly float $averageIntensity,
        public readonly array $segments,
        public readonly array $perSecondIntensities,
    ) {}
}
