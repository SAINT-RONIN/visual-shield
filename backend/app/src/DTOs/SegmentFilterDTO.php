<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Captures and validates query parameters for filtering and sorting
 * flagged segments on the report page.
 *
 * All values are validated against whitelists so the resulting DTO
 * is safe to use directly in repository queries.
 */
final class SegmentFilterDTO
{
    public function __construct(
        public readonly ?string $type = null,
        public readonly ?string $severity = null,
        public readonly string $sort = 'start_time',
        public readonly string $order = 'asc',
    ) {}

    /**
     * Build from raw query parameters ($_GET).
     *
     * Note: the query param names use "segment_sort" and "segment_order"
     * to avoid conflicts with any future top-level sort parameters.
     */
    public static function fromQuery(array $query): self
    {
        $validTypes = ['flash', 'motion'];
        $validSeverities = ['high', 'medium', 'low'];
        $validSorts = ['start_time', 'end_time', 'segment_type', 'severity', 'metric_value'];
        $validOrders = ['asc', 'desc'];

        return new self(
            type: isset($query['type']) && in_array($query['type'], $validTypes, true)
                ? $query['type'] : null,
            severity: isset($query['severity']) && in_array($query['severity'], $validSeverities, true)
                ? $query['severity'] : null,
            sort: isset($query['segment_sort']) && in_array($query['segment_sort'], $validSorts, true)
                ? $query['segment_sort'] : 'start_time',
            order: isset($query['segment_order']) && in_array($query['segment_order'], $validOrders, true)
                ? $query['segment_order'] : 'asc',
        );
    }
}
