<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Video;

interface AnalysisServiceInterface
{
    /**
     * Manage the full lifecycle of video processing.
     */
    public function processVideo(int $videoId): void;

    /**
     * Dequeue the next video waiting for analysis.
     */
    public function dequeueNextVideo(): ?Video;

    /**
     * Run the complete analysis pipeline for a single video.
     */
    public function analyze(int $videoId, ?callable $onProgress = null): void;
}
