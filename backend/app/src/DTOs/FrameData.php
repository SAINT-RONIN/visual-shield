<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Immutable value object holding per-frame analysis measurements.
 *
 * Replaces the raw associative array that AnalysisService previously
 * passed between computePerFrameData(), FlashDetector, and MotionDetector.
 * Typed access removes the reliance on string array keys across those
 * three classes.
 */
final readonly class FrameData
{
    /**
     * @param float $luminance
     * @param float $luminanceDiff
     * @param float $motionIntensity
     * @return void
     */
    public function __construct(
        public float $luminance,
        public float $luminanceDiff,
        public float $motionIntensity,
    ) {}
}
