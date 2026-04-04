<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\ReportServiceInterface;
use App\DTOs\ReportDTO;
use App\DTOs\SegmentFilterDTO;
use App\Models\AnalysisDatapoint;
use App\Models\FlaggedSegment;
use App\Models\Video;
use App\Repositories\VideoRepository;
use App\Repositories\AnalysisResultRepository;
use App\Repositories\FlaggedSegmentRepository;
use App\Repositories\AnalysisDatapointRepository;

/**
 * Builds the finished report data for a video.
 *
 * Once analysis is done, this service is the place that pulls the different
 * result pieces back together so the frontend can treat them as one report
 * instead of making separate requests and merging them by itself.
 */
class ReportService extends BaseService implements ReportServiceInterface
{
    /**
     * Create the service with its report-related repositories.
     *
     * @param VideoRepository $videoRepo Repository used for ownership checks and video lookups.
     * @param AnalysisResultRepository $analysisResultRepo Repository for analysis summary rows.
     * @param FlaggedSegmentRepository $segmentRepo Repository for flagged report segments.
     * @param AnalysisDatapointRepository $datapointRepo Repository for per-second chart datapoints.
     * @return void
     */
    public function __construct(
        private VideoRepository $videoRepo,
        private AnalysisResultRepository $analysisResultRepo,
        private FlaggedSegmentRepository $segmentRepo,
        private AnalysisDatapointRepository $datapointRepo,
    ) {}

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    //  Report generation
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * This is the main report builder and it gathers the video, summary,
     * segments, and datapoints into one DTO the frontend can render directly.
     * The ownership check lives here so every report view and export follows
     * the same access rule.
     *
     * @param int $userId Authenticated user for ownership checks.
     * @param int $videoId Video to build a report for.
     * @param SegmentFilterDTO|null $segmentFilters Optional type, severity, and sort filters.
     * @return ReportDTO Fully assembled report DTO.
     * @throws \RuntimeException If the video doesn't exist or doesn't belong to the user.
     */
    public function getReport(int $userId, int $videoId, ?SegmentFilterDTO $segmentFilters = null): ReportDTO
    {
        $video = $this->findUserVideoOrFail($this->videoRepo, $userId, $videoId);

        $analysisResult = $this->analysisResultRepo->findByVideoId($videoId);
        $segments = $this->segmentRepo->findByVideoId($videoId, $segmentFilters);
        $datapoints = $this->datapointRepo->findByVideoId($videoId);

        return new ReportDTO($video, $analysisResult, $segments, $datapoints);
    }

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    //  Export formats
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * Return the flagged segments for a video, ready for CSV export.
     *
     * Ownership is verified here. Serialisation (toCsvRow, fputcsv) is the
     * controller's responsibility â€” this method returns typed objects only.
     *
     * @param int $userId Authenticated user for ownership checks.
     * @param int $videoId Video to export.
     * @return FlaggedSegment[]
     */
    public function exportAsCsv(int $userId, int $videoId): array
    {
        $this->findUserVideoOrFail($this->videoRepo, $userId, $videoId);

        return $this->segmentRepo->findByVideoId($videoId);
    }

    /**
     * This reuses the normal report-building path for JSON export so the data
     * a user downloads matches the data they see on the report page.
     *
     * @param int $userId Authenticated user for ownership checks.
     * @param int $videoId Video to export.
     * @return ReportDTO Full report payload for JSON export.
     */
    public function exportAsJson(int $userId, int $videoId): ReportDTO
    {
        return $this->getReport($userId, $videoId);
    }

    /**
     * This is the lighter-weight way to fetch only the flagged segments when a
     * caller does not need the full report wrapper.
     *
     * @param int $userId Authenticated user (ownership check).
     * @param int $videoId The video to query.
     * @param SegmentFilterDTO|null $segmentFilters Optional type, severity, and sort filters.
     * @return FlaggedSegment[] Typed segment objects.
     *
     * @throws \RuntimeException If the video doesn't exist or doesn't belong to the user.
     */
    public function getSegments(int $userId, int $videoId, ?SegmentFilterDTO $segmentFilters = null): array
    {
        $this->findUserVideoOrFail($this->videoRepo, $userId, $videoId);

        return $this->segmentRepo->findByVideoId($videoId, $segmentFilters);
    }

    /**
     * This returns the chart-ready time-series data on its own when the caller
     * only needs datapoints and not the rest of the report.
     *
     * @param int $userId Authenticated user (ownership check).
     * @param int $videoId The video to query.
     * @return AnalysisDatapoint[] Typed datapoint objects ordered by time.
     *
     * @throws \RuntimeException If the video doesn't exist or doesn't belong to the user.
     */
    public function getDatapoints(int $userId, int $videoId): array
    {
        $this->findUserVideoOrFail($this->videoRepo, $userId, $videoId);

        return $this->datapointRepo->findByVideoId($videoId);
    }

}
