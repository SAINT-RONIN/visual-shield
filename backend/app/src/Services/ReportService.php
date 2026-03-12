<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\ReportDTO;
use App\DTOs\SegmentFilterDTO;
use App\Exceptions\NotFoundException;
use App\Models\AnalysisDatapoint;
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
    public function getReport(int $userId, int $videoId, ?SegmentFilterDTO $segmentFilters = null): ReportDTO
    {
        $video = $this->findUserVideoOrFail($userId, $videoId);

        $analysisResult = $this->analysisResultRepo->findByVideoId($videoId);
        $segments = $this->segmentRepo->findByVideoId($videoId, $segmentFilters);
        $datapoints = $this->datapointRepo->findByVideoId($videoId);

        return new ReportDTO($video, $analysisResult, $segments, $datapoints);
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

    /**
     * Return just the flagged segments for a video.
     *
     * @param  int                  $userId         Authenticated user (ownership check).
     * @param  int                  $videoId        The video to query.
     * @param  SegmentFilterDTO|null $segmentFilters Optional type/severity/sort filters.
     * @return FlaggedSegment[]     Typed segment objects.
     *
     * @throws \RuntimeException If the video doesn't exist or doesn't belong to the user.
     */
    public function getSegments(int $userId, int $videoId, ?SegmentFilterDTO $segmentFilters = null): array
    {
        $this->findUserVideoOrFail($userId, $videoId);

        return $this->segmentRepo->findByVideoId($videoId, $segmentFilters);
    }

    /**
     * Return just the per-second analysis datapoints for a video.
     *
     * @param  int                    $userId  Authenticated user (ownership check).
     * @param  int                    $videoId The video to query.
     * @return AnalysisDatapoint[]    Typed datapoint objects ordered by time.
     *
     * @throws \RuntimeException If the video doesn't exist or doesn't belong to the user.
     */
    public function getDatapoints(int $userId, int $videoId): array
    {
        $this->findUserVideoOrFail($userId, $videoId);

        return $this->datapointRepo->findByVideoId($videoId);
    }

    // ──────────────────────────────────────────────
    //  Lookup helpers
    // ──────────────────────────────────────────────

    /** Find a video that belongs to a specific user, or throw a 404 error. */
    private function findUserVideoOrFail(int $userId, int $videoId): Video
    {
        $video = $this->videoRepo->findByIdAndUserId($videoId, $userId);

        if (!$video) {
            throw new NotFoundException('Video not found');
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

    /** @param resource $stream An open file handle to write the header into. */
    private function writeCsvHeaderRow(mixed $stream): void
    {
        fputcsv($stream, ['Start Time', 'End Time', 'Type', 'Severity', 'Metric Value']);
    }

    /**
     * @param resource         $stream   An open file handle to write segment rows into.
     * @param FlaggedSegment[] $segments Typed segment objects.
     */
    private function writeCsvDataRows(mixed $stream, array $segments): void
    {
        foreach ($segments as $segment) {
            fputcsv($stream, $segment->toCsvRow());
        }
    }

    /**
     * Rewind the stream to the beginning and read all its contents.
     *
     * @param resource $stream An open file handle to read from.
     */
    private function readEntireStream(mixed $stream): string
    {
        rewind($stream);

        return stream_get_contents($stream);
    }
}
