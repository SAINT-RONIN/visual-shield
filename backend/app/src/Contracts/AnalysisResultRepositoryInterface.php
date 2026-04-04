<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\AnalysisResult;

interface AnalysisResultRepositoryInterface
{
    /**
     * @param int $videoId
     * @param int $totalFrames
     * @param int $totalFlashEvents
     * @param float $highestFlashFreq
     * @param float $avgMotionIntensity
     * @param int $effectiveRate
     * @return int
     */
    public function create(
        int $videoId,
        int $totalFrames,
        int $totalFlashEvents,
        float $highestFlashFreq,
        float $avgMotionIntensity,
        int $effectiveRate,
    ): int;

    /**
     * @param int $videoId
     * @return ?AnalysisResult
     */
    public function findByVideoId(int $videoId): ?AnalysisResult;

    /**
     * @param int $videoId
     * @return void
     */
    public function deleteByVideoId(int $videoId): void;
}
