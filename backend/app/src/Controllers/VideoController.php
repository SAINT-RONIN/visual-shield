<?php

namespace App\Controllers;

use App\Framework\BaseController;
use App\Framework\AuthMiddleware;
use App\DTOs\UploadVideoDTO;
use App\Services\AuthService;
use App\Services\VideoService;
use App\Services\FFprobeService;
use App\Repositories\UserRepository;
use App\Repositories\TokenRepository;
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

    private function authenticateForStream(): int
    {
        // Try Authorization header first, then fall back to query parameter
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            $token = $matches[1];
        } else {
            $token = $_GET['token'] ?? '';
        }

        if (!$token) {
            $this->jsonResponse(['error' => ['code' => 401, 'message' => 'Unauthorized']], 401);
            exit;
        }

        $authService = new AuthService(new UserRepository(), new TokenRepository());
        $user = $authService->getUserFromToken($token);

        if (!$user) {
            $this->jsonResponse(['error' => ['code' => 401, 'message' => 'Invalid or expired token']], 401);
            exit;
        }

        return (int) $user['id'];
    }

    public function stream(int $id): void
    {
        try {
            $userId = $this->authenticateForStream();
            $video = $this->videoService->getRawForUser($userId, $id);

            $filePath = __DIR__ . '/../../' . $video['stored_path'];

            if (!file_exists($filePath)) {
                $this->jsonResponse(['error' => ['code' => 404, 'message' => 'Video file not found']], 404);
                return;
            }

            $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $mimeTypes = [
                'mp4' => 'video/mp4',
                'webm' => 'video/webm',
                'ogg' => 'video/ogg',
                'mov' => 'video/quicktime',
                'avi' => 'video/x-msvideo',
            ];
            $contentType = $mimeTypes[$ext] ?? 'application/octet-stream';
            $fileSize = filesize($filePath);

            header('Content-Type: ' . $contentType);
            header('Accept-Ranges: bytes');

            if (isset($_SERVER['HTTP_RANGE'])) {
                preg_match('/bytes=(\d+)-(\d*)/', $_SERVER['HTTP_RANGE'], $matches);
                $start = (int) $matches[1];
                $end = $matches[2] !== '' ? (int) $matches[2] : $fileSize - 1;
                $length = $end - $start + 1;

                http_response_code(206);
                header("Content-Range: bytes {$start}-{$end}/{$fileSize}");
                header("Content-Length: {$length}");

                $fp = fopen($filePath, 'rb');
                fseek($fp, $start);
                echo fread($fp, $length);
                fclose($fp);
            } else {
                header("Content-Length: {$fileSize}");
                readfile($filePath);
            }

            exit;
        } catch (\RuntimeException $e) {
            $code = $e->getCode() === 404 ? 404 : 500;
            $this->jsonResponse(['error' => ['code' => $code, 'message' => $e->getMessage()]], $code);
        } catch (\Throwable $e) {
            $this->jsonResponse(['error' => ['code' => 500, 'message' => 'Internal server error']], 500);
        }
    }
}
