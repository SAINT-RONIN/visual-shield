<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\AnalysisDatapoint;
use App\Models\AnalysisResult;
use App\Models\FlaggedSegment;
use App\Models\Video;
use App\Utils\RiskLevel;

/**
 * Immutable value object that holds the typed models needed for a report.
 *
 * This is a pure typed property bag â€” serialisation is the controller's
 * responsibility. No toArray() or toApiArray() calls live here.
 */
class ReportDTO
{
    public function __construct(
        public readonly Video $video,
        public readonly ?AnalysisResult $analysisResult,
        public readonly array $segments,
        public readonly array $datapoints,
    ) {}

    // Pure domain computation over the typed segment models; not serialisation.
    public function calculateRiskLevel(): string
    {
        $highSegments = 0;
        $mediumSegments = 0;

        foreach ($this->segments as $segment) {
            if ($segment->severity === 'high') {
                $highSegments++;
            } elseif ($segment->severity === 'medium') {
                $mediumSegments++;
            }
        }

        $highestFreq = $this->analysisResult?->highestFlashFrequency ?? 0.0;
        $avgMotion = $this->analysisResult?->averageMotionIntensity ?? 0.0;

        return RiskLevel::determine(
            $highestFreq,
            $avgMotion,
            $highSegments,
            $mediumSegments,
            count($this->segments),
        );
    }
}
