<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\DTOs\PaginatedResultDTO;
use App\DTOs\StreamInfo;
use App\DTOs\UploadVideoDTO;
use App\DTOs\VideoFilterDTO;
use App\Models\Video;

interface VideoServiceInterface
{
    /**
     * Process a new video upload from start to finish.
     *
     * @param int $userId Authenticated user uploading the video.
     * @param UploadVideoDTO $dto Validated upload request data.
     * @return Video Newly created queued video model.
     */
    public function handleUpload(int $userId, UploadVideoDTO $dto): Video;

    /**
     * Get all videos belonging to a user, with optional filtering, sorting, and pagination.
     *
     * @param int $userId Authenticated user whose videos should be listed.
     * @param VideoFilterDTO $filters Validated list filters and pagination options.
     * @return PaginatedResultDTO Paginated collection of matching videos.
     */
    public function getAllForUser(int $userId, VideoFilterDTO $filters): PaginatedResultDTO;

    /**
     * Update a video's metadata (title/original name).
     *
     * @param int $userId Authenticated owner of the video.
     * @param int $videoId Video ID to update.
     * @param string $originalName New original name value.
     * @return Video Refreshed video model.
     */
    public function updateMetadata(int $userId, int $videoId, string $originalName): Video;

    /**
     * Get a single video belonging to a user.
     *
     * @param int $userId Authenticated owner of the video.
     * @param int $videoId Video ID to load.
     * @return Video Matching video model.
     */
    public function getOneForUser(int $userId, int $videoId): Video;

    /**
     * Delete a video's file from disk and its record from the database.
     *
     * @param int $userId Authenticated owner of the video.
     * @param int $videoId Video ID to delete.
     * @return void
     */
    public function delete(int $userId, int $videoId): void;

    /**
     * Delete any video as admin (no ownership check).
     *
     * @param int $videoId Video ID to delete.
     * @return void
     */
    public function deleteAsAdmin(int $videoId): void;

    /**
     * Queue a video for re-analysis at a different sampling rate.
     *
     * @param int $userId Authenticated owner of the video.
     * @param int $videoId Video ID to requeue.
     * @param int $samplingRate Newly requested sampling rate.
     * @return Video Refreshed video model after queue reset.
     */
    public function reanalyze(int $userId, int $videoId, int $samplingRate): Video;

    /**
     * Resolve the file path, size, and content type for streaming a video.
     *
     * @param int $userId Authenticated owner of the video.
     * @param int $videoId Video ID to stream.
     * @return StreamInfo Stream metadata and file path information.
     */
    public function getStreamInfo(int $userId, int $videoId): StreamInfo;
}
