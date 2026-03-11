<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Generic paginated result wrapper.
 *
 * Carries the items for the current page along with pagination
 * metadata (total count, limit, offset) so controllers can build
 * standard paginated JSON responses.
 */
final class PaginatedResultDTO
{
    /**
     * @param array $items  The items for the current page.
     * @param int   $total  Total number of matching items across all pages.
     * @param int   $limit  Maximum items per page.
     * @param int   $offset Number of items skipped from the start.
     */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $limit,
        public readonly int $offset,
    ) {}
}
