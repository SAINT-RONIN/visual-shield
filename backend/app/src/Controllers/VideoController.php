<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Contracts\VideoServiceInterface;
use App\Framework\BaseController;
use App\Framework\AuthMiddleware;
use App\Framework\ServiceRegistry;
use App\DTOs\ByteRange;
use App\DTOs\UpdateVideoDTO;
use App\DTOs\UploadVideoDTO;
use App\DTOs\ReanalyzeVideoDTO;
use App\DTOs\VideoFilterDTO;
use App\Models\Video;

/**
 * HTTP layer for video management endpoints (upload, list, detail,
 * reanalyze, delete, stream).
 *
 * Accepts HTTP requests, parses input into typed DTOs, delegates business
 * logic to VideoService, and lets BaseController::handleRequest() map
 * exceptions to HTTP status codes.
 */
class VideoController extends BaseController
{
    private VideoServiceInterface $videoService;

    /**
     * Create the controller with its video service dependency.
     *
     * @return void
     */
    public function __construct()
    {
        $this->videoService = ServiceRegistry::videoService();
    }

    /**
     * Accept a video file upload and queue it for analysis.
     *
     * @return void
     */
    public function upload(): void
    {
        $this->handleRequest(function () {
            $userId = $this->getAuthenticatedUserId();
            $dto = UploadVideoDTO::fromRequest($_FILES['video'] ?? [], $_POST);
            $video = $this->videoService->handleUpload($userId, $dto);
            $this->jsonResponse(['data' => $video->toApiArray()], 201);
        });
    }

    /**
     * List all videos for the authenticated user with pagination and filters.
     *
     * @return void
     */
    public function getAll(): void
    {
        $this->handleRequest(function () {
            $userId = $this->getAuthenticatedUserId();
            $filters = VideoFilterDTO::fromQuery($_GET);
            $result = $this->videoService->getAllForUser($userId, $filters);

            $this->jsonResponse([
                'data' => array_map(fn(Video $video) => $video->toApiArray(), $result->items),
                'pagination' => [
                    'total' => $result->total,
                    'limit' => $result->limit,
                    'offset' => $result->offset,
                ],
            ]);
        });
    }

    /**
     * Get a single video's details by ID.
     *
     * @param int $id Video ID to load.
     * @return void
     */
    public function getOne(int $id): void
    {
        $this->handleRequest(function () use ($id) {
            $userId = $this->getAuthenticatedUserId();
            $video = $this->videoService->getOneForUser($userId, $id);
            $this->jsonResponse(['data' => $video->toApiArray()], 200);
        });
    }

    /**
     * Update a video's metadata (e.g. title).
     *
     * @param int $id Video ID to update.
     * @return void
     */
    public function update(int $id): void
    {
        $this->handleRequest(function () use ($id) {
            $userId = $this->getAuthenticatedUserId();
            $dto = UpdateVideoDTO::fromArray($this->getJsonBody());
            $video = $this->videoService->updateMetadata($userId, $id, $dto->originalName);
            $this->jsonResponse(['data' => $video->toApiArray()]);
        });
    }

    /**
     * Queue a video for re-analysis with a new sampling rate.
     *
     * @param int $id Video ID to requeue.
     * @return void
     */
    public function reanalyze(int $id): void
    {
        $this->handleRequest(function () use ($id) {
            $userId = $this->getAuthenticatedUserId();
            $dto = ReanalyzeVideoDTO::fromArray($this->getJsonBody());
            $video = $this->videoService->reanalyze($userId, $id, $dto->samplingRate);
            $this->jsonResponse(['data' => $video->toApiArray()], 200);
        });
    }

    /**
     * Delete a video (admins can delete any video, users only their own).
     *
     * @param int $id Video ID to delete.
     * @return void
     */
    public function delete(int $id): void
    {
        $this->handleRequest(function () use ($id) {
            $userId = $this->getAuthenticatedUserId();
            $role = $this->getAuthenticatedUserRole();

            if ($role === 'admin') {
                $this->videoService->deleteAsAdmin($id);
            } else {
                $this->videoService->delete($userId, $id);
            }

            $this->jsonResponse(['message' => 'Video deleted successfully'], 200);
        });
    }

    /**
     * Stream a video file to the client with HTTP Range request support.
     *
     * Uses header-or-query-param auth because HTML <video> elements
     * cannot set custom Authorization headers on their media requests.
     *
     * @param int $id Video ID to stream.
     * @return void
     */
    public function stream(int $id): void
    {
        $this->handleRequest(function () use ($id) {
            $userId = AuthMiddleware::authenticateFromHeaderOrQueryParam();
            $streamInfo = $this->videoService->getStreamInfo($userId, $id);

            $this->sendVideoHeaders($streamInfo->contentType);
            $rangeHeader = $_SERVER['HTTP_RANGE'] ?? null;

            if ($rangeHeader) {
                $this->sendPartialContent($streamInfo->filePath, $streamInfo->fileSize, $rangeHeader);
            } else {
                $this->sendFullContent($streamInfo->filePath, $streamInfo->fileSize);
            }

            exit;
        });
    }

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    //  Streaming helpers (HTTP concerns belong here)
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * Send baseline headers shared by full and partial video responses.
     *
     * @param string $contentType MIME type of the video being streamed.
     * @return void
     */
    private function sendVideoHeaders(string $contentType): void
    {
        header('Content-Type: ' . $contentType);
        header('Accept-Ranges: bytes');
    }

    /**
     * Send a partial (206) response for an HTTP Range request.
     *
     * This is what allows the video player to seek â€” it requests
     * just the bytes it needs (e.g. "bytes=1000-1999") and we send
     * only that chunk.
     *
     * @param string $filePath Absolute path to the video file.
     * @param int $fileSize Full file size in bytes.
     * @param string $rangeHeader Raw HTTP Range header value.
     * @return void
     */
    private function sendPartialContent(string $filePath, int $fileSize, string $rangeHeader): void
    {
        $range = $this->parseRangeHeader($rangeHeader, $fileSize);
        $contentLength = $range->end - $range->start + 1;

        http_response_code(206);
        header("Content-Range: bytes {$range->start}-{$range->end}/{$fileSize}");
        header("Content-Length: {$contentLength}");

        $fileHandle = fopen($filePath, 'rb');
        fseek($fileHandle, $range->start);
        echo fread($fileHandle, $contentLength);
        fclose($fileHandle);
    }

    /**
     * Send the entire file as a standard 200 response.
     *
     * @param string $filePath Absolute path to the video file.
     * @param int $fileSize Full file size in bytes.
     * @return void
     */
    private function sendFullContent(string $filePath, int $fileSize): void
    {
        header("Content-Length: {$fileSize}");
        readfile($filePath);
    }

    /**
     * Parse an HTTP Range header like "bytes=1000-1999" into start and end positions.
     *
     * @param string $rangeHeader Raw HTTP Range header value.
     * @param int $fileSize Full file size in bytes.
     * @return ByteRange Parsed byte range boundaries.
     */
    private function parseRangeHeader(string $rangeHeader, int $fileSize): ByteRange
    {
        preg_match('/bytes=(\d+)-(\d*)/', $rangeHeader, $matches);

        $start = (int) $matches[1];
        $end = $matches[2] !== '' ? (int) $matches[2] : $fileSize - 1;

        return new ByteRange(start: $start, end: $end);
    }
}
