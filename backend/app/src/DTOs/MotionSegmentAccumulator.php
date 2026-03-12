<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Mutable accumulator tracking the state of an in-progress motion segment.
 *
 * Used only within MotionDetector while iterating over per-second motion
 * intensities. Once the segment is closed it is converted to a typed
 * SegmentData output DTO.
 *
 * startSecond is readonly because it never changes once a segment starts.
 * peakIntensity is mutable because it is updated with each new high-motion second.
 */
final class MotionSegmentAccumulator
{
    public function __construct(
        public readonly int $startSecond,
        public float $peakIntensity,
    ) {}
}
