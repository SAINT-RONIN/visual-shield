<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\AnalysisDatapoint;
use App\Models\AnalysisResult;
use App\Models\FlaggedSegment;
use App\Models\Video;
use App\Utils\RiskLevel;

/**
 * Immutable value object that transforms typed models into a
 * frontend-friendly report structure with video, summary, segments,
 * and charts sections.
 *
 * Centralises all the reshaping so every consumer (API endpoint,
 * JSON export, CSV export) receives an identical structure.
 */
class ReportDTO
{
    /**
     * @param array $video    Formatted video metadata (id, originalName, duration, etc.).
     * @param array $summary  Aggregated metrics and overall risk level.
     * @param array $segments Hazardous time-range entries (flash/motion).
     * @param array $charts   Time-series arrays for Chart.js (flashFrequency, motionIntensity, luminance).
     */
    public function __construct(
        public readonly array $video,
        public readonly array $summary,
        public readonly array $segments,
        public readonly array $charts,
    ) {}

    /**
     * Build a complete ReportDTO from typed models.
     *
     * @param Video               $video          The video record.
     * @param AnalysisResult|null $analysisResult The summary metrics (null if not yet analysed).
     * @param FlaggedSegment[]    $segments       Flagged time segments.
     * @param AnalysisDatapoint[] $datapoints     Per-second chart data.
     */
    public static function fromData(
        Video $video,
        ?AnalysisResult $analysisResult,
        array $segments,
        array $datapoints,
    ): self {
        return new self(
            video: self::buildVideoData($video, $analysisResult),
            summary: self::buildSummary($analysisResult, $segments),
            segments: self::formatSegments($segments),
            charts: self::buildCharts($datapoints),
        );
    }

    /**
     * Serialise the report to a plain associative array.
     *
     * Used by both the API JSON response and the downloadable export.
     */
    public function toArray(): array
    {
        return [
            'video' => $this->video,
            'summary' => $this->summary,
            'segments' => $this->segments,
            'charts' => $this->charts,
        ];
    }

    // ──────────────────────────────────────────────
    //  Section builders
    // ──────────────────────────────────────────────

    /** Build the video metadata section of the report. */
    private static function buildVideoData(Video $video, ?AnalysisResult $analysisResult): array
    {
        return [
            'id' => $video->id,
            'originalName' => $video->originalName,
            'duration' => $video->durationSeconds ?? 0.0,
            'samplingRate' => $video->samplingRate,
            'effectiveSamplingRate' => $analysisResult?->effectiveSamplingRate ?? $video->samplingRate,
            'uploadedAt' => $video->createdAt,
            'status' => $video->status,
        ];
    }

    /**
     * Compute aggregate summary metrics including the overall risk level.
     *
     * @param AnalysisResult|null $analysisResult Summary metrics.
     * @param FlaggedSegment[]    $segments       Used for severity counting.
     */
    private static function buildSummary(?AnalysisResult $analysisResult, array $segments): array
    {
        $totalFlash = $analysisResult?->totalFlashEvents ?? 0;
        $highestFreq = $analysisResult?->highestFlashFrequency ?? 0.0;
        $avgMotion = $analysisResult?->averageMotionIntensity ?? 0.0;

        return [
            'totalFlashEvents' => $totalFlash,
            'highestFlashFrequency' => $highestFreq,
            'averageMotionIntensity' => $avgMotion,
            'overallRiskLevel' => self::calculateRiskLevel($highestFreq, $avgMotion, $segments),
            'flashEventsRisk' => RiskLevel::colorForFlashCount($totalFlash),
            'flashFrequencyRisk' => RiskLevel::colorForFlashFrequency($highestFreq),
            'motionIntensityRisk' => RiskLevel::colorForMotionIntensity($avgMotion),
            'samplingRateRisk' => 'safe',
        ];
    }

    /**
     * Count segment severities and delegate to the shared RiskLevel utility.
     *
     * @param FlaggedSegment[] $segments Typed segment objects.
     */
    private static function calculateRiskLevel(float $highestFreq, float $avgMotion, array $segments): string
    {
        $highSegments = 0;
        $mediumSegments = 0;

        foreach ($segments as $segment) {
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
            count($segments),
        );
    }

    /**
     * Convert FlaggedSegment models into camelCase frontend arrays.
     *
     * @param FlaggedSegment[] $segments Typed segment objects.
     */
    private static function formatSegments(array $segments): array
    {
        $formatted = [];

        foreach ($segments as $segment) {
            $formatted[] = $segment->toApiArray();
        }

        return $formatted;
    }

    /**
     * Split AnalysisDatapoint models into three Chart.js-compatible time series.
     *
     * @param AnalysisDatapoint[] $datapoints Typed datapoint objects.
     */
    private static function buildCharts(array $datapoints): array
    {
        $flashFrequency = [];
        $motionIntensity = [];
        $luminance = [];

        foreach ($datapoints as $datapoint) {
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
