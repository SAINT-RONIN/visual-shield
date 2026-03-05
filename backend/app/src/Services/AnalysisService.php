<?php

namespace App\Services;

use App\Config\AnalysisConfig;
use App\Models\FlashAnalysisResult;
use App\Models\MotionAnalysisResult;
use App\Repositories\VideoRepository;
use App\Repositories\AnalysisResultRepository;
use App\Repositories\FlaggedSegmentRepository;
use App\Repositories\AnalysisDatapointRepository;
use App\Utils\ImageAnalyzer;

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

    public function analyze(int $videoId): void
    {
        $video = $this->videoRepo->findById($videoId);

        if (!$video) {
            throw new \RuntimeException('Video not found');
        }

        $videoPath = $this->resolveVideoPath($video['stored_path']);
        $outputDir = $this->buildOutputDir($videoId);
        $samplingRate = (int) ($video['effective_rate'] ?? $video['sampling_rate']);

        try {
            $framePaths = $this->frameExtractor->extract($videoPath, $samplingRate, $outputDir);
            $this->runAnalysis($videoId, $framePaths, $samplingRate);
        } finally {
            $this->frameExtractor->cleanup($outputDir);
        }
    }

    private function runAnalysis(int $videoId, array $framePaths, int $samplingRate): void
    {
        $flashResult = $this->flashDetector->detect($framePaths, $samplingRate);
        $motionResult = $this->motionDetector->detect($framePaths, $samplingRate);
        $luminanceValues = $this->computeLuminanceTimeSeries($framePaths, $samplingRate);

        $this->storeDatapoints($videoId, $flashResult, $motionResult, $luminanceValues);
        $this->storeSegments($videoId, $flashResult, $motionResult);
        $this->storeSummary($videoId, count($framePaths), $flashResult, $motionResult, $samplingRate);
    }

    private function computeLuminanceTimeSeries(array $framePaths, int $samplingRate): array
    {
        $totalFrames = count($framePaths);
        $durationSeconds = (int) ceil($totalFrames / $samplingRate);
        $luminancePerSecond = [];

        for ($sec = 0; $sec < $durationSeconds; $sec++) {
            $frameIndex = min($sec * $samplingRate, $totalFrames - 1);
            $luminance = ImageAnalyzer::calculateAverageLuminance($framePaths[$frameIndex]);
            $luminancePerSecond[] = [
                'second' => $sec,
                'luminance' => round($luminance, 2),
            ];
        }

        return $luminancePerSecond;
    }

    private function storeDatapoints(
        int $videoId,
        FlashAnalysisResult $flashResult,
        MotionAnalysisResult $motionResult,
        array $luminanceValues,
    ): void {
        $datapoints = [];
        $flashBySecond = $this->indexBySecond($flashResult->perSecondFrequencies, 'frequency');
        $motionBySecond = $this->indexBySecond($motionResult->perSecondIntensities, 'intensity');
        $lumBySecond = $this->indexBySecond($luminanceValues, 'luminance');

        $maxSecond = max(
            count($flashBySecond),
            count($motionBySecond),
            count($lumBySecond)
        );

        for ($sec = 0; $sec < $maxSecond; $sec++) {
            $freq = $flashBySecond[$sec] ?? 0.0;
            $datapoints[] = [
                'timePoint' => (float) $sec,
                'flashFrequency' => $freq,
                'motionIntensity' => $motionBySecond[$sec] ?? 0.0,
                'luminance' => $lumBySecond[$sec] ?? 0.0,
                'flashDetected' => $freq >= AnalysisConfig::FLASH_FREQUENCY_DANGER,
            ];
        }

        $this->datapointRepo->createBatch($videoId, $datapoints);
    }

    private function indexBySecond(array $entries, string $valueKey): array
    {
        $indexed = [];

        foreach ($entries as $entry) {
            $indexed[$entry['second']] = $entry[$valueKey];
        }

        return $indexed;
    }

    private function storeSegments(
        int $videoId,
        FlashAnalysisResult $flashResult,
        MotionAnalysisResult $motionResult,
    ): void {
        $allSegments = array_merge($flashResult->segments, $motionResult->segments);

        if (!empty($allSegments)) {
            $this->segmentRepo->createBatch($videoId, $allSegments);
        }
    }

    private function storeSummary(
        int $videoId,
        int $totalFrames,
        FlashAnalysisResult $flashResult,
        MotionAnalysisResult $motionResult,
        int $effectiveRate,
    ): void {
        $this->analysisResultRepo->create(
            $videoId,
            $totalFrames,
            $flashResult->totalEvents,
            $flashResult->highestFrequency,
            $motionResult->averageIntensity,
            $effectiveRate
        );
        $this->videoRepo->updateEffectiveRate($videoId, $effectiveRate);
    }

    private function resolveVideoPath(string $storedPath): string
    {
        $fullPath = __DIR__ . '/../../' . $storedPath;

        if (!file_exists($fullPath)) {
            throw new \RuntimeException('Video file not found on disk');
        }

        return $fullPath;
    }

    private function buildOutputDir(int $videoId): string
    {
        return __DIR__ . '/../../storage/frames/video_' . $videoId;
    }
}
