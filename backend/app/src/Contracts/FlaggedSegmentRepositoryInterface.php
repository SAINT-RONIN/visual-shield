<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\SegmentData;
use App\DTOs\SegmentFilterDTO;
use App\Models\FlaggedSegment;

interface FlaggedSegmentRepositoryInterface
{
    /** @param SegmentData[] $segments */
    public function createBatch(int $videoId, array $segments): void;

    /** @return FlaggedSegment[] */
    public function findByVideoId(int $videoId, ?SegmentFilterDTO $filters = null): array;

    public function deleteByVideoId(int $videoId): void;
}
