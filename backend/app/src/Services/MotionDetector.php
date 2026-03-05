<?php

namespace App\Services;

use App\Config\AnalysisConfig;
use App\Models\MotionAnalysisResult;

class MotionDetector
{
    private const MIN_SUSTAINED_SECONDS = 1;

    // Detects motion segments from pre-computed per-frame data.
    public function detectFromData(array $perFrameData, int $samplingRate): MotionAnalysisResult
    {
        $perSecondIntensities = $this->groupBySecond($perFrameData, $samplingRate);
        $segments = $this->buildFlaggedSegments($perSecondIntensities);
        $averageIntensity = $this->computeAverage($perSecondIntensities);

        return new MotionAnalysisResult($averageIntensity, $segments, $perSecondIntensities);
    }

    // Averages motion intensity per second. Skips frame 0 (no prior frame).
    private function groupBySecond(array $perFrameData, int $samplingRate): array
    {
        $totalFrames = count($perFrameData);
        $durationSeconds = (int) ceil($totalFrames / $samplingRate);
        $perSecond = [];

        for ($sec = 0; $sec < $durationSeconds; $sec++) {
            $startFrame = $sec * $samplingRate;
            $endFrame = min(($sec + 1) * $samplingRate, $totalFrames);
            $sum = 0.0;
            $count = 0;

            for ($f = $startFrame; $f < $endFrame; $f++) {
                if ($f === 0) {
                    continue;
                }
                $sum += $perFrameData[$f]['motionIntensity'];
                $count++;
            }

            $perSecond[] = [
                'second' => $sec,
                'intensity' => $count > 0 ? round($sum / $count, 2) : 0.0,
            ];
        }

        return $perSecond;
    }

    private function buildFlaggedSegments(array $perSecondIntensities): array
    {
        $segments = [];
        $inSegment = false;
        $segStart = 0;
        $maxIntensity = 0.0;
        $consecutiveCount = 0;

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
                if ($inSegment && $consecutiveCount >= self::MIN_SUSTAINED_SECONDS) {
                    $segments[] = $this->createSegment($segStart, $entry['second'], $maxIntensity);
                }
                $inSegment = false;
                $consecutiveCount = 0;
            }
        }

        if ($inSegment && $consecutiveCount >= self::MIN_SUSTAINED_SECONDS) {
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

        $sum = 0.0;
        foreach ($perSecondIntensities as $entry) {
            $sum += $entry['intensity'];
        }

        return round($sum / count($perSecondIntensities), 2);
    }
}
