<?php

namespace App\Services;

use App\Repositories\VideoRepository;
use App\Repositories\AnalysisResultRepository;
use App\Repositories\FlaggedSegmentRepository;
use App\Repositories\AnalysisDatapointRepository;

class AnalysisService
{
    public function __construct(
        private VideoRepository $videoRepo,
        private AnalysisResultRepository $analysisResultRepo,
        private FlaggedSegmentRepository $segmentRepo,
        private AnalysisDatapointRepository $datapointRepo,
        private FrameExtractor $frameExtractor,
        private FFprobeService $ffprobe,
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
            $this->storePlaceholderResults($videoId, count($framePaths), $samplingRate);
        } finally {
            $this->frameExtractor->cleanup($outputDir);
        }
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

    private function storePlaceholderResults(int $videoId, int $totalFrames, int $effectiveRate): void
    {
        $this->analysisResultRepo->create($videoId, $totalFrames, 0, 0.0, 0.0, $effectiveRate);
        $this->videoRepo->updateEffectiveRate($videoId, $effectiveRate);
    }
}
