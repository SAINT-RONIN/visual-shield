<?php

namespace App\Services;

use App\Config\AnalysisConfig;
use App\Models\FlashAnalysisResult;

class FlashDetector
{
    public function detectFromData(array $perFrameData, int $samplingRate): FlashAnalysisResult
    {
        $flashFrames = $this->identifyFlashFrames($perFrameData);
        $perSecondFrequencies = $this->groupBySecond($flashFrames, $samplingRate, count($perFrameData));
        $segments = $this->buildFlaggedSegments($perSecondFrequencies);

        $totalEvents = 0;
        foreach ($flashFrames as $isFlash) {
            $totalEvents += $isFlash;
        }

        $highestFrequency = 0.0;
        foreach ($perSecondFrequencies as $entry) {
            if ($entry['frequency'] > $highestFrequency) {
                $highestFrequency = $entry['frequency'];
            }
        }

        return new FlashAnalysisResult($totalEvents, $highestFrequency, $segments, $perSecondFrequencies);
    }

    // Returns [frameIndex => 1 or 0] — whether each frame is a flash event.
    private function identifyFlashFrames(array $perFrameData): array
    {
        $events = [];

        foreach ($perFrameData as $i => $frame) {
            if ($i === 0) {
                continue;
            }
            $events[$i] = $frame['luminanceDiff'] >= AnalysisConfig::FLASH_THRESHOLD ? 1 : 0;
        }

        return $events;
    }

    // Counts flash events per second. Raw count = brightness reversals/sec (WCAG measure).
    private function groupBySecond(array $flashFrames, int $samplingRate, int $totalFrames): array
    {
        $durationSeconds = (int) ceil($totalFrames / $samplingRate);
        $perSecond = [];

        for ($sec = 0; $sec < $durationSeconds; $sec++) {
            $startFrame = $sec * $samplingRate + 1;
            $endFrame = min(($sec + 1) * $samplingRate, $totalFrames);
            $count = 0;

            for ($f = $startFrame; $f < $endFrame; $f++) {
                $count += $flashFrames[$f] ?? 0;
            }

            $perSecond[] = [
                'second' => $sec,
                'frequency' => (float) $count,
            ];
        }

        return $perSecond;
    }

    private function buildFlaggedSegments(array $perSecondFrequencies): array
    {
        $segments = [];
        $inSegment = false;
        $segStart = 0;
        $maxFreq = 0.0;

        foreach ($perSecondFrequencies as $entry) {
            $isDangerous = $entry['frequency'] >= AnalysisConfig::FLASH_FREQUENCY_DANGER;

            if ($isDangerous && !$inSegment) {
                $inSegment = true;
                $segStart = $entry['second'];
                $maxFreq = $entry['frequency'];
            } elseif ($isDangerous && $inSegment) {
                $maxFreq = max($maxFreq, $entry['frequency']);
            } elseif (!$isDangerous && $inSegment) {
                $segments[] = $this->createSegment($segStart, $entry['second'], $maxFreq);
                $inSegment = false;
                $maxFreq = 0.0;
            }
        }

        if ($inSegment) {
            $lastSecond = end($perSecondFrequencies)['second'] + 1;
            $segments[] = $this->createSegment($segStart, $lastSecond, $maxFreq);
        }

        return $segments;
    }

    private function createSegment(int $startSecond, int $endSecond, float $maxFrequency): array
    {
        return [
            'startTime' => (float) $startSecond,
            'endTime' => (float) $endSecond,
            'type' => 'flash',
            'severity' => $this->classifySeverity($maxFrequency),
            'metricValue' => round($maxFrequency, 2),
        ];
    }

    private function classifySeverity(float $frequency): string
    {
        if ($frequency > 10) {
            return 'high';
        }
        if ($frequency > 5) {
            return 'medium';
        }

        return 'low';
    }
}
