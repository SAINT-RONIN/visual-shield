<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\ReportDTO;
use App\DTOs\SegmentFilterDTO;
use App\Models\AnalysisDatapoint;
use App\Models\FlaggedSegment;

interface ReportServiceInterface
{
    /**
     * Build the full analysis report for a video.
     */
    public function getReport(int $userId, int $videoId, ?SegmentFilterDTO $segmentFilters = null): ReportDTO;

    /**
     * Return the flagged segments for a video, ready for CSV export.
     *
     * @return FlaggedSegment[]
     */
    public function exportAsCsv(int $userId, int $videoId): array;

    /** Export the full report as a ReportDTO for JSON serialization. */
    public function exportAsJson(int $userId, int $videoId): ReportDTO;

    /**
     * Return just the flagged segments for a video.
     *
     * @return FlaggedSegment[]
     */
    public function getSegments(int $userId, int $videoId, ?SegmentFilterDTO $segmentFilters = null): array;

    /**
     * Return just the per-second analysis datapoints for a video.
     *
     * @return AnalysisDatapoint[]
     */
    public function getDatapoints(int $userId, int $videoId): array;
}
