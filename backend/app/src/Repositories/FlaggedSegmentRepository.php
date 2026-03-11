<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTOs\SegmentFilterDTO;
use App\Framework\Database;
use App\Models\FlaggedSegment;
use PDO;

/**
 * Data-access layer for the `flagged_segments` table.
 *
 * Purpose: Persists and retrieves contiguous time segments that the
 * detectors flagged as potentially harmful — each record stores the
 * start/end time, type (flash or motion), severity level, and the peak
 * metric value within that segment.
 *
 * Why do I need it: The SegmentTimeline and SegmentTable components on
 * the report page render these flagged ranges so users can see exactly
 * where in the video a compliance issue occurs. VideoRepository also
 * sub-queries this table to show segment counts (high / medium / total)
 * on dashboard video cards. Keeping segments in their own table avoids
 * scanning the much larger analysis_datapoints table at display time.
 */
class FlaggedSegmentRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Insert all flagged segments for a video in a single multi-row INSERT.
     *
     * Called by AnalysisService after the detectors have identified
     * contiguous regions that exceed flash or motion thresholds. A no-op
     * when the segments array is empty (i.e. the video passed all checks).
     *
     * @param  int   $videoId  The video these segments belong to.
     * @param  array $segments Array of associative arrays, each containing
     *                         startTime, endTime, type, severity, and
     *                         optional metricValue keys.
     * @return void
     */
    public function createBatch(int $videoId, array $segments): void
    {
        if (empty($segments)) {
            return;
        }

        $placeholders = [];
        $values = [];

        foreach ($segments as $segment) {
            $placeholders[] = '(?, ?, ?, ?, ?, ?)';
            $values[] = $videoId;
            $values[] = $segment['startTime'];
            $values[] = $segment['endTime'];
            $values[] = $segment['type'];
            $values[] = $segment['severity'];
            $values[] = $segment['metricValue'] ?? null;
        }

        $sql = 'INSERT INTO flagged_segments
                (video_id, start_time, end_time, segment_type, severity, metric_value)
                VALUES ' . implode(', ', $placeholders);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
    }

    /**
     * Retrieve all flagged segments for a video, ordered by start time.
     *
     * Used by ReportService to populate the SegmentTimeline and
     * SegmentTable on the report page.
     *
     * @param  int              $videoId The video to query.
     * @return FlaggedSegment[] List of segments sorted by start time.
     */
    public function findByVideoId(int $videoId, ?SegmentFilterDTO $filters = null): array
    {
        $sql = 'SELECT start_time, end_time, segment_type, severity, metric_value
                FROM flagged_segments WHERE video_id = :videoId';

        $params = ['videoId' => $videoId];

        if ($filters !== null) {
            if ($filters->type !== null) {
                $sql .= ' AND segment_type = :type';
                $params['type'] = $filters->type;
            }

            if ($filters->severity !== null) {
                $sql .= ' AND severity = :severity';
                $params['severity'] = $filters->severity;
            }

            // sort and order are validated against a strict whitelist in SegmentFilterDTO,
            // so interpolation here is safe — PDO cannot bind identifiers (column names).
            $allowedSorts = ['start_time', 'end_time', 'segment_type', 'severity', 'metric_value'];
            $allowedOrders = ['asc', 'desc'];
            $sortCol = in_array($filters->sort, $allowedSorts, true) ? $filters->sort : 'start_time';
            $orderDir = in_array($filters->order, $allowedOrders, true) ? strtoupper($filters->order) : 'ASC';

            $sql .= " ORDER BY {$sortCol} {$orderDir}";
        } else {
            $sql .= ' ORDER BY start_time ASC';
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return array_map(
            fn(array $row) => FlaggedSegment::fromRow($row),
            $stmt->fetchAll(),
        );
    }

    /**
     * Delete all flagged segments for a video.
     *
     * Called during re-analysis reset to clear stale segment data before
     * the worker produces fresh results.
     *
     * @param  int  $videoId The video whose segments should be removed.
     * @return void
     */
    public function deleteByVideoId(int $videoId): void
    {
        $stmt = $this->db->prepare('DELETE FROM flagged_segments WHERE video_id = ?');
        $stmt->execute([$videoId]);
    }
}
