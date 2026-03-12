<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Immutable value object holding the result of a two-frame GD pixel analysis.
 *
 * This is an output DTO produced by ImageAnalyzer::analyzeFramePair(). It
 * replaces the raw associative array that was previously returned, ensuring
 * no raw arrays cross the Utils → Service boundary.
 */
class FramePairAnalysis
{
    public function __construct(
        public readonly float $luminance1,
        public readonly float $luminance2,
        public readonly float $motionIntensity,
    ) {}
}
