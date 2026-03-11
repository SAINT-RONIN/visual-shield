<?php

namespace App\Services;

use App\Config\AnalysisConfig;
use App\DTOs\FlashAnalysisResult;
use App\DTOs\MotionAnalysisResult;
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
class AnalysisService
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
     * Returns an array like:
     *   [ 0 => ['luminance' => 128.5, 'luminanceDiff' => 0.0, 'motionIntensity' => 0.0],
     *     1 => ['luminance' => 135.2, 'luminanceDiff' => 6.7, 'motionIntensity' => 12.3],
     *     ... ]
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
    private function buildFirstFrameData(string $framePath): array
    {
        $luminance = ImageAnalyzer::calculateAverageLuminance($framePath);

        return [
            'luminance' => $luminance,
            'luminanceDiff' => 0.0,
            'motionIntensity' => 0.0,
        ];
    }

    /** Compare each frame to its predecessor and record the differences. */
    private function buildRemainingFramesData(array $framePaths): array
    {
        $frameCount = count($framePaths);
        $results = [];

        for ($i = 1; $i < $frameCount; $i++) {
            $previousFramePath = $framePaths[$i - 1];
            $currentFramePath = $framePaths[$i];
            $comparison = ImageAnalyzer::analyzeFramePair($previousFramePath, $currentFramePath);

            $results[] = [
                'luminance' => $comparison['luminance2'],
                'luminanceDiff' => abs($comparison['luminance2'] - $comparison['luminance1']),
                'motionIntensity' => $comparison['motionIntensity'],
            ];
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
     */
    private function calculateAverageLuminancePerSecond(array $perFrameData, int $samplingRate): array
    {
        $totalFrames = count($perFrameData);
        $totalSeconds = (int) ceil($totalFrames / $samplingRate);
        $luminancePerSecond = [];

        for ($second = 0; $second < $totalSeconds; $second++) {
            $framesInThisSecond = $this->getFramesInSecond($perFrameData, $second, $samplingRate, $totalFrames);
            $averageLuminance = $this->averageOfKey($framesInThisSecond, 'luminance');

            $luminancePerSecond[] = [
                'second' => $second,
                'luminance' => round($averageLuminance, 2),
            ];
        }

        return $luminancePerSecond;
    }

    /** Extract the slice of per-frame data that falls within a given second. */
    private function getFramesInSecond(array $perFrameData, int $second, int $samplingRate, int $totalFrames): array
    {
        $startFrame = $second * $samplingRate;
        $endFrame = min(($second + 1) * $samplingRate, $totalFrames);

        return array_slice($perFrameData, $startFrame, $endFrame - $startFrame);
    }

    /** Calculate the average of a specific key across an array of associative arrays. */
    private function averageOfKey(array $items, string $key): float
    {
        if (empty($items)) {
            return 0.0;
        }

        $sum = 0.0;
        foreach ($items as $item) {
            $sum += $item[$key];
        }

        return $sum / count($items);
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
     */
    private function saveDatapoints(
        int $videoId,
        FlashAnalysisResult $flashResult,
        MotionAnalysisResult $motionResult,
        array $luminancePerSecond,
    ): void {
        $this->datapointRepo->deleteByVideoId($videoId);

        // Build lookup tables so we can quickly find each metric by second number
        $flashBySecond = $this->indexArrayByKey($flashResult->perSecondFrequencies, 'second', 'frequency');
        $motionBySecond = $this->indexArrayByKey($motionResult->perSecondIntensities, 'second', 'intensity');
        $luminanceBySecond = $this->indexArrayByKey($luminancePerSecond, 'second', 'luminance');

        $totalSeconds = max(count($flashBySecond), count($motionBySecond), count($luminanceBySecond), 1);
        $mergedDatapoints = $this->buildMergedDatapoints($totalSeconds, $flashBySecond, $motionBySecond, $luminanceBySecond);

        $this->datapointRepo->createBatch($videoId, $mergedDatapoints);
    }

    /**
     * Convert an array of ['second' => 3, 'frequency' => 5.0] entries
     * into a lookup like [3 => 5.0] for quick access by second number.
     */
    private function indexArrayByKey(array $entries, string $indexKey, string $valueKey): array
    {
        $indexed = [];

        foreach ($entries as $entry) {
            $indexed[$entry[$indexKey]] = $entry[$valueKey];
        }

        return $indexed;
    }

    /** Combine flash, motion, and luminance data into one row per second. */
    private function buildMergedDatapoints(
        int $totalSeconds,
        array $flashBySecond,
        array $motionBySecond,
        array $luminanceBySecond,
    ): array {
        $datapoints = [];

        for ($second = 0; $second < $totalSeconds; $second++) {
            $flashFrequency = $flashBySecond[$second] ?? 0.0;

            $datapoints[] = [
                'timePoint' => (float) $second,
                'flashFrequency' => $flashFrequency,
                'motionIntensity' => $motionBySecond[$second] ?? 0.0,
                'luminance' => $luminanceBySecond[$second] ?? 0.0,
                'flashDetected' => $flashFrequency >= AnalysisConfig::FLASH_FREQUENCY_DANGER,
            ];
        }

        return $datapoints;
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
            throw new \RuntimeException('Video not found');
        }

        return $video;
    }

    /** Convert the database's relative stored_path into an absolute filesystem path. */
    private function resolveVideoFilePath(string $storedPath): string
    {
        $fullPath = AnalysisConfig::appRoot() . '/' . $storedPath;

        if (!file_exists($fullPath)) {
            throw new \RuntimeException('Video file not found on disk');
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
