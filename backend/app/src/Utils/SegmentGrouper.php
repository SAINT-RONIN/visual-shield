<?php

declare(strict_types=1);

namespace App\Utils;

use App\DTOs\SegmentData;

/**
 * Shared stateless utility for grouping consecutive high-metric seconds into
 * flagged time segments.
 *
 * Both FlashDetector and MotionDetector use the same state-machine logic:
 * iterate per-second entries, open a segment when the metric crosses a threshold,
 * extend it while the metric stays above the threshold, and close it when the
 * metric drops below the threshold (provided the run meets the minimum length).
 *
 * Extracted here to eliminate the duplication between the two detectors.
 */
final class SegmentGrouper
{
    /**
     * Group consecutive entries whose metric is at or above $threshold into
     * SegmentData objects.
     *
     * @template T
     *
     * @param  array<T> $perSecondEntries   Per-second DTOs (PerSecondFlash or PerSecondMotion).
     * @param  callable $getMetric          Extracts the numeric metric from an entry (e.g. frequency or intensity).
     * @param  callable $getSecond          Extracts the integer second from an entry.
     * @param  float    $threshold          Minimum metric value to be considered dangerous/high.
     * @param  string   $type               Segment type label written into SegmentData ('flash' or 'motion').
     * @param  callable $classifySeverity   Maps peak metric (float) to a severity string ('low'|'medium'|'high').
     * @param  int      $minSustainedSeconds Minimum consecutive seconds required before a segment is emitted.
     *
     * @return SegmentData[]
     */
    public static function group(
        array $perSecondEntries,
        callable $getMetric,
        callable $getSecond,
        float $threshold,
        string $type,
        callable $classifySeverity,
        int $minSustainedSeconds = 1,
    ): array {
        $segments = [];

        // State for the current open segment
        $startSecond      = null;  // int|null — second where the current run began
        $peakMetric       = 0.0;   // highest metric value seen in the current run
        $consecutiveCount = 0;     // number of consecutive above-threshold seconds

        foreach ($perSecondEntries as $entry) {
            $metric = (float) $getMetric($entry);
            $second = (int) $getSecond($entry);

            if ($metric >= $threshold) {
                // Start or extend the current segment
                if ($startSecond === null) {
                    $startSecond = $second;
                    $peakMetric  = $metric;
                } else {
                    $peakMetric = max($peakMetric, $metric);
                }

                $consecutiveCount++;
            } else {
                // Metric dropped below threshold — close the segment if it qualifies
                if ($startSecond !== null && $consecutiveCount >= $minSustainedSeconds) {
                    $segments[] = new SegmentData(
                        startTime:   (float) $startSecond,
                        endTime:     (float) $second,
                        type:        $type,
                        severity:    $classifySeverity($peakMetric),
                        metricValue: round($peakMetric, 2),
                    );
                }

                // Reset state regardless of whether the segment qualified
                $startSecond      = null;
                $peakMetric       = 0.0;
                $consecutiveCount = 0;
            }
        }

        // If the data ends while a segment is still open, close it now
        if ($startSecond !== null && $consecutiveCount >= $minSustainedSeconds) {
            $lastSecond = (int) $getSecond(end($perSecondEntries)) + 1;

            $segments[] = new SegmentData(
                startTime:   (float) $startSecond,
                endTime:     (float) $lastSecond,
                type:        $type,
                severity:    $classifySeverity($peakMetric),
                metricValue: round($peakMetric, 2),
            );
        }

        return $segments;
    }
}
