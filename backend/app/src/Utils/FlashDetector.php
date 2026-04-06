<?php

declare(strict_types=1);

namespace App\Utils;

use App\Config\AnalysisConfig;
use App\DTOs\FlashAnalysisResult;
use App\DTOs\FrameData;
use App\DTOs\FrameFlashTags;
use App\DTOs\PerSecondFlash;

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
     * @param  FrameData[] $perFrameData Each frame's luminance and motion data (from AnalysisService).
     * @param  int         $samplingRate How many frames per second were extracted.
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

    //  Step 1: Tag each frame

    /**
     * Mark each frame as a flash (1) or not a flash (0).
     *
     * A frame counts as a flash when its brightness changed by more than
     * FLASH_THRESHOLD compared to the previous frame. Frame 0 is skipped
     * because it has no previous frame to compare against.
     *
     * @param FrameData[] $perFrameData
     */
    private function tagEachFrameAsFlashOrNot(array $perFrameData): FrameFlashTags
    {
        $tags = [];

        foreach ($perFrameData as $frameIndex => $frame) {
            if ($frameIndex === 0) {
                continue;
            }

            $brightnessChangedEnough = $frame->luminanceDiff >= AnalysisConfig::FLASH_THRESHOLD;
            $tags[$frameIndex] = $brightnessChangedEnough ? 1 : 0;
        }

        return new FrameFlashTags(tags: $tags);
    }

    //  Step 2: Count flashes per second

    /**
     * Count how many flash events occurred in each 1-second window.
     *
     * For example, at 10fps: frames 1â€“10 belong to second 0,
     * frames 11â€“20 belong to second 1, etc.
     *
     * @return PerSecondFlash[]
     */
    private function countFlashesPerSecond(FrameFlashTags $flashTags, int $samplingRate, int $totalFrames): array
    {
        $totalSeconds = (int) ceil($totalFrames / $samplingRate);
        $perSecondCounts = [];

        for ($second = 0; $second < $totalSeconds; $second++) {
            $flashCount = $this->countFlashesInSecond($flashTags, $second, $samplingRate, $totalFrames);

            $perSecondCounts[] = new PerSecondFlash(
                second: $second,
                frequency: (float) $flashCount,
            );
        }

        return $perSecondCounts;
    }

    /** Count flash events within a specific 1-second window of frames. */
    /**
     * @param FrameFlashTags $flashTags
     * @param int $second
     * @param int $samplingRate
     * @param int $totalFrames
     * @return int
     */
    private function countFlashesInSecond(FrameFlashTags $flashTags, int $second, int $samplingRate, int $totalFrames): int
    {
        $firstFrameInSecond = $second * $samplingRate + 1;
        $lastFrameInSecond = min(($second + 1) * $samplingRate, $totalFrames);
        $count = 0;

        for ($frame = $firstFrameInSecond; $frame < $lastFrameInSecond; $frame++) {
            $count += $flashTags->tags[$frame] ?? 0;
        }

        return $count;
    }

    //  Step 3: Group dangerous seconds into segments

    /**
     * Merge consecutive dangerous seconds into flagged time segments.
     *
     * For example, if seconds 5, 6, 7 are all dangerous, they become
     * one segment: { startTime: 5.0, endTime: 8.0 }.
     *
     * @param  PerSecondFlash[] $perSecondFrequencies
     * @return SegmentData[]
     */
    private function groupDangerousSecondsIntoSegments(array $perSecondFrequencies): array
    {
        return SegmentGrouper::group(
            perSecondEntries: $perSecondFrequencies,
            getMetric: fn(PerSecondFlash $e) => (float) $e->frequency,
            getSecond: fn(PerSecondFlash $e) => $e->second,
            threshold: AnalysisConfig::FLASH_FREQUENCY_DANGER,
            type: 'flash',
            classifySeverity: $this->classifySeverity(...),
        );
    }

    //  Aggregation helpers

    /** Add up all the flash events across every frame. */
    private function countTotalFlashEvents(FrameFlashTags $flashTags): int
    {
        return $flashTags->count();
    }

    /**
     * Find the highest flash-per-second value across all seconds.
     *
     * @param PerSecondFlash[] $perSecondFrequencies
     */
    private function findHighestFrequency(array $perSecondFrequencies): float
    {
        $highest = 0.0;

        foreach ($perSecondFrequencies as $entry) {
            if ($entry->frequency > $highest) {
                $highest = $entry->frequency;
            }
        }

        return $highest;
    }

    /**
     * Classify how severe a flash frequency is.
     *   - Over 10 flashes/sec = high (very dangerous)
     *   - Over 5 flashes/sec  = medium (concerning)
     *   - 3â€“5 flashes/sec     = low (slightly risky)
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
