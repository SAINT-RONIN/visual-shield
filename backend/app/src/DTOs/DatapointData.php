<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Immutable value object representing one merged per-second analysis datapoint.
 *
 * This is an output DTO â€” it carries the combined flash, motion, and luminance
 * data for a single second from AnalysisService to AnalysisDatapointRepository.
 * It is NOT a database model.
 */
class DatapointData
{
    public function __construct(
        public readonly float $timePoint,
        public readonly float $flashFrequency,
        public readonly float $motionIntensity,
        public readonly float $luminance,
        public readonly bool $flashDetected,
    ) {}
}
