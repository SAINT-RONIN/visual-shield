<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\DatapointData;
use App\Models\AnalysisDatapoint;

interface AnalysisDatapointRepositoryInterface
{
    /** @param DatapointData[] $datapoints */
    /**
     * @param int $videoId
     * @param array $datapoints
     * @return void
     */
    public function createBatch(int $videoId, array $datapoints): void;

    /** @return AnalysisDatapoint[] */
    public function findByVideoId(int $videoId): array;

    /**
     * @param int $videoId
     * @return void
     */
    public function deleteByVideoId(int $videoId): void;
}
