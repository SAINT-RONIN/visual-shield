<?php

namespace App\Models;

class FlashAnalysisResult
{
    public function __construct(
        public readonly int $totalEvents,
        public readonly float $highestFrequency,
        public readonly array $segments,
        public readonly array $perSecondFrequencies,
    ) {}
}
