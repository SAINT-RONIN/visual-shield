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
use App\Models\Video;
use App\Repositories\VideoRepository;
use App\Repositories\AnalysisResultRepository;
use App\Repositories\FlaggedSegmentRepository;
use App\Repositories\AnalysisDatapointRepository;
use App\Utils\FFprobe;
use App\Utils\FlashDetector;
use App\Utils\FrameExtractor;
use App\Utils\ImageAnalyzer;
use App\Utils\MotionDetector;
use App\Utils\PathResolver;

/**
 * Runs the end-to-end analysis pipeline for queued videos.
 *
 * This service is where the project stops being "just uploads and database
 * rows" and actually becomes a video analysis tool, because this is the part
 * that turns one stored file into frames, measurements, warnings, and charts.
 */
class AnalysisService extends BaseService implements AnalysisServiceInterface
{
    public function __construct(
        private VideoRepository $videoRepo,
        private AnalysisResultRepository $analysisResultRepo,
        private FlaggedSegmentRepository $segmentRepo,
        private AnalysisDatapointRepository $datapointRepo,
        private FrameExtractor $frameExtractor,
        private FFprobe $ffprobe,
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
     * This asks the repository for the oldest queued video so the worker can
     * process uploads in a predictable first-in, first-out order.
     * Keeping this logic here means the worker does not need to know anything
     * about repositories or queue rules.
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
        $video = $this->findOrFail($this->videoRepo->findById($videoId), 'Video not found');
        $videoPath = PathResolver::resolveOrFail($video->storedPath);
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
     * This is the middle of the pipeline where raw extracted frames become
     * useful measurements, then useful measurements become detector results,
     * and finally those results get prepared for storage.
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
     * This converts the ordered frame images into a simpler data structure the
     * detectors can work with, so they do not each have to reopen and compare
     * JPEG files on their own.
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
     * This walks forward through the frame list and records how much each new
     * frame changed compared with the one just before it, which is the basic
     * input both flash detection and motion detection rely on.
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
     * This grabs just the frame data that belongs to one second of playback so
     * the averaging step can work on a small, well-defined slice at a time.
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
     * This turns one slice of frame data into a single average brightness
     * value, which is the number we later plot on the luminance chart.
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

    /**
     * This is the last step of the pipeline and writes everything useful to
     * the database so the report page does not have to recalculate anything
     * when a user opens the finished analysis later.
     */
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
     * This stores the risky time ranges the detectors found so the report can
     * highlight them in the timeline and list them in the segments table.
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

    /**
     * This saves the headline summary values the dashboard and report header
     * care about most, like total flashes, peak frequency, and average motion.
     */
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

    /**
     * This builds the temp folder path where FFmpeg should write extracted
     * frames for one video so each analysis job has its own isolated workspace.
     */
    private function buildFrameOutputDirectory(int $videoId): string
    {
        return AnalysisConfig::appRoot() . '/storage/frames/video_' . $videoId;
    }

    /**
     * This safely forwards progress updates only when the caller actually
     * asked for them, which lets the analysis stay reusable in places that do
     * and do not care about progress reporting.
     */
    private function reportProgress(?callable $onProgress, int $percent, string $message): void
    {
        if ($onProgress) {
            $onProgress($percent, $message);
        }
    }
}
