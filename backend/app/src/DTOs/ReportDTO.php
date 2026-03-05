<?php

namespace App\DTOs;

class ReportDTO
{
    public function __construct(
        public readonly array $video,
        public readonly array $summary,
        public readonly array $segments,
        public readonly array $charts,
    ) {}

    public static function fromData(array $video, array $analysisResult, array $segments, array $datapoints): self
    {
        return new self(
            video: self::buildVideoData($video, $analysisResult),
            summary: self::buildSummary($analysisResult, $segments),
            segments: self::formatSegments($segments),
            charts: self::buildCharts($datapoints),
        );
    }

    public function toArray(): array
    {
        return [
            'video' => $this->video,
            'summary' => $this->summary,
            'segments' => $this->segments,
            'charts' => $this->charts,
        ];
    }

    private static function buildVideoData(array $video, array $analysisResult): array
    {
        return [
            'id' => (int) $video['id'],
            'originalName' => $video['original_name'],
            'duration' => (float) ($video['duration_seconds'] ?? 0),
            'samplingRate' => (int) $video['sampling_rate'],
            'effectiveSamplingRate' => (int) ($analysisResult['effective_sampling_rate'] ?? $video['sampling_rate']),
            'uploadedAt' => $video['created_at'],
            'status' => $video['status'],
        ];
    }

    private static function buildSummary(array $analysisResult, array $segments): array
    {
        $highestFreq = (float) ($analysisResult['highest_flash_frequency'] ?? 0);
        $avgMotion = (float) ($analysisResult['average_motion_intensity'] ?? 0);

        return [
            'totalFlashEvents' => (int) ($analysisResult['total_flash_events'] ?? 0),
            'highestFlashFrequency' => $highestFreq,
            'averageMotionIntensity' => $avgMotion,
            'overallRiskLevel' => self::determineRiskLevel($highestFreq, $avgMotion, $segments),
        ];
    }

    private static function determineRiskLevel(float $highestFreq, float $avgMotion, array $segments): string
    {
        $hasHighSeverity = false;
        $hasMediumSeverity = false;

        foreach ($segments as $seg) {
            if ($seg['severity'] === 'high') {
                $hasHighSeverity = true;
            } elseif ($seg['severity'] === 'medium') {
                $hasMediumSeverity = true;
            }
        }

        if ($hasHighSeverity || $highestFreq > 10 || $avgMotion > 120) {
            return 'high';
        }

        if ($hasMediumSeverity || $highestFreq > 5 || $avgMotion > 60) {
            return 'medium';
        }

        if (!empty($segments) || $highestFreq > 3 || $avgMotion > 30) {
            return 'low';
        }

        return 'safe';
    }

    private static function formatSegments(array $segments): array
    {
        $formatted = [];

        foreach ($segments as $s) {
            $formatted[] = [
                'startTime' => (float) $s['start_time'],
                'endTime' => (float) $s['end_time'],
                'type' => $s['segment_type'],
                'severity' => $s['severity'],
                'metricValue' => (float) ($s['metric_value'] ?? 0),
            ];
        }

        return $formatted;
    }

    private static function buildCharts(array $datapoints): array
    {
        $flashFrequency = [];
        $motionIntensity = [];
        $luminance = [];

        foreach ($datapoints as $dp) {
            $time = (float) $dp['time_point'];
            $flashFrequency[] = ['time' => $time, 'frequency' => (float) $dp['flash_frequency']];
            $motionIntensity[] = ['time' => $time, 'intensity' => (float) $dp['motion_intensity']];
            $luminance[] = [
                'time' => $time,
                'luminance' => (float) $dp['luminance'],
                'flashDetected' => (bool) $dp['flash_detected'],
            ];
        }

        return [
            'flashFrequency' => $flashFrequency,
            'motionIntensity' => $motionIntensity,
            'luminance' => $luminance,
        ];
    }
}
