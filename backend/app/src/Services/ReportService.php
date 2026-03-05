<?php

namespace App\Services;

use App\DTOs\ReportDTO;
use App\Repositories\VideoRepository;
use App\Repositories\AnalysisResultRepository;
use App\Repositories\FlaggedSegmentRepository;
use App\Repositories\AnalysisDatapointRepository;

class ReportService
{
    public function __construct(
        private VideoRepository $videoRepo,
        private AnalysisResultRepository $analysisResultRepo,
        private FlaggedSegmentRepository $segmentRepo,
        private AnalysisDatapointRepository $datapointRepo,
    ) {}

    public function getReport(int $userId, int $videoId): array
    {
        $video = $this->getVerifiedVideo($userId, $videoId);
        $analysisResult = $this->analysisResultRepo->findByVideoId($videoId) ?? [];
        $segments = $this->segmentRepo->findByVideoId($videoId);
        $datapoints = $this->datapointRepo->findByVideoId($videoId);

        $dto = ReportDTO::fromData($video, $analysisResult, $segments, $datapoints);

        return $dto->toArray();
    }

    public function exportAsCsv(int $userId, int $videoId): string
    {
        $this->getVerifiedVideo($userId, $videoId);
        $segments = $this->segmentRepo->findByVideoId($videoId);

        return $this->buildCsvString($segments);
    }

    public function exportAsJson(int $userId, int $videoId): array
    {
        return $this->getReport($userId, $videoId);
    }

    private function getVerifiedVideo(int $userId, int $videoId): array
    {
        $video = $this->videoRepo->findByIdAndUserId($videoId, $userId);

        if (!$video) {
            throw new \RuntimeException('Video not found', 404);
        }

        return $video;
    }

    private function buildCsvString(array $segments): string
    {
        $lines = ['Start Time,End Time,Type,Severity,Metric Value'];

        foreach ($segments as $seg) {
            $lines[] = implode(',', [
                $seg['start_time'],
                $seg['end_time'],
                $seg['segment_type'],
                $seg['severity'],
                $seg['metric_value'] ?? '',
            ]);
        }

        return implode("\n", $lines);
    }
}
