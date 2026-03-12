<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\AnalysisDatapoint;
use App\Models\AnalysisResult;
use App\Models\FlaggedSegment;
use App\Models\Video;
use App\Utils\RiskLevel;

/**
 * Immutable value object that holds the typed models needed for a report
 * and serialises them into a frontend-friendly structure on demand.
 *
 * Storing typed models (rather than pre-serialised arrays) keeps the DTO
 * honest about what it contains and ensures serialisation happens in one
 * place — toArray() — instead of being scattered across factory methods.
 */
class ReportDTO
{
    /**
     * @param Video               $video          The video record.
     * @param AnalysisResult|null $analysisResult The summary metrics (null if not yet analysed).
     * @param FlaggedSegment[]    $segments       Flagged time segments.
     * @param AnalysisDatapoint[] $datapoints     Per-second chart data.
     */
    public function __construct(
        public readonly Video $video,
        public readonly ?AnalysisResult $analysisResult,
        public readonly array $segments,
        public readonly array $datapoints,
    ) {}

    /**
     * Serialise the report to a plain associative array.
     *
     * This is the single point of serialisation — toApiArray() is called
     * on every model here, never earlier (not in the service, not in a
     * factory method).
     */
    public function toArray(): array
    {
        return [
            'video' => $this->buildVideoData(),
            'summary' => $this->buildSummary(),
            'segments' => array_map(fn(FlaggedSegment $s) => $s->toApiArray(), $this->segments),
            'charts' => $this->buildCharts(),
        ];
    }

    // ──────────────────────────────────────────────
    //  Section builders (serialisation only)
    // ──────────────────────────────────────────────

    /** Build the video metadata section of the report. */
    private function buildVideoData(): array
    {
        return [
            'id' => $this->video->id,
            'originalName' => $this->video->originalName,
            'duration' => $this->video->durationSeconds ?? 0.0,
            'samplingRate' => $this->video->samplingRate,
            'effectiveSamplingRate' => $this->analysisResult?->effectiveSamplingRate ?? $this->video->samplingRate,
            'uploadedAt' => $this->video->createdAt,
            'status' => $this->video->status,
        ];
    }

    /**
     * Compute aggregate summary metrics including the overall risk level.
     */
    private function buildSummary(): array
    {
        $totalFlash = $this->analysisResult?->totalFlashEvents ?? 0;
        $highestFreq = $this->analysisResult?->highestFlashFrequency ?? 0.0;
        $avgMotion = $this->analysisResult?->averageMotionIntensity ?? 0.0;

        return [
            'totalFlashEvents' => $totalFlash,
            'highestFlashFrequency' => $highestFreq,
            'averageMotionIntensity' => $avgMotion,
            'overallRiskLevel' => $this->calculateRiskLevel($highestFreq, $avgMotion),
            'flashEventsRisk' => RiskLevel::colorForFlashCount($totalFlash),
            'flashFrequencyRisk' => RiskLevel::colorForFlashFrequency($highestFreq),
            'motionIntensityRisk' => RiskLevel::colorForMotionIntensity($avgMotion),
            'samplingRateRisk' => 'safe',
        ];
    }

    /**
     * Count segment severities and delegate to the shared RiskLevel utility.
     */
    private function calculateRiskLevel(float $highestFreq, float $avgMotion): string
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

        return RiskLevel::determine(
            $highestFreq,
            $avgMotion,
            $highSegments,
            $mediumSegments,
            count($this->segments),
        );
    }

    /**
     * Split AnalysisDatapoint models into three Chart.js-compatible time series.
     */
    private function buildCharts(): array
    {
        $flashFrequency = [];
        $motionIntensity = [];
        $luminance = [];

        foreach ($this->datapoints as $datapoint) {
            $flashFrequency[] = ['time' => $datapoint->timePoint, 'frequency' => $datapoint->flashFrequency];
            $motionIntensity[] = ['time' => $datapoint->timePoint, 'intensity' => $datapoint->motionIntensity];
            $luminance[] = [
                'time' => $datapoint->timePoint,
                'luminance' => $datapoint->luminance,
                'flashDetected' => $datapoint->flashDetected,
            ];
        }

        return [
            'flashFrequency' => $flashFrequency,
            'motionIntensity' => $motionIntensity,
            'luminance' => $luminance,
        ];
    }
}
