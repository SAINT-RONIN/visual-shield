<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Immutable value object representing a single flagged time segment produced
 * by the flash or motion detector.
 *
 * This is an output DTO — it carries segment data from a detector through
 * AnalysisService to FlaggedSegmentRepository. It is NOT a database model.
 */
class SegmentData
{
    public function __construct(
        public readonly float $startTime,
        public readonly float $endTime,
        public readonly string $type,
        public readonly string $severity,
        public readonly float $metricValue,
    ) {}
}
