<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Config\AnalysisConfig;

/**
 * Captures and validates query parameters for the admin user list.
 */
final class UserFilterDTO
{
    public function __construct(
        public readonly ?string $role = null,
        public readonly ?string $search = null,
        public readonly string $sort = 'created_at',
        public readonly string $order = 'asc',
        public readonly int $limit = 100,
        public readonly int $offset = 0,
    ) {}

    // Builds a validated filter DTO from raw query parameters.
    public static function fromQuery(array $query): self
    {
        $validRoles = ['admin', 'member'];
        $validSorts = ['created_at', 'username', 'role'];
        $validOrders = ['asc', 'desc'];

        $role = isset($query['role']) && \in_array($query['role'], $validRoles, true)
            ? $query['role']
            : null;

        $searchRaw = isset($query['search']) ? trim((string) $query['search']) : '';
        $search = $searchRaw !== '' ? $searchRaw : null;

        $sort = isset($query['sort']) && \in_array($query['sort'], $validSorts, true)
            ? $query['sort']
            : 'created_at';

        $order = isset($query['order']) && \in_array($query['order'], $validOrders, true)
            ? $query['order']
            : 'asc';

        $rawLimit = isset($query['limit']) ? (int) $query['limit'] : 100;
        $limit = max(AnalysisConfig::PAGINATION_MIN_LIMIT, min(AnalysisConfig::PAGINATION_MAX_LIMIT, $rawLimit));

        $rawOffset = isset($query['offset']) ? (int) $query['offset'] : 0;
        $offset = max(0, $rawOffset);

        return new self(
            role: $role,
            search: $search,
            sort: $sort,
            order: $order,
            limit: $limit,
            offset: $offset,
        );
    }
}
