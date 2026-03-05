<?php

namespace App\Services;

use App\Config\AnalysisConfig;
use App\Models\MotionAnalysisResult;
use App\Utils\ImageAnalyzer;

class MotionDetector
{
    private const SUSTAINED_THRESHOLD_SECONDS = 0.5;

    public function detect(array $framePaths, int $samplingRate): MotionAnalysisResult
    {
        $frameDiffs = $this->computeFrameDiffs($framePaths);
        $perSecondIntensities = $this->groupBySecond($frameDiffs, $samplingRate, count($framePaths));
        $segments = $this->buildFlaggedSegments($perSecondIntensities, $samplingRate);
        $averageIntensity = $this->computeAverage($perSecondIntensities);

        return new MotionAnalysisResult($averageIntensity, $segments, $perSecondIntensities);
    }

    private function computeFrameDiffs(array $framePaths): array
    {
        $diffs = [];

        for ($i = 1; $i < count($framePaths); $i++) {
            $diffs[] = [
                'index' => $i,
                'intensity' => ImageAnalyzer::calculateFrameDifference($framePaths[$i - 1], $framePaths[$i]),
            ];
        }

        return $diffs;
    }

    private function groupBySecond(array $frameDiffs, int $samplingRate, int $totalFrames): array
    {
        $durationSeconds = (int) ceil($totalFrames / $samplingRate);
        $perSecond = [];

        for ($sec = 0; $sec < $durationSeconds; $sec++) {
            $startFrame = $sec * $samplingRate;
            $endFrame = min(($sec + 1) * $samplingRate, $totalFrames);
            $intensities = [];

            for ($f = $startFrame + 1; $f < $endFrame; $f++) {
                $intensities[] = $this->findIntensityForFrame($frameDiffs, $f);
            }

            $avg = empty($intensities) ? 0.0 : array_sum($intensities) / count($intensities);
            $perSecond[] = [
                'second' => $sec,
                'intensity' => round($avg, 2),
            ];
        }

        return $perSecond;
    }

    private function findIntensityForFrame(array $frameDiffs, int $frameIndex): float
    {
        foreach ($frameDiffs as $diff) {
            if ($diff['index'] === $frameIndex) {
                return $diff['intensity'];
            }
        }

        return 0.0;
    }

    private function buildFlaggedSegments(array $perSecondIntensities, int $samplingRate): array
    {
        $segments = [];
        $inSegment = false;
        $segStart = 0;
        $maxIntensity = 0.0;
        $consecutiveCount = 0;
        $minConsecutive = max(1, (int) ceil(self::SUSTAINED_THRESHOLD_SECONDS));

        foreach ($perSecondIntensities as $entry) {
            $isHigh = $entry['intensity'] >= AnalysisConfig::MOTION_THRESHOLD;

            if ($isHigh) {
                if (!$inSegment) {
                    $segStart = $entry['second'];
                    $maxIntensity = $entry['intensity'];
                    $consecutiveCount = 1;
                    $inSegment = true;
                } else {
                    $maxIntensity = max($maxIntensity, $entry['intensity']);
                    $consecutiveCount++;
                }
            } else {
                if ($inSegment && $consecutiveCount >= $minConsecutive) {
                    $segments[] = $this->createSegment($segStart, $entry['second'], $maxIntensity);
                }
                $inSegment = false;
                $consecutiveCount = 0;
            }
        }

        if ($inSegment && $consecutiveCount >= $minConsecutive) {
            $lastSecond = end($perSecondIntensities)['second'] + 1;
            $segments[] = $this->createSegment($segStart, $lastSecond, $maxIntensity);
        }

        return $segments;
    }

    private function createSegment(int $startSecond, int $endSecond, float $maxIntensity): array
    {
        return [
            'startTime' => (float) $startSecond,
            'endTime' => (float) $endSecond,
            'type' => 'motion',
            'severity' => $this->classifySeverity($maxIntensity),
            'metricValue' => round($maxIntensity, 2),
        ];
    }

    private function classifySeverity(float $intensity): string
    {
        if ($intensity > 120) {
            return 'high';
        }
        if ($intensity > 60) {
            return 'medium';
        }

        return 'low';
    }

    private function computeAverage(array $perSecondIntensities): float
    {
        if (empty($perSecondIntensities)) {
            return 0.0;
        }

        $sum = array_sum(array_column($perSecondIntensities, 'intensity'));

        return round($sum / count($perSecondIntensities), 2);
    }
}
