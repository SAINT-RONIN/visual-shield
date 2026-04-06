<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\Interfaces\VideoServiceInterface;
use App\Framework\BaseController;
use App\Framework\AuthMiddleware;
use App\Framework\ServiceRegistry;
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
     * Stream a video file to the client.
     *
     * PHP authenticates the request then delegates actual byte delivery to
     * nginx via X-Accel-Redirect. This avoids PHP buffering the entire file
     * (which caused ERR_CONTENT_LENGTH_MISMATCH on large videos) and gives
     * nginx native range-request support for seeking.
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

            header('Content-Type: ' . $streamInfo->contentType);
            header('X-Accel-Redirect: /internal-videos/' . basename($streamInfo->filePath));
            exit;
        });
    }
}
