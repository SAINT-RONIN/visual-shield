<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\PaginatedResultDTO;
use App\DTOs\StreamInfo;
use App\DTOs\UploadVideoDTO;
use App\DTOs\VideoFilterDTO;
use App\Models\Video;

interface VideoServiceInterface
{
    /**
     * Process a new video upload from start to finish.
     */
    public function handleUpload(int $userId, UploadVideoDTO $dto): Video;

    /**
     * Get all videos belonging to a user, with optional filtering, sorting, and pagination.
     */
    public function getAllForUser(int $userId, VideoFilterDTO $filters): PaginatedResultDTO;

    /** Update a video's metadata (title/original name). */
    public function updateMetadata(int $userId, int $videoId, string $originalName): Video;

    /** Get a single video belonging to a user. */
    public function getOneForUser(int $userId, int $videoId): Video;

    /** Delete a video's file from disk and its record from the database. */
    public function delete(int $userId, int $videoId): void;

    /** Delete any video as admin (no ownership check). */
    public function deleteAsAdmin(int $videoId): void;

    /**
     * Queue a video for re-analysis at a different sampling rate.
     */
    public function reanalyze(int $userId, int $videoId, int $samplingRate): Video;

    /**
     * Resolve the file path, size, and content type for streaming a video.
     */
    public function getStreamInfo(int $userId, int $videoId): StreamInfo;
}
