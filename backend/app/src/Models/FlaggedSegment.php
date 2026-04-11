<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Immutable value object representing a flagged_segments row.
 *
 * Each segment is a contiguous time range where flash or motion
 * exceeded the safety threshold. Used by the report page timeline,
 * segment table, and CSV export.
 */
class FlaggedSegment
{
    public function __construct(
        public readonly float $startTime,
        public readonly float $endTime,
        public readonly string $type,
        public readonly string $severity,
        public readonly float $metricValue,
    ) {}

    // Builds a FlaggedSegment from a raw database row.
    public static function fromRow(array $row): self
    {
        return new self(
            startTime: (float) $row['start_time'],
            endTime: (float) $row['end_time'],
            type: $row['segment_type'],
            severity: $row['severity'],
            metricValue: (float) ($row['metric_value'] ?? 0),
        );
    }

    // Converts to a camelCase array for the API response.
    public function toApiArray(): array
    {
        return [
            'startTime' => $this->startTime,
            'endTime' => $this->endTime,
            'type' => $this->type,
            'severity' => $this->severity,
            'metricValue' => $this->metricValue,
        ];
    }

    // Converts to an ordered array for CSV row output.
    public function toCsvRow(): array
    {
        return [
            $this->startTime,
            $this->endTime,
            $this->type,
            $this->severity,
            $this->metricValue,
        ];
    }
}
