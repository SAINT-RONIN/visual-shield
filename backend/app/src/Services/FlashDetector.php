<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\AnalysisConfig;
use App\DTOs\FlashAnalysisResult;

/**
 * Detects dangerous flash/strobe events in video frames.
 *
 * A "flash" is a sudden jump in brightness between two consecutive frames.
 * Too many flashes per second (3+ per WCAG 2.3.1) can trigger seizures
 * in people with photosensitive epilepsy.
 *
 * This detector:
 *   1. Marks each frame as "flash" or "not flash" based on brightness change
 *   2. Counts how many flashes happen each second
 *   3. Groups consecutive dangerous seconds into flagged time segments
 */
class FlashDetector
{
    /**
     * Analyze pre-computed frame data for flash events.
     *
     * @param  array $perFrameData Each frame's luminance and motion data (from AnalysisService).
     * @param  int   $samplingRate How many frames per second were extracted.
     * @return FlashAnalysisResult Contains total events, peak frequency, segments, and per-second data.
     */
    public function detectFromData(array $perFrameData, int $samplingRate): FlashAnalysisResult
    {
        $flashFrames = $this->tagEachFrameAsFlashOrNot($perFrameData);
        $perSecondFrequencies = $this->countFlashesPerSecond($flashFrames, $samplingRate, count($perFrameData));
        $flaggedSegments = $this->groupDangerousSecondsIntoSegments($perSecondFrequencies);

        $totalFlashEvents = $this->countTotalFlashEvents($flashFrames);
        $highestFrequency = $this->findHighestFrequency($perSecondFrequencies);

        return new FlashAnalysisResult($totalFlashEvents, $highestFrequency, $flaggedSegments, $perSecondFrequencies);
    }

    // ──────────────────────────────────────────────
    //  Step 1: Tag each frame
    // ──────────────────────────────────────────────

    /**
     * Mark each frame as a flash (1) or not a flash (0).
     *
     * A frame counts as a flash when its brightness changed by more than
     * FLASH_THRESHOLD compared to the previous frame. Frame 0 is skipped
     * because it has no previous frame to compare against.
     */
    private function tagEachFrameAsFlashOrNot(array $perFrameData): array
    {
        $flashTags = [];

        foreach ($perFrameData as $frameIndex => $frame) {
            if ($frameIndex === 0) {
                continue;
            }

            $brightnessChangedEnough = $frame['luminanceDiff'] >= AnalysisConfig::FLASH_THRESHOLD;
            $flashTags[$frameIndex] = $brightnessChangedEnough ? 1 : 0;
        }

        return $flashTags;
    }

    // ──────────────────────────────────────────────
    //  Step 2: Count flashes per second
    // ──────────────────────────────────────────────

    /**
     * Count how many flash events occurred in each 1-second window.
     *
     * For example, at 10fps: frames 1–10 belong to second 0,
     * frames 11–20 belong to second 1, etc.
     */
    private function countFlashesPerSecond(array $flashTags, int $samplingRate, int $totalFrames): array
    {
        $totalSeconds = (int) ceil($totalFrames / $samplingRate);
        $perSecondCounts = [];

        for ($second = 0; $second < $totalSeconds; $second++) {
            $flashCount = $this->countFlashesInSecond($flashTags, $second, $samplingRate, $totalFrames);

            $perSecondCounts[] = [
                'second' => $second,
                'frequency' => (float) $flashCount,
            ];
        }

        return $perSecondCounts;
    }

    /** Count flash events within a specific 1-second window of frames. */
    private function countFlashesInSecond(array $flashTags, int $second, int $samplingRate, int $totalFrames): int
    {
        $firstFrameInSecond = $second * $samplingRate + 1;
        $lastFrameInSecond = min(($second + 1) * $samplingRate, $totalFrames);
        $count = 0;

        for ($frame = $firstFrameInSecond; $frame < $lastFrameInSecond; $frame++) {
            $count += $flashTags[$frame] ?? 0;
        }

        return $count;
    }

    // ──────────────────────────────────────────────
    //  Step 3: Group dangerous seconds into segments
    // ──────────────────────────────────────────────

    /**
     * Merge consecutive dangerous seconds into flagged time segments.
     *
     * For example, if seconds 5, 6, 7 are all dangerous, they become
     * one segment: { startTime: 5.0, endTime: 8.0 }.
     */
    private function groupDangerousSecondsIntoSegments(array $perSecondFrequencies): array
    {
        $segments = [];
        $currentSegment = null;

        foreach ($perSecondFrequencies as $entry) {
            $isDangerous = $entry['frequency'] >= AnalysisConfig::FLASH_FREQUENCY_DANGER;

            if ($isDangerous && $currentSegment === null) {
                // Start a new segment
                $currentSegment = $this->startNewSegment($entry);
            } elseif ($isDangerous && $currentSegment !== null) {
                // Extend the current segment
                $currentSegment = $this->extendSegment($currentSegment, $entry['frequency']);
            } elseif (!$isDangerous && $currentSegment !== null) {
                // Close the current segment
                $segments[] = $this->closeSegment($currentSegment, $entry['second']);
                $currentSegment = null;
            }
        }

        // If the video ends while still in a dangerous segment, close it
        if ($currentSegment !== null) {
            $lastSecond = end($perSecondFrequencies)['second'] + 1;
            $segments[] = $this->closeSegment($currentSegment, $lastSecond);
        }

        return $segments;
    }

    // ──────────────────────────────────────────────
    //  Segment building helpers
    // ──────────────────────────────────────────────

    private function startNewSegment(array $entry): array
    {
        return [
            'startSecond' => $entry['second'],
            'peakFrequency' => $entry['frequency'],
        ];
    }

    private function extendSegment(array $segment, float $frequency): array
    {
        $segment['peakFrequency'] = max($segment['peakFrequency'], $frequency);

        return $segment;
    }

    /** Convert a tracking segment into the final output format. */
    private function closeSegment(array $segment, int $endSecond): array
    {
        return [
            'startTime' => (float) $segment['startSecond'],
            'endTime' => (float) $endSecond,
            'type' => 'flash',
            'severity' => $this->classifySeverity($segment['peakFrequency']),
            'metricValue' => round($segment['peakFrequency'], 2),
        ];
    }

    // ──────────────────────────────────────────────
    //  Aggregation helpers
    // ──────────────────────────────────────────────

    /** Add up all the flash events across every frame. */
    private function countTotalFlashEvents(array $flashTags): int
    {
        $total = 0;

        foreach ($flashTags as $isFlash) {
            $total += $isFlash;
        }

        return $total;
    }

    /** Find the highest flash-per-second value across all seconds. */
    private function findHighestFrequency(array $perSecondFrequencies): float
    {
        $highest = 0.0;

        foreach ($perSecondFrequencies as $entry) {
            if ($entry['frequency'] > $highest) {
                $highest = $entry['frequency'];
            }
        }

        return $highest;
    }

    /**
     * Classify how severe a flash frequency is.
     *   - Over 10 flashes/sec = high (very dangerous)
     *   - Over 5 flashes/sec  = medium (concerning)
     *   - 3–5 flashes/sec     = low (slightly risky)
     */
    private function classifySeverity(float $frequency): string
    {
        if ($frequency > AnalysisConfig::FLASH_SEVERITY_HIGH) {
            return 'high';
        }

        if ($frequency > AnalysisConfig::FLASH_SEVERITY_MEDIUM) {
            return 'medium';
        }

        return 'low';
    }
}
