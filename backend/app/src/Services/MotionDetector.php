<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\AnalysisConfig;
use App\DTOs\MotionAnalysisResult;

/**
 * Detects excessive or rapid motion in video frames.
 *
 * High motion intensity can cause discomfort or vestibular triggers for
 * sensitive viewers (think: rapid camera shaking, fast panning, etc.).
 *
 * This detector:
 *   1. Averages motion intensity for each 1-second window
 *   2. Flags consecutive high-motion seconds as dangerous segments
 *   3. Only reports segments that last at least MIN_SUSTAINED_SECONDS
 *      (brief spikes are filtered out since they rarely cause discomfort)
 */
class MotionDetector
{
    /** A segment must last at least this many consecutive seconds to be flagged. */
    private const MIN_SUSTAINED_SECONDS = 1;

    /**
     * Analyze pre-computed frame data for excessive motion.
     *
     * @param  array $perFrameData Each frame's luminance and motion data (from AnalysisService).
     * @param  int   $samplingRate How many frames per second were extracted.
     * @return MotionAnalysisResult Contains average intensity, flagged segments, and per-second data.
     */
    public function detectFromData(array $perFrameData, int $samplingRate): MotionAnalysisResult
    {
        $perSecondIntensities = $this->calculateMotionPerSecond($perFrameData, $samplingRate);
        $flaggedSegments = $this->groupHighMotionSecondsIntoSegments($perSecondIntensities);
        $overallAverageIntensity = $this->calculateOverallAverage($perSecondIntensities);

        return new MotionAnalysisResult($overallAverageIntensity, $flaggedSegments, $perSecondIntensities);
    }

    // ──────────────────────────────────────────────
    //  Step 1: Average motion per second
    // ──────────────────────────────────────────────

    /**
     * Calculate the average motion intensity for each 1-second window.
     *
     * Frame 0 is skipped because motion is measured as the difference
     * between two consecutive frames, and frame 0 has no predecessor.
     */
    private function calculateMotionPerSecond(array $perFrameData, int $samplingRate): array
    {
        $totalFrames = count($perFrameData);
        $totalSeconds = (int) ceil($totalFrames / $samplingRate);
        $perSecondResults = [];

        for ($second = 0; $second < $totalSeconds; $second++) {
            $averageIntensity = $this->averageMotionInSecond($perFrameData, $second, $samplingRate, $totalFrames);

            $perSecondResults[] = [
                'second' => $second,
                'intensity' => round($averageIntensity, 2),
            ];
        }

        return $perSecondResults;
    }

    /** Calculate the average motion intensity for frames within a specific second. */
    private function averageMotionInSecond(
        array $perFrameData,
        int $second,
        int $samplingRate,
        int $totalFrames,
    ): float {
        $startFrame = $second * $samplingRate;
        $endFrame = min(($second + 1) * $samplingRate, $totalFrames);

        $sum = 0.0;
        $count = 0;

        for ($frame = $startFrame; $frame < $endFrame; $frame++) {
            // Skip frame 0 — it has no predecessor to compare against
            if ($frame === 0) {
                continue;
            }

            $sum += $perFrameData[$frame]['motionIntensity'];
            $count++;
        }

        if ($count === 0) {
            return 0.0;
        }

        return $sum / $count;
    }

    // ──────────────────────────────────────────────
    //  Step 2: Group high-motion seconds into segments
    // ──────────────────────────────────────────────

    /**
     * Merge consecutive high-motion seconds into flagged time segments.
     *
     * Only creates a segment if the run of high-intensity seconds meets
     * the MIN_SUSTAINED_SECONDS threshold. This filters out brief spikes
     * that are unlikely to cause viewer discomfort.
     */
    private function groupHighMotionSecondsIntoSegments(array $perSecondIntensities): array
    {
        $completedSegments = [];
        $currentSegment = null;
        $consecutiveHighSeconds = 0;

        foreach ($perSecondIntensities as $entry) {
            $isHighMotion = $entry['intensity'] >= AnalysisConfig::MOTION_THRESHOLD;

            if ($isHighMotion) {
                $result = $this->handleHighMotionSecond($currentSegment, $consecutiveHighSeconds, $entry);
                $currentSegment = $result['segment'];
                $consecutiveHighSeconds = $result['count'];
            } else {
                // The high-motion streak ended — save the segment if it was long enough
                if ($currentSegment !== null && $consecutiveHighSeconds >= self::MIN_SUSTAINED_SECONDS) {
                    $completedSegments[] = $this->closeSegment($currentSegment, $entry['second']);
                }
                $currentSegment = null;
                $consecutiveHighSeconds = 0;
            }
        }

        // If the video ends during a high-motion segment, close it
        if ($currentSegment !== null && $consecutiveHighSeconds >= self::MIN_SUSTAINED_SECONDS) {
            $lastSecond = end($perSecondIntensities)['second'] + 1;
            $completedSegments[] = $this->closeSegment($currentSegment, $lastSecond);
        }

        return $completedSegments;
    }

    /** Either start a new segment or extend the current one. */
    private function handleHighMotionSecond(
        ?array $currentSegment,
        int $consecutiveHighSeconds,
        array $entry,
    ): array {
        if ($currentSegment === null) {
            // Start a new segment
            return [
                'segment' => [
                    'startSecond' => $entry['second'],
                    'peakIntensity' => $entry['intensity'],
                ],
                'count' => 1,
            ];
        }

        // Extend the existing segment
        $currentSegment['peakIntensity'] = max($currentSegment['peakIntensity'], $entry['intensity']);

        return [
            'segment' => $currentSegment,
            'count' => $consecutiveHighSeconds + 1,
        ];
    }

    // ──────────────────────────────────────────────
    //  Segment building helpers
    // ──────────────────────────────────────────────

    /** Convert a tracking segment into the final output format. */
    private function closeSegment(array $segment, int $endSecond): array
    {
        return [
            'startTime' => (float) $segment['startSecond'],
            'endTime' => (float) $endSecond,
            'type' => 'motion',
            'severity' => $this->classifySeverity($segment['peakIntensity']),
            'metricValue' => round($segment['peakIntensity'], 2),
        ];
    }

    /**
     * Classify how severe a motion intensity is.
     *   - Over 120 = high (very jarring camera movement)
     *   - Over 60  = medium (noticeable fast motion)
     *   - 30–60    = low (slightly elevated motion)
     */
    private function classifySeverity(float $intensity): string
    {
        if ($intensity > AnalysisConfig::MOTION_SEVERITY_HIGH) {
            return 'high';
        }

        if ($intensity > AnalysisConfig::MOTION_SEVERITY_MEDIUM) {
            return 'medium';
        }

        return 'low';
    }

    // ──────────────────────────────────────────────
    //  Aggregation helpers
    // ──────────────────────────────────────────────

    /** Calculate the average motion intensity across all seconds of the video. */
    private function calculateOverallAverage(array $perSecondIntensities): float
    {
        if (empty($perSecondIntensities)) {
            return 0.0;
        }

        $sum = 0.0;

        foreach ($perSecondIntensities as $entry) {
            $sum += $entry['intensity'];
        }

        return round($sum / count($perSecondIntensities), 2);
    }
}
