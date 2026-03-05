<?php

namespace App\Services;

use App\Config\AnalysisConfig;
use App\DTOs\UploadVideoDTO;
use App\Repositories\VideoRepository;

class VideoService
{
    public function __construct(
        private VideoRepository $videoRepo,
        private FFprobeService $ffprobe,
    ) {}

    public function handleUpload(int $userId, UploadVideoDTO $dto): array
    {
        $this->validateFileType($dto->file['tmp_name']);
        $this->validateFileSize($dto->file['size']);

        $duration = $this->ffprobe->getDuration($dto->file['tmp_name']);
        $effectiveRate = $this->adjustSamplingRate($duration, $dto->samplingRate);
        $storedPath = $this->storeFile($dto->file['tmp_name'], $dto->file['name']);

        $id = $this->videoRepo->create(
            $userId,
            $dto->file['name'],
            $storedPath,
            $dto->file['size'],
            $duration,
            $dto->samplingRate,
        );

        if ($effectiveRate !== $dto->samplingRate) {
            $this->videoRepo->updateEffectiveRate($id, $effectiveRate);
        }

        return $this->formatVideo($this->videoRepo->findById($id));
    }

    public function getAllForUser(int $userId): array
    {
        return array_map([$this, 'formatVideo'], $this->videoRepo->findAllByUserId($userId));
    }

    public function getOneForUser(int $userId, int $videoId): array
    {
        $video = $this->videoRepo->findByIdAndUserId($videoId, $userId);

        if (!$video) {
            throw new \RuntimeException('Video not found', 404);
        }

        return $this->formatVideo($video);
    }

    public function delete(int $userId, int $videoId): void
    {
        $video = $this->videoRepo->findByIdAndUserId($videoId, $userId);

        if (!$video) {
            throw new \RuntimeException('Video not found', 404);
        }

        $fullPath = __DIR__ . '/../../' . $video['stored_path'];

        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        $this->videoRepo->delete($videoId);
    }

    private function validateFileType(string $tmpPath): void
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmpPath);
        $allowed = ['video/mp4', 'video/webm', 'video/quicktime', 'video/x-msvideo'];

        if (!in_array($mime, $allowed, true)) {
            throw new \InvalidArgumentException('Invalid file type. Allowed: MP4, WebM');
        }
    }

    private function validateFileSize(int $size): void
    {
        if ($size > AnalysisConfig::MAX_FILE_SIZE) {
            throw new \InvalidArgumentException('File too large. Maximum: ' . (AnalysisConfig::MAX_FILE_SIZE / 1048576) . ' MB');
        }
    }

    private function adjustSamplingRate(float $duration, int $requestedRate): int
    {
        $totalFrames = $duration * $requestedRate;

        if ($totalFrames <= AnalysisConfig::MAX_TOTAL_FRAMES) {
            return $requestedRate;
        }

        $adjusted = (int) floor(AnalysisConfig::MAX_TOTAL_FRAMES / $duration);
        $rates = AnalysisConfig::ALLOWED_SAMPLING_RATES;
        sort($rates);

        foreach (array_reverse($rates) as $rate) {
            if ($rate <= $adjusted) {
                return $rate;
            }
        }

        return $rates[0];
    }

    private function storeFile(string $tmpPath, string $originalName): string
    {
        $ext = pathinfo($originalName, PATHINFO_EXTENSION) ?: 'mp4';
        $uuid = bin2hex(random_bytes(16));
        $filename = "{$uuid}.{$ext}";
        $dir = __DIR__ . '/../../storage/videos';

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $dest = "{$dir}/{$filename}";
        move_uploaded_file($tmpPath, $dest);

        return "storage/videos/{$filename}";
    }

    private function formatVideo(array $video): array
    {
        return [
            'id' => (int) $video['id'],
            'originalName' => $video['original_name'],
            'fileSize' => (int) $video['file_size'],
            'duration' => $video['duration_seconds'] ? (float) $video['duration_seconds'] : null,
            'status' => $video['status'],
            'samplingRate' => (int) $video['sampling_rate'],
            'effectiveRate' => $video['effective_rate'] ? (int) $video['effective_rate'] : null,
            'createdAt' => $video['created_at'],
            'updatedAt' => $video['updated_at'],
        ];
    }
}
