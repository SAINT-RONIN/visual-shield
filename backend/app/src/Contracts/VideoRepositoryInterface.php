<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\VideoFilterDTO;
use App\Models\Video;

interface VideoRepositoryInterface
{
    /**
     * Create a new video record.
     *
     * @param int $userId Owner user ID.
     * @param string $originalName Original uploaded filename.
     * @param string $storedPath Relative storage path on disk.
     * @param int $fileSize Uploaded file size in bytes.
     * @param float|null $duration Video duration in seconds when known.
     * @param int $samplingRate Requested sampling rate in frames per second.
     * @return int Newly created video ID.
     */
    public function create(int $userId, string $originalName, string $storedPath, int $fileSize, ?float $duration, int $samplingRate): int;

    /**
     * Retrieve videos owned by a user with optional filters.
     *
     * @param int $userId Owner user ID.
     * @param VideoFilterDTO $filters Validated list filters and pagination options.
     * @return Video[] Matching video models.
     */
    public function findAllByUserId(int $userId, VideoFilterDTO $filters): array;

    /**
     * Count videos owned by a user that match the supplied filters.
     *
     * @param int $userId Owner user ID.
     * @param VideoFilterDTO $filters Validated list filters and pagination options.
     * @return int Number of matching videos.
     */
    public function countAllByUserId(int $userId, VideoFilterDTO $filters): int;

    /**
     * Retrieve a single video owned by a specific user.
     *
     * @param int $id Video ID to load.
     * @param int $userId Owner user ID.
     * @return Video|null Matching video, or null if not found.
     */
    public function findByIdAndUserId(int $id, int $userId): ?Video;

    /**
     * Retrieve a single video by ID.
     *
     * @param int $id Video ID to load.
     * @return Video|null Matching video, or null if not found.
     */
    public function findById(int $id): ?Video;

    /**
     * Update the video's processing status.
     *
     * @param int $id Video ID to update.
     * @param string $status New processing status.
     * @return void
     */
    public function updateStatus(int $id, string $status): void;

    /**
     * Store the effective sampling rate used during analysis.
     *
     * @param int $id Video ID to update.
     * @param int $effectiveRate Effective frames-per-second rate.
     * @return void
     */
    public function updateEffectiveRate(int $id, int $effectiveRate): void;

    /**
     * Update frontend progress information for a video.
     *
     * @param int $id Video ID to update.
     * @param int $progress Progress percentage.
     * @param string $message Human-readable progress message.
     * @return void
     */
    public function updateProgress(int $id, int $progress, string $message): void;

    /**
     * Store an error message for a failed analysis run.
     *
     * @param int $id Video ID to update.
     * @param string $message Error message to persist.
     * @return void
     */
    public function updateError(int $id, string $message): void;

    /**
     * Reset a video so it can be analysed again.
     *
     * @param int $id Video ID to reset.
     * @param int $samplingRate Newly requested sampling rate.
     * @return void
     */
    public function resetForReanalysis(int $id, int $samplingRate): void;

    /**
     * Retrieve the next queued video for the worker.
     *
     * @return Video|null Oldest queued video, or null if the queue is empty.
     */
    public function findNextQueued(): ?Video;

    /**
     * Update the video's original display name.
     *
     * @param int $id Video ID to update.
     * @param string $originalName New original name value.
     * @return void
     */
    public function updateOriginalName(int $id, string $originalName): void;

    /**
     * Delete a video row by ID.
     *
     * @param int $id Video ID to delete.
     * @return void
     */
    public function delete(int $id): void;
}
