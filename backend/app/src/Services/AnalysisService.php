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

    // Runs flash + motion + luminance analysis in a single pass over frames.
    private function runAnalysis(int $videoId, array $framePaths, int $samplingRate): void
    {
        $perFrameData = $this->computePerFrameData($framePaths);

        $flashResult = $this->flashDetector->detectFromData($perFrameData, $samplingRate);
        $motionResult = $this->motionDetector->detectFromData($perFrameData, $samplingRate);
        $luminancePerSecond = $this->averageLuminancePerSecond($perFrameData, $samplingRate);

        $this->storeDatapoints($videoId, $flashResult, $motionResult, $luminancePerSecond);
        $this->storeSegments($videoId, $flashResult, $motionResult);
        $this->storeSummary($videoId, count($framePaths), $flashResult, $motionResult, $samplingRate);
    }

    // Computes luminance, luminance-diff, and motion for each frame pair.
    private function computePerFrameData(array $framePaths): array
    {
        $count = count($framePaths);

        if ($count === 0) {
            return [];
        }

        // First frame: compute luminance standalone
        $firstLuminance = ImageAnalyzer::calculateAverageLuminance($framePaths[0]);
        $data = [
            0 => [
                'luminance' => $firstLuminance,
                'luminanceDiff' => 0.0,
                'motionIntensity' => 0.0,
            ],
        ];

        // Subsequent frames: analyze in pairs (each image loaded once)
        for ($i = 1; $i < $count; $i++) {
            $pair = ImageAnalyzer::analyzeFramePair($framePaths[$i - 1], $framePaths[$i]);

            $data[$i] = [
                'luminance' => $pair['luminance2'],
                'luminanceDiff' => abs($pair['luminance2'] - $pair['luminance1']),
                'motionIntensity' => $pair['motionIntensity'],
            ];
        }

        return $data;
    }

    private function averageLuminancePerSecond(array $perFrameData, int $samplingRate): array
    {
        $totalFrames = count($perFrameData);
        $durationSeconds = (int) ceil($totalFrames / $samplingRate);
        $result = [];

        for ($sec = 0; $sec < $durationSeconds; $sec++) {
            $startFrame = $sec * $samplingRate;
            $endFrame = min(($sec + 1) * $samplingRate, $totalFrames);
            $sum = 0.0;
            $count = 0;

            for ($f = $startFrame; $f < $endFrame; $f++) {
                $sum += $perFrameData[$f]['luminance'];
                $count++;
            }

            $result[] = [
                'second' => $sec,
                'luminance' => $count > 0 ? round($sum / $count, 2) : 0.0,
            ];
        }

        return $result;
    }

    private function storeDatapoints(
        int $videoId,
        FlashAnalysisResult $flashResult,
        MotionAnalysisResult $motionResult,
        array $luminanceValues,
    ): void {
        // Index each metric by second for easy lookup
        $flashBySecond = [];
        foreach ($flashResult->perSecondFrequencies as $entry) {
            $flashBySecond[$entry['second']] = $entry['frequency'];
        }

        $motionBySecond = [];
        foreach ($motionResult->perSecondIntensities as $entry) {
            $motionBySecond[$entry['second']] = $entry['intensity'];
        }

        $lumBySecond = [];
        foreach ($luminanceValues as $entry) {
            $lumBySecond[$entry['second']] = $entry['luminance'];
        }

        $maxSecond = max(count($flashBySecond), count($motionBySecond), count($lumBySecond), 1);

        $datapoints = [];
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
