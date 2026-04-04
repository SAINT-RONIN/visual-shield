<?php

declare(strict_types=1);

namespace App\Utils;

use App\Config\AnalysisConfig;
use App\DTOs\FrameData;
use App\DTOs\MotionAnalysisResult;
use App\DTOs\PerSecondMotion;

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
     * @param  FrameData[] $perFrameData Each frame's luminance and motion data (from AnalysisService).
     * @param  int         $samplingRate How many frames per second were extracted.
     * @return MotionAnalysisResult Contains average intensity, flagged segments, and per-second data.
     */
    public function detectFromData(array $perFrameData, int $samplingRate): MotionAnalysisResult
    {
        $perSecondIntensities = $this->calculateMotionPerSecond($perFrameData, $samplingRate);
        $flaggedSegments = $this->groupHighMotionSecondsIntoSegments($perSecondIntensities);
        $overallAverageIntensity = $this->calculateOverallAverage($perSecondIntensities);

        return new MotionAnalysisResult($overallAverageIntensity, $flaggedSegments, $perSecondIntensities);
    }

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    //  Step 1: Average motion per second
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * Calculate the average motion intensity for each 1-second window.
     *
     * Frame 0 is skipped because motion is measured as the difference
     * between two consecutive frames, and frame 0 has no predecessor.
     *
     * @param  FrameData[] $perFrameData
     * @return PerSecondMotion[]
     */
    private function calculateMotionPerSecond(array $perFrameData, int $samplingRate): array
    {
        $totalFrames = count($perFrameData);
        $totalSeconds = (int) ceil($totalFrames / $samplingRate);
        $perSecondResults = [];

        for ($second = 0; $second < $totalSeconds; $second++) {
            $averageIntensity = $this->averageMotionInSecond($perFrameData, $second, $samplingRate, $totalFrames);

            $perSecondResults[] = new PerSecondMotion(
                second: $second,
                intensity: round($averageIntensity, 2),
            );
        }

        return $perSecondResults;
    }

    /**
     * Calculate the average motion intensity for frames within a specific second.
     *
     * @param FrameData[] $perFrameData
     */
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
            // Skip frame 0 â€” it has no predecessor to compare against
            if ($frame === 0) {
                continue;
            }

            $sum += $perFrameData[$frame]->motionIntensity;
            $count++;
        }

        if ($count === 0) {
            return 0.0;
        }

        return $sum / $count;
    }

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    //  Step 2: Group high-motion seconds into segments
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * Merge consecutive high-motion seconds into flagged time segments.
     *
     * Only creates a segment if the run of high-intensity seconds meets
     * the MIN_SUSTAINED_SECONDS threshold. This filters out brief spikes
     * that are unlikely to cause viewer discomfort.
     *
     * @param  PerSecondMotion[] $perSecondIntensities
     * @return SegmentData[]
     */
    private function groupHighMotionSecondsIntoSegments(array $perSecondIntensities): array
    {
        return SegmentGrouper::group(
            perSecondEntries: $perSecondIntensities,
            getMetric: fn(PerSecondMotion $e) => $e->intensity,
            getSecond: fn(PerSecondMotion $e) => $e->second,
            threshold: AnalysisConfig::MOTION_THRESHOLD,
            type: 'motion',
            classifySeverity: $this->classifySeverity(...),
            minSustainedSeconds: self::MIN_SUSTAINED_SECONDS,
        );
    }

    /**
     * Classify how severe a motion intensity is.
     *   - Over 120 = high (very jarring camera movement)
     *   - Over 60  = medium (noticeable fast motion)
     *   - 30â€“60    = low (slightly elevated motion)
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

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    //  Aggregation helpers
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * Calculate the average motion intensity across all seconds of the video.
     *
     * @param PerSecondMotion[] $perSecondIntensities
     */
    private function calculateOverallAverage(array $perSecondIntensities): float
    {
        if (empty($perSecondIntensities)) {
            return 0.0;
        }

        $sum = 0.0;

        foreach ($perSecondIntensities as $entry) {
            $sum += $entry->intensity;
        }

        return round($sum / count($perSecondIntensities), 2);
    }
}
