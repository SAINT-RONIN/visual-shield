<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\AnalysisConfig;
use App\Contracts\AnalysisServiceInterface;
use App\DTOs\DatapointData;
use App\DTOs\FlashAnalysisResult;
use App\DTOs\FrameData;
use App\DTOs\MotionAnalysisResult;
use App\DTOs\PerSecondLuminance;
use App\Exceptions\NotFoundException;
use App\Models\Video;
use App\Repositories\VideoRepository;
use App\Repositories\AnalysisResultRepository;
use App\Repositories\FlaggedSegmentRepository;
use App\Repositories\AnalysisDatapointRepository;
use App\Utils\ImageAnalyzer;

/**
 * Orchestrates the full video analysis pipeline.
 *
 * This is the "brain" of the analysis flow. After a video is uploaded, the
 * background worker calls analyze() which:
 *   1. Extracts frames from the video (like taking screenshots at regular intervals)
 *   2. Compares each frame to the one before it (measuring brightness changes and motion)
 *   3. Runs flash detection (are there dangerous strobe-like effects?)
 *   4. Runs motion detection (is there excessive/jarring movement?)
 *   5. Saves all results to the database
 */
class AnalysisService implements AnalysisServiceInterface
{
    public function __construct(
        private VideoRepository $videoRepo,
        private AnalysisResultRepository $analysisResultRepo,
        private FlaggedSegmentRepository $segmentRepo,
        private AnalysisDatapointRepository $datapointRepo,
        private FrameExtractor $frameExtractor,
        private FFprobeService $ffprobe,
        private FlashDetector $flashDetector,
        private MotionDetector $motionDetector,
    ) {}

    /**
     * Manage the full lifecycle of video processing.
     *
     * Sets status/progress bookends, delegates to analyze(), and handles
     * success and failure transitions. This is the entry point the worker
     * calls — it owns all status transitions so worker.php stays thin.
     */
    public function processVideo(int $videoId): void
    {
        $this->videoRepo->updateStatus($videoId, 'processing');
        $this->videoRepo->updateProgress($videoId, 5, 'Starting analysis...');

        try {
            $onProgress = fn(int $percent, string $message) => $this->videoRepo->updateProgress($videoId, $percent, $message);
            $this->analyze($videoId, $onProgress);
            $this->videoRepo->updateProgress($videoId, 100, 'Completed');
            $this->videoRepo->updateStatus($videoId, 'completed');
        } catch (\Throwable $e) {
            $this->videoRepo->updateError($videoId, $e->getMessage());
            $this->videoRepo->updateStatus($videoId, 'failed');
            throw $e;
        }
    }

    /**
     * Dequeue the next video waiting for analysis.
     *
     * Returns the oldest queued video, or null if the queue is empty.
     * Encapsulates the repository call so worker.php has zero repository
     * references.
     */
    public function dequeueNextVideo(): ?Video
    {
        return $this->videoRepo->findNextQueued();
    }

    /**
     * Run the complete analysis pipeline for a single video.
     *
     * The optional $onProgress callback receives updates like:
     *   $onProgress(40, 'Analyzing flash events...')
     * so the frontend can show a progress bar.
     */
    public function analyze(int $videoId, ?callable $onProgress = null): void
    {
        $video = $this->findVideoOrFail($videoId);
        $videoPath = $this->resolveVideoFilePath($video->storedPath);
        $frameOutputDirectory = $this->buildFrameOutputDirectory($videoId);
        $samplingRate = $video->getEffectiveSamplingRate();

        $this->reportProgress($onProgress, 10, 'Extracting frames...');

        try {
            $framePaths = $this->frameExtractor->extract($videoPath, $samplingRate, $frameOutputDirectory);
            $this->reportProgress($onProgress, 40, 'Analyzing flash events...');
            $this->runFullAnalysis($videoId, $framePaths, $samplingRate, $onProgress);
        } finally {
            // Always clean up temporary frame files, even if analysis fails
            $this->frameExtractor->cleanup($frameOutputDirectory);
        }
    }

    // ──────────────────────────────────────────────
    //  Pipeline steps (called in order by analyze)
    // ──────────────────────────────────────────────

    /**
     * Run all three analysis passes and save the results.
     *
     * First we compute per-frame data (brightness + motion for each frame),
     * then feed that data into the flash and motion detectors, and finally
     * calculate average luminance per second for the timeline chart.
     */
    private function runFullAnalysis(
        int $videoId,
        array $framePaths,
        int $samplingRate,
        ?callable $onProgress = null,
    ): void {
        $perFrameData = $this->computePerFrameData($framePaths);

        $flashResult = $this->flashDetector->detectFromData($perFrameData, $samplingRate);
        $this->reportProgress($onProgress, 65, 'Analyzing motion...');

        $motionResult = $this->motionDetector->detectFromData($perFrameData, $samplingRate);
        $luminancePerSecond = $this->calculateAverageLuminancePerSecond($perFrameData, $samplingRate);

        $this->reportProgress($onProgress, 85, 'Saving results...');

        $this->saveAllResults($videoId, $framePaths, $flashResult, $motionResult, $luminancePerSecond, $samplingRate);
    }

