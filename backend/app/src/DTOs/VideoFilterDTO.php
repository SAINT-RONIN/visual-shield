<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Config\AnalysisConfig;

/**
 * Captures and validates query parameters for filtering and sorting
 * the video list on the dashboard.
 *
 * All values are validated against whitelists so the resulting DTO
 * is safe to use directly in repository queries.
 */
final class VideoFilterDTO
{
    public function __construct(
        public readonly ?string $status = null,
        public readonly ?string $search = null,
        public readonly string $sort = 'created_at',
        public readonly string $order = 'desc',
        public readonly int $limit = 20,
        public readonly int $offset = 0,
    ) {}

    // Invalid or missing values fall back to safe defaults — no injection risk.
    public static function fromQuery(array $query): self
    {
        $validSorts = ['created_at', 'original_name', 'status'];
        $validOrders = ['asc', 'desc'];
        $validStatuses = ['queued', 'processing', 'completed', 'failed'];

        $status = isset($query['status']) && in_array($query['status'], $validStatuses, true)
            ? $query['status']
            : null;

        $searchRaw = isset($query['search']) ? trim($query['search']) : '';
        $search = $searchRaw !== '' ? $searchRaw : null;

        $sort = isset($query['sort']) && in_array($query['sort'], $validSorts, true)
            ? $query['sort']
            : 'created_at';

        $order = isset($query['order']) && in_array($query['order'], $validOrders, true)
            ? $query['order']
            : 'desc';

        $rawLimit = isset($query['limit']) ? (int) $query['limit'] : 20;
        $limit = max(AnalysisConfig::PAGINATION_MIN_LIMIT, min(AnalysisConfig::PAGINATION_MAX_LIMIT, $rawLimit));

        $rawOffset = isset($query['offset']) ? (int) $query['offset'] : 0;
        $offset = max(0, $rawOffset);

        return new self(
            status: $status,
            search: $search,
            sort: $sort,
            order: $order,
            limit: $limit,
            offset: $offset,
        );
    }
}
