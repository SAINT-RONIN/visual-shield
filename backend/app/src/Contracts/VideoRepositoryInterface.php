<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\VideoFilterDTO;
use App\Models\Video;

interface VideoRepositoryInterface
{
    public function create(int $userId, string $originalName, string $storedPath, int $fileSize, ?float $duration, int $samplingRate): int;

    /** @return Video[] */
    public function findAllByUserId(int $userId, VideoFilterDTO $filters): array;

    public function countAllByUserId(int $userId, VideoFilterDTO $filters): int;

    public function findByIdAndUserId(int $id, int $userId): ?Video;

    public function findById(int $id): ?Video;

    public function updateStatus(int $id, string $status): void;

    public function updateEffectiveRate(int $id, int $effectiveRate): void;

    public function updateProgress(int $id, int $progress, string $message): void;

    public function updateError(int $id, string $message): void;

    public function resetForReanalysis(int $id, int $samplingRate): void;

    public function findNextQueued(): ?Video;

    public function updateOriginalName(int $id, string $originalName): void;

    public function delete(int $id): void;
}
