<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Framework\BaseController;
use App\Framework\AuthMiddleware;
use App\Framework\ServiceRegistry;
use App\DTOs\UpdateVideoDTO;
use App\DTOs\UploadVideoDTO;
use App\DTOs\ReanalyzeVideoDTO;
use App\DTOs\VideoFilterDTO;
use App\Models\Video;
use App\Services\VideoService;

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
    private VideoService $videoService;

    public function __construct()
    {
        $this->videoService = ServiceRegistry::videoService();
    }

    /** Accept a video file upload and queue it for analysis. */
    public function upload(): void
    {
        $this->handleRequest(function () {
            $userId = $this->getAuthenticatedUserId();
            $dto = UploadVideoDTO::fromRequest($_FILES['video'] ?? [], $_POST);
            $video = $this->videoService->handleUpload($userId, $dto);
            $this->jsonResponse($video->toApiArray(), 201);
        });
    }

    /** List all videos for the authenticated user with pagination and filters. */
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

    /** Get a single video's details by ID. */
    public function getOne(int $id): void
    {
        $this->handleRequest(function () use ($id) {
            $userId = $this->getAuthenticatedUserId();
            $video = $this->videoService->getOneForUser($userId, $id);
            $this->jsonResponse($video->toApiArray(), 200);
        });
    }

    /** Update a video's metadata (e.g. title). */
    public function update(int $id): void
    {
        $this->handleRequest(function () use ($id) {
            $userId = $this->getAuthenticatedUserId();
            $dto = UpdateVideoDTO::fromArray($this->getJsonBody());
            $video = $this->videoService->updateMetadata($userId, $id, $dto->originalName);
            $this->jsonResponse(['data' => $video->toApiArray()]);
        });
    }

    /** Queue a video for re-analysis with a new sampling rate. */
    public function reanalyze(int $id): void
    {
        $this->handleRequest(function () use ($id) {
            $userId = $this->getAuthenticatedUserId();
            $dto = ReanalyzeVideoDTO::fromArray($this->getJsonBody());
            $video = $this->videoService->reanalyze($userId, $id, $dto->samplingRate);
            $this->jsonResponse($video->toApiArray(), 200);
        });
    }

    /** Delete a video (admins can delete any video, users only their own). */
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

    // ──────────────────────────────────────────────
    //  Streaming helpers (HTTP concerns belong here)
    // ──────────────────────────────────────────────

    private function sendVideoHeaders(string $contentType): void
    {
        header('Content-Type: ' . $contentType);
        header('Accept-Ranges: bytes');
    }

    /**
     * Send a partial (206) response for an HTTP Range request.
     *
     * This is what allows the video player to seek — it requests
     * just the bytes it needs (e.g. "bytes=1000-1999") and we send
     * only that chunk.
     */
    private function sendPartialContent(string $filePath, int $fileSize, string $rangeHeader): void
    {
        ['start' => $start, 'end' => $end] = $this->parseRangeHeader($rangeHeader, $fileSize);
        $contentLength = $end - $start + 1;

        http_response_code(206);
        header("Content-Range: bytes {$start}-{$end}/{$fileSize}");
        header("Content-Length: {$contentLength}");

        $fileHandle = fopen($filePath, 'rb');
        fseek($fileHandle, $start);
        echo fread($fileHandle, $contentLength);
        fclose($fileHandle);
    }

    /** Send the entire file as a standard 200 response. */
    private function sendFullContent(string $filePath, int $fileSize): void
    {
        header("Content-Length: {$fileSize}");
        readfile($filePath);
    }

    /** Parse an HTTP Range header like "bytes=1000-1999" into start and end positions. */
    private function parseRangeHeader(string $rangeHeader, int $fileSize): array
    {
        preg_match('/bytes=(\d+)-(\d*)/', $rangeHeader, $matches);

        $start = (int) $matches[1];
        $end = $matches[2] !== '' ? (int) $matches[2] : $fileSize - 1;

        return ['start' => $start, 'end' => $end];
    }
}