    // ──────────────────────────────────────────────
    //  Frame-level data computation
    // ──────────────────────────────────────────────

    /**
     * Compute brightness and motion data for every extracted frame.
     *
     * The first frame has no predecessor to compare against, so its
     * diff and motion values are zero. Every subsequent frame is compared
     * to the one before it to measure how much changed.
     *
     * @return FrameData[]
     */
    private function computePerFrameData(array $framePaths): array
    {
        if (empty($framePaths)) {
            return [];
        }

        $firstFrameData = $this->buildFirstFrameData($framePaths[0]);
        $remainingFramesData = $this->buildRemainingFramesData($framePaths);

        return [$firstFrameData, ...$remainingFramesData];
    }

    /** The first frame only has luminance — no diff or motion since there's nothing before it. */
    private function buildFirstFrameData(string $framePath): FrameData
    {
        $luminance = ImageAnalyzer::calculateAverageLuminance($framePath);

        return new FrameData(
            luminance: $luminance,
            luminanceDiff: 0.0,
            motionIntensity: 0.0,
        );
    }

    /**
     * Compare each frame to its predecessor and record the differences.
     *
     * @return FrameData[]
     */
    private function buildRemainingFramesData(array $framePaths): array
    {
        $frameCount = count($framePaths);
        $results = [];

        for ($i = 1; $i < $frameCount; $i++) {
            $previousFramePath = $framePaths[$i - 1];
            $currentFramePath = $framePaths[$i];
            $comparison = ImageAnalyzer::analyzeFramePair($previousFramePath, $currentFramePath);

            $results[] = new FrameData(
                luminance: $comparison->luminance2,
                luminanceDiff: abs($comparison->luminance2 - $comparison->luminance1),
                motionIntensity: $comparison->motionIntensity,
            );
        }

        return $results;
    }

    // ──────────────────────────────────────────────
    //  Luminance averaging
    // ──────────────────────────────────────────────

    /**
     * Group frame luminance values into 1-second windows and average each window.
     *
     * For example, at 10fps: frames 0–9 become second 0, frames 10–19 become second 1, etc.
     *
     * @param  FrameData[] $perFrameData
     * @return PerSecondLuminance[]
     */
    private function calculateAverageLuminancePerSecond(array $perFrameData, int $samplingRate): array
    {
        $totalFrames = count($perFrameData);
        $totalSeconds = (int) ceil($totalFrames / $samplingRate);
        $luminancePerSecond = [];

        for ($second = 0; $second < $totalSeconds; $second++) {
            $framesInThisSecond = $this->getFramesInSecond($perFrameData, $second, $samplingRate, $totalFrames);
            $averageLuminance = $this->averageFrameLuminance($framesInThisSecond);

            $luminancePerSecond[] = new PerSecondLuminance(
                second: $second,
                luminance: round($averageLuminance, 2),
            );
        }

        return $luminancePerSecond;
    }

    /**
     * Extract the slice of per-frame data that falls within a given second.
     *
     * @param  FrameData[] $perFrameData
     * @return FrameData[]
     */
    private function getFramesInSecond(array $perFrameData, int $second, int $samplingRate, int $totalFrames): array
    {
        $startFrame = $second * $samplingRate;
        $endFrame = min(($second + 1) * $samplingRate, $totalFrames);

        return array_slice($perFrameData, $startFrame, $endFrame - $startFrame);
    }

    /**
     * Calculate the average luminance across a slice of FrameData objects.
     *
     * @param FrameData[] $frames
     */
    private function averageFrameLuminance(array $frames): float
    {
        if (empty($frames)) {
            return 0.0;
        }

        $sum = 0.0;
        foreach ($frames as $frame) {
            $sum += $frame->luminance;
        }

        return $sum / count($frames);
    }

    // ──────────────────────────────────────────────
    //  Saving results to the database
    // ──────────────────────────────────────────────

    /** Persist all analysis outputs: datapoints, segments, and the summary row. */
    private function saveAllResults(
        int $videoId,
        array $framePaths,
        FlashAnalysisResult $flashResult,
        MotionAnalysisResult $motionResult,
        array $luminancePerSecond,
        int $samplingRate,
    ): void {
        $this->saveDatapoints($videoId, $flashResult, $motionResult, $luminancePerSecond);
        $this->saveSegments($videoId, $flashResult, $motionResult);
        $this->saveSummary($videoId, count($framePaths), $flashResult, $motionResult, $samplingRate);
    }

