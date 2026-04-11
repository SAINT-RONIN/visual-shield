<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Immutable value object holding the results of flash detection analysis.
 *
 * This is an output DTO â€” it carries data produced by FlashDetector
 * to AnalysisService for persistence. It is NOT a database model.
 */
class FlashAnalysisResult
{
    public function __construct(
        public readonly int $totalEvents,
        public readonly float $highestFrequency,
        public readonly array $segments,
        public readonly array $perSecondFrequencies,
    ) {}
}
