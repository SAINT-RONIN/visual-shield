<?php

namespace App\Services;

use App\DTOs\ReportDTO;
use App\Models\FlaggedSegment;
use App\Models\Video;
use App\Repositories\VideoRepository;
use App\Repositories\AnalysisResultRepository;
use App\Repositories\FlaggedSegmentRepository;
use App\Repositories\AnalysisDatapointRepository;

/**
 * Assembles and exports analysis reports for a given video.
 *
 * After a video has been analyzed, this service gathers all the results
 * (summary metrics, flagged segments, per-second datapoints) and packages
 * them into a report the frontend can display or the user can download.
 *
 * Supports two export formats:
 *   - JSON: the full report as a structured object
 *   - CSV: just the flagged segments as a spreadsheet-friendly format
 */
class ReportService
{
    public function __construct(
        private VideoRepository $videoRepo,
        private AnalysisResultRepository $analysisResultRepo,
        private FlaggedSegmentRepository $segmentRepo,
        private AnalysisDatapointRepository $datapointRepo,
    ) {}

    // ──────────────────────────────────────────────
    //  Report generation
    // ──────────────────────────────────────────────

    /**
     * Build the full analysis report for a video.
     *
     * Gathers the video record, analysis summary, flagged segments, and
     * per-second datapoints, then assembles them into a single report.
     *
     * @throws \RuntimeException If the video doesn't exist or doesn't belong to the user.
     */
    public function getReport(int $userId, int $videoId): ReportDTO
    {
        $video = $this->findUserVideoOrFail($userId, $videoId);

        $analysisResult = $this->analysisResultRepo->findByVideoId($videoId);
        $segments = $this->segmentRepo->findByVideoId($videoId);
        $datapoints = $this->datapointRepo->findByVideoId($videoId);

        return ReportDTO::fromData($video, $analysisResult, $segments, $datapoints);
    }

    // ──────────────────────────────────────────────
    //  Export formats
    // ──────────────────────────────────────────────

    /**
     * Export flagged segments as a CSV string for spreadsheet download.
     *
     * Columns: Start Time, End Time, Type, Severity, Metric Value.
     */
    public function exportAsCsv(int $userId, int $videoId): string
    {
        $this->findUserVideoOrFail($userId, $videoId);

        $segments = $this->segmentRepo->findByVideoId($videoId);

        return $this->convertSegmentsToCsvString($segments);
    }

    /** Export the full report as a ReportDTO for JSON serialization. */
    public function exportAsJson(int $userId, int $videoId): ReportDTO
    {
        return $this->getReport($userId, $videoId);
    }

    // ──────────────────────────────────────────────
    //  Lookup helpers
    // ──────────────────────────────────────────────

    /** Find a video that belongs to a specific user, or throw a 404 error. */
    private function findUserVideoOrFail(int $userId, int $videoId): Video
    {
        $video = $this->videoRepo->findByIdAndUserId($videoId, $userId);

        if (!$video) {
            throw new \RuntimeException('Video not found', 404);
        }

        return $video;
    }

    // ──────────────────────────────────────────────
    //  CSV building
    // ──────────────────────────────────────────────

    /**
     * Convert FlaggedSegment models into a CSV-formatted string.
     *
     * Uses php://temp (an in-memory stream) so we don't need to write
     * a temporary file to disk — PHP handles it all in memory.
     *
     * @param FlaggedSegment[] $segments Typed segment objects.
     */
    private function convertSegmentsToCsvString(array $segments): string
    {
        $memoryStream = fopen('php://temp', 'r+');

        $this->writeCsvHeaderRow($memoryStream);
        $this->writeCsvDataRows($memoryStream, $segments);

        $csvString = $this->readEntireStream($memoryStream);
        fclose($memoryStream);

        return $csvString;
    }

    private function writeCsvHeaderRow($stream): void
    {
        fputcsv($stream, ['Start Time', 'End Time', 'Type', 'Severity', 'Metric Value']);
    }

    /**
     * @param FlaggedSegment[] $segments Typed segment objects.
     */
    private function writeCsvDataRows($stream, array $segments): void
    {
        foreach ($segments as $segment) {
            fputcsv($stream, $segment->toCsvRow());
        }
    }

    /** Rewind the stream to the beginning and read all its contents. */
    private function readEntireStream($stream): string
    {
        rewind($stream);

        return stream_get_contents($stream);
    }
}