    /**
     * Save the per-second timeline data used by the frontend charts.
     *
     * Each second gets one row with flash frequency, motion intensity, and luminance.
     * We clear old data first so re-analysis replaces previous results.
     *
     * Lookup tables indexed by second number are built inline here for O(1) access
     * and kept as local variables so no raw arrays cross method boundaries.
     *
     * @param PerSecondLuminance[] $luminancePerSecond
     */
    private function saveDatapoints(
        int $videoId,
        FlashAnalysisResult $flashResult,
        MotionAnalysisResult $motionResult,
        array $luminancePerSecond,
    ): void {
        $this->datapointRepo->deleteByVideoId($videoId);

        // Build lookup tables indexed by second number for O(1) access
        $flashBySecond = [];
        foreach ($flashResult->perSecondFrequencies as $entry) {
            $flashBySecond[$entry->second] = $entry->frequency;
        }

        $motionBySecond = [];
        foreach ($motionResult->perSecondIntensities as $entry) {
            $motionBySecond[$entry->second] = $entry->intensity;
        }

        $luminanceBySecond = [];
        foreach ($luminancePerSecond as $entry) {
            $luminanceBySecond[$entry->second] = $entry->luminance;
        }

        $totalSeconds = max(count($flashBySecond), count($motionBySecond), count($luminanceBySecond), 1);
        $datapoints = [];

        for ($second = 0; $second < $totalSeconds; $second++) {
            $flashFrequency = $flashBySecond[$second] ?? 0.0;
            $motionIntensity = $motionBySecond[$second] ?? 0.0;
            $luminance = $luminanceBySecond[$second] ?? 0.0;
            $flashDetected = $flashFrequency >= AnalysisConfig::FLASH_FREQUENCY_DANGER;

            $datapoints[] = new DatapointData(
                timePoint: (float) $second,
                flashFrequency: $flashFrequency,
                motionIntensity: $motionIntensity,
                luminance: $luminance,
                flashDetected: $flashDetected,
            );
        }

        $this->datapointRepo->createBatch($videoId, $datapoints);
    }

    /**
     * Save flagged time segments (dangerous flash or motion regions).
     * These appear as highlighted zones on the video timeline in the UI.
     */
    private function saveSegments(
        int $videoId,
        FlashAnalysisResult $flashResult,
        MotionAnalysisResult $motionResult,
    ): void {
        $this->segmentRepo->deleteByVideoId($videoId);

        $allSegments = array_merge($flashResult->segments, $motionResult->segments);

        if (!empty($allSegments)) {
            $this->segmentRepo->createBatch($videoId, $allSegments);
        }
    }

    /** Save the high-level summary row (total frames, peak flash frequency, average motion). */
    private function saveSummary(
        int $videoId,
        int $totalFrames,
        FlashAnalysisResult $flashResult,
        MotionAnalysisResult $motionResult,
        int $effectiveRate,
    ): void {
        $this->analysisResultRepo->deleteByVideoId($videoId);

        $this->analysisResultRepo->create(
            $videoId,
            $totalFrames,
            $flashResult->totalEvents,
            $flashResult->highestFrequency,
            $motionResult->averageIntensity,
            $effectiveRate,
        );

        $this->videoRepo->updateEffectiveRate($videoId, $effectiveRate);
    }

    // ──────────────────────────────────────────────
    //  Utility helpers
    // ──────────────────────────────────────────────

    private function findVideoOrFail(int $videoId): Video
    {
        $video = $this->videoRepo->findById($videoId);

        if (!$video) {
            throw new NotFoundException('Video not found');
        }

        return $video;
    }

    /** Convert the database's relative stored_path into an absolute filesystem path. */
    private function resolveVideoFilePath(string $storedPath): string
    {
        $fullPath = AnalysisConfig::appRoot() . '/' . $storedPath;

        if (!file_exists($fullPath)) {
            throw new NotFoundException('Video file not found on disk');
        }

        return $fullPath;
    }

    /** Build the temporary directory path where extracted frames will be stored. */
    private function buildFrameOutputDirectory(int $videoId): string
    {
        return AnalysisConfig::appRoot() . '/storage/frames/video_' . $videoId;
    }

    /** Send a progress update to the callback, if one was provided. */
    private function reportProgress(?callable $onProgress, int $percent, string $message): void
    {
        if ($onProgress) {
            $onProgress($percent, $message);
        }
    }
}
