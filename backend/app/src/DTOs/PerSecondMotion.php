<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Immutable value object holding the motion intensity for a single second.
 *
 * This is an output DTO produced by MotionDetector. It replaces the raw
 * associative array ['second' => int, 'intensity' => float] that was
 * previously used internally.
 */
class PerSecondMotion
{
    /**
     * @param int $second
     * @param float $intensity
     * @return void
     */
    public function __construct(
        public readonly int $second,
        public readonly float $intensity,
    ) {}
}
