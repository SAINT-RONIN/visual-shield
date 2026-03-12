<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Mutable accumulator tracking the state of an in-progress flash segment.
 *
 * Used only within FlashDetector while iterating over per-second flash
 * frequencies. Once the segment is closed it is converted to a typed
 * SegmentData output DTO.
 *
 * startSecond is readonly because it never changes once a segment starts.
 * peakFrequency is mutable because it is updated with each new dangerous second.
 */
final class FlashSegmentAccumulator
{
    public function __construct(
        public readonly int $startSecond,
        public float $peakFrequency,
    ) {}
}
