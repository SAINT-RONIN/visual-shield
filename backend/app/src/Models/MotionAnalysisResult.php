<?php

namespace App\Models;

class MotionAnalysisResult
{
    public function __construct(
        public readonly float $averageIntensity,
        public readonly array $segments,
        public readonly array $perSecondIntensities,
    ) {}
}
