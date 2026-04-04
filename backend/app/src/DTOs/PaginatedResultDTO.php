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
     * @param array $items
     * @param int $total
     * @param int $limit
     * @param int $offset
     * @return void
     */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $limit,
        public readonly int $offset,
    ) {}
}
