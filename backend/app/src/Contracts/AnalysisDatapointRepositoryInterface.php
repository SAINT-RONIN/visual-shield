<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\DatapointData;
use App\Models\AnalysisDatapoint;

interface AnalysisDatapointRepositoryInterface
{
    /** @param DatapointData[] $datapoints */
    public function createBatch(int $videoId, array $datapoints): void;

    /** @return AnalysisDatapoint[] */
    public function findByVideoId(int $videoId): array;

    public function deleteByVideoId(int $videoId): void;
}
