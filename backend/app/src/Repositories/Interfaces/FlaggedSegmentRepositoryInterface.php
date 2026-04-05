<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\DTOs\SegmentData;
use App\DTOs\SegmentFilterDTO;
use App\Models\FlaggedSegment;

interface FlaggedSegmentRepositoryInterface
{
    /** @param SegmentData[] $segments */
    /**
     * @param int $videoId
     * @param array $segments
     * @return void
     */
    public function createBatch(int $videoId, array $segments): void;

    /** @return FlaggedSegment[] */
    /**
     * @param int $videoId
     * @param ?SegmentFilterDTO $filters
     * @return array
     */
    public function findByVideoId(int $videoId, ?SegmentFilterDTO $filters = null): array;

    /**
     * @param int $videoId
     * @return void
     */
    public function deleteByVideoId(int $videoId): void;
}
