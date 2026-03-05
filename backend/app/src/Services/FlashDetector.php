<?php

namespace App\Services;

use App\Config\AnalysisConfig;
use App\Models\FlashAnalysisResult;
use App\Utils\ImageAnalyzer;

class FlashDetector
{
    public function detect(array $framePaths, int $samplingRate): FlashAnalysisResult
    {
        $luminanceDiffs = $this->computeLuminanceDiffs($framePaths);
        $flashEvents = $this->identifyFlashEvents($luminanceDiffs);
        $perSecondFrequencies = $this->groupBySecond($flashEvents, $samplingRate, count($framePaths));
        $segments = $this->buildFlaggedSegments($perSecondFrequencies);
        $totalEvents = array_sum(array_map(fn(int $v): int => $v, $flashEvents));
        $highestFrequency = empty($perSecondFrequencies) ? 0.0 : max(array_column($perSecondFrequencies, 'frequency'));

        return new FlashAnalysisResult($totalEvents, $highestFrequency, $segments, $perSecondFrequencies);
    }

    private function computeLuminanceDiffs(array $framePaths): array
    {
        $diffs = [];

        for ($i = 1; $i < count($framePaths); $i++) {
            $lum1 = ImageAnalyzer::calculateAverageLuminance($framePaths[$i - 1]);
            $lum2 = ImageAnalyzer::calculateAverageLuminance($framePaths[$i]);
            $diffs[] = [
                'index' => $i,
                'diff' => abs($lum2 - $lum1),
                'luminance' => $lum2,
            ];
        }

        return $diffs;
    }

    private function identifyFlashEvents(array $diffs): array
    {
        $events = [];

        foreach ($diffs as $d) {
            $events[$d['index']] = $d['diff'] >= AnalysisConfig::FLASH_THRESHOLD ? 1 : 0;
        }

        return $events;
    }

    private function groupBySecond(array $flashEvents, int $samplingRate, int $totalFrames): array
    {
        $durationSeconds = (int) ceil($totalFrames / $samplingRate);
        $perSecond = [];

        for ($sec = 0; $sec < $durationSeconds; $sec++) {
            $startFrame = $sec * $samplingRate;
            $endFrame = min(($sec + 1) * $samplingRate, $totalFrames);
            $count = 0;

            for ($f = $startFrame + 1; $f < $endFrame; $f++) {
                $count += $flashEvents[$f] ?? 0;
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
