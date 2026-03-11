<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Immutable value object holding the results of flash detection analysis.
 *
 * This is an output DTO — it carries data produced by FlashDetector
 * to AnalysisService for persistence. It is NOT a database model.
 */
class FlashAnalysisResult
{
    /**
     * @param int   $totalEvents          Total number of frames flagged as flash events.
     * @param float $highestFrequency     Peak flashes-per-second observed in any 1s window.
     * @param array $segments             Flagged time segments where flash rate exceeded the danger threshold.
     *                                    Each entry: {startTime, endTime, type, severity, metricValue}.
     * @param array $perSecondFrequencies Flash count per second across the entire video.
     *                                    Each entry: {second, frequency}.
     */
    public function __construct(
        public readonly int $totalEvents,
        public readonly float $highestFrequency,
        public readonly array $segments,
        public readonly array $perSecondFrequencies,
    ) {}
}
