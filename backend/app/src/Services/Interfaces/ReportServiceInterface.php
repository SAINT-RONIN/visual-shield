<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\DTOs\ReportDTO;
use App\DTOs\SegmentFilterDTO;
use App\Models\AnalysisDatapoint;
use App\Models\FlaggedSegment;

interface ReportServiceInterface
{
    /**
     * Build the full analysis report for a video.
     *
     * @param int $userId Authenticated user for ownership checks.
     * @param int $videoId Video to build a report for.
     * @param SegmentFilterDTO|null $segmentFilters Optional segment filters for the report payload.
     * @return ReportDTO Fully assembled report DTO.
     */
    public function getReport(int $userId, int $videoId, ?SegmentFilterDTO $segmentFilters = null): ReportDTO;

    /**
     * Return the flagged segments for a video, ready for CSV export.
     *
     * @param int $userId Authenticated user for ownership checks.
     * @param int $videoId Video to export.
     * @return FlaggedSegment[]
     */
    public function exportAsCsv(int $userId, int $videoId): array;

    /**
     * Export the full report as a ReportDTO for JSON serialization.
     *
     * @param int $userId Authenticated user for ownership checks.
     * @param int $videoId Video to export.
     * @return ReportDTO Full report payload for JSON export.
     */
    public function exportAsJson(int $userId, int $videoId): ReportDTO;

    /**
     * Return just the flagged segments for a video.
     *
     * @param int $userId Authenticated user for ownership checks.
     * @param int $videoId Video to inspect.
     * @param SegmentFilterDTO|null $segmentFilters Optional type, severity, and sort filters.
     * @return FlaggedSegment[]
     */
    public function getSegments(int $userId, int $videoId, ?SegmentFilterDTO $segmentFilters = null): array;

    /**
     * Return just the per-second analysis datapoints for a video.
     *
     * @param int $userId Authenticated user for ownership checks.
     * @param int $videoId Video to inspect.
     * @return AnalysisDatapoint[]
     */
    public function getDatapoints(int $userId, int $videoId): array;
}
