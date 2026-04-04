<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Immutable value object holding the average luminance for a single second.
 *
 * This is an output DTO produced by AnalysisService::calculateAverageLuminancePerSecond().
 * It replaces the raw associative array ['second' => int, 'luminance' => float]
 * that was previously used internally.
 */
class PerSecondLuminance
{
    /**
     * @param int $second
     * @param float $luminance
     * @return void
     */
    public function __construct(
        public readonly int $second,
        public readonly float $luminance,
    ) {}
}
