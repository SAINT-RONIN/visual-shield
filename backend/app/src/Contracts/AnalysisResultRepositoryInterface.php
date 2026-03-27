<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\AnalysisResult;

interface AnalysisResultRepositoryInterface
{
    public function create(
        int $videoId,
        int $totalFrames,
        int $totalFlashEvents,
        float $highestFlashFreq,
        float $avgMotionIntensity,
        int $effectiveRate,
    ): int;

    public function findByVideoId(int $videoId): ?AnalysisResult;

    public function deleteByVideoId(int $videoId): void;
}
