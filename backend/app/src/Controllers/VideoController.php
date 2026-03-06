<?php

namespace App\Controllers;

use App\Framework\BaseController;
use App\DTOs\UploadVideoDTO;
use App\Services\VideoService;
use App\Services\FFprobeService;
use App\Repositories\VideoRepository;

class VideoController extends BaseController
{
    private VideoService $videoService;

    public function __construct()
    {
        $this->videoService = new VideoService(new VideoRepository(), new FFprobeService());
    }

    public function upload(): void
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            $dto = UploadVideoDTO::fromRequest($_FILES['video'] ?? [], $_POST);
            $video = $this->videoService->handleUpload($userId, $dto);
            $this->jsonResponse($video, 201);
        } catch (\InvalidArgumentException $e) {
            $this->jsonResponse(['error' => ['code' => 400, 'message' => $e->getMessage()]], 400);
        } catch (\RuntimeException $e) {
            $this->jsonResponse(['error' => ['code' => 500, 'message' => $e->getMessage()]], 500);
        } catch (\Throwable $e) {
            $this->jsonResponse(['error' => ['code' => 500, 'message' => 'Upload failed: ' . $e->getMessage()]], 500);
        }
    }

    public function getAll(): void
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            $videos = $this->videoService->getAllForUser($userId);
            $this->jsonResponse($videos, 200);
        } catch (\Throwable $e) {
            $this->jsonResponse(['error' => ['code' => 500, 'message' => 'Internal server error']], 500);
        }
    }

    public function getOne(int $id): void
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            $video = $this->videoService->getOneForUser($userId, $id);
            $this->jsonResponse($video, 200);
        } catch (\RuntimeException $e) {
            $code = $e->getCode() === 404 ? 404 : 500;
            $this->jsonResponse(['error' => ['code' => $code, 'message' => $e->getMessage()]], $code);
        } catch (\Throwable $e) {
            $this->jsonResponse(['error' => ['code' => 500, 'message' => 'Internal server error']], 500);
        }
    }

    public function reanalyze(int $id): void
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            $body = $this->getJsonBody();
            $samplingRate = (int) ($body['samplingRate'] ?? 15);
            $video = $this->videoService->reanalyze($userId, $id, $samplingRate);
            $this->jsonResponse($video, 200);
        } catch (\InvalidArgumentException $e) {
            $this->jsonResponse(['error' => ['code' => 400, 'message' => $e->getMessage()]], 400);
        } catch (\RuntimeException $e) {
            $code = $e->getCode() === 404 ? 404 : 500;
            $this->jsonResponse(['error' => ['code' => $code, 'message' => $e->getMessage()]], $code);
        } catch (\Throwable $e) {
            $this->jsonResponse(['error' => ['code' => 500, 'message' => 'Internal server error']], 500);
        }
    }

    public function delete(int $id): void
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            $this->videoService->delete($userId, $id);
            $this->jsonResponse(['message' => 'Video deleted successfully'], 200);
        } catch (\RuntimeException $e) {
            $code = $e->getCode() === 404 ? 404 : 500;
            $this->jsonResponse(['error' => ['code' => $code, 'message' => $e->getMessage()]], $code);
        } catch (\Throwable $e) {
            $this->jsonResponse(['error' => ['code' => 500, 'message' => 'Internal server error']], 500);
        }
    }
}
