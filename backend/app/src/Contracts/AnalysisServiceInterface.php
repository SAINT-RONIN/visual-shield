<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Video;

interface AnalysisServiceInterface
{
    /**
     * Manage the full lifecycle of video processing.
     *
     * @param int $videoId The queued video to process.
     * @return void
     */
    public function processVideo(int $videoId): void;

    /**
     * Dequeue the next video waiting for analysis.
     *
     * @return Video|null The next queued video, or null when the queue is empty.
     */
    public function dequeueNextVideo(): ?Video;

    /**
     * Run the complete analysis pipeline for a single video.
     *
     * @param int $videoId The video to analyse.
     * @param callable|null $onProgress Optional callback receiving progress percentage and message.
     * @return void
     */
    public function analyze(int $videoId, ?callable $onProgress = null): void;
}
