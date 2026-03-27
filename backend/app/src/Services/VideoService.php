<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\AnalysisConfig;
use App\Contracts\VideoServiceInterface;
use App\DTOs\PaginatedResultDTO;
use App\DTOs\StreamInfo;
use App\DTOs\UploadVideoDTO;
use App\DTOs\VideoFilterDTO;
use App\Exceptions\ValidationException;
use App\Models\Video;
use App\Repositories\VideoRepository;
use App\Utils\FFprobe;
use App\Utils\FileSystem;
use App\Utils\PathResolver;

/**
 * Manages the full video lifecycle: upload, retrieval, deletion, and re-analysis.
 *
 * When a user uploads a video, this service:
 *   1. Validates the file (correct type? not too large?)
 *   2. Extracts metadata like duration using FFprobe
 *   3. Adjusts the sampling rate if needed (to avoid extracting too many frames)
 *   4. Moves the file to permanent storage with a unique filename
 *   5. Saves the video record to the database
 */
class VideoService extends BaseService implements VideoServiceInterface
{
    /** Video MIME types we accept for upload. */
    private const ALLOWED_MIME_TYPES = [
        'video/mp4',
        'video/webm',
        'video/quicktime',
        'video/x-msvideo',
    ];

    /** Number of bytes in one megabyte, used for human-readable error messages. */
    private const BYTES_PER_MEGABYTE = 1_048_576;

    /** Known video file extensions and their MIME types. */
    private const MIME_TYPES_BY_EXTENSION = [
        'mp4' => 'video/mp4',
        'webm' => 'video/webm',
        'ogg' => 'video/ogg',
        'mov' => 'video/quicktime',
        'avi' => 'video/x-msvideo',
    ];

    public function __construct(
        private VideoRepository $videoRepo,
        private FFprobe $ffprobe,
    ) {}

    // ──────────────────────────────────────────────
    //  Upload
    // ──────────────────────────────────────────────

    /**
     * Process a new video upload from start to finish.
     *
     * @param  int            $userId The ID of the user uploading the video.
     * @param  UploadVideoDTO $dto    Contains the uploaded file info and requested sampling rate.
     */
    public function handleUpload(int $userId, UploadVideoDTO $dto): Video
    {
        $this->validateFileType($dto->file->tmpName);
        $this->validateFileSize($dto->file->size);

        $duration = $this->extractVideoDuration($dto->file->tmpName);
        $effectiveRate = $this->calculateSafeSamplingRate($duration, $dto->samplingRate);
        $storedPath = $this->moveUploadToPermanentStorage($dto->file->tmpName, $dto->file->name);

        $videoId = $this->videoRepo->create(
            $userId,
            $dto->file->name,
            $storedPath,
            $dto->file->size,
            $duration,
            $dto->samplingRate,
        );

        // If we had to lower the sampling rate, save the adjusted value
        if ($effectiveRate !== $dto->samplingRate) {
            $this->videoRepo->updateEffectiveRate($videoId, $effectiveRate);
        }

        return $this->findVideoOrFail($videoId);
    }

    // ──────────────────────────────────────────────
    //  Retrieval
    // ──────────────────────────────────────────────

    /**
     * Get all videos belonging to a user, with optional filtering, sorting, and pagination.
     */
    public function getAllForUser(int $userId, VideoFilterDTO $filters): PaginatedResultDTO
    {
        $videos = $this->videoRepo->findAllByUserId($userId, $filters);
        $total = $this->videoRepo->countAllByUserId($userId, $filters);

        return new PaginatedResultDTO(
            items: $videos,
            total: $total,
            limit: $filters->limit,
            offset: $filters->offset,
        );
    }

    /** Update a video's metadata (title/original name). */
    public function updateMetadata(int $userId, int $videoId, string $originalName): Video
    {
        $this->findUserVideoOrFail($this->videoRepo, $userId, $videoId);
        $this->videoRepo->updateOriginalName($videoId, $originalName);

        return $this->findVideoOrFail($videoId);
    }

    /** Get a single video belonging to a user. */
    public function getOneForUser(int $userId, int $videoId): Video
    {
        return $this->findUserVideoOrFail($this->videoRepo, $userId, $videoId);
    }

    // ──────────────────────────────────────────────
    //  Deletion
    // ──────────────────────────────────────────────

    /** Delete a video's file from disk and its record from the database. */
    public function delete(int $userId, int $videoId): void
    {
        $video = $this->findUserVideoOrFail($this->videoRepo, $userId, $videoId);

        $this->deleteVideoFileFromDisk($video->storedPath);
        $this->videoRepo->delete($videoId);
    }

    /** Delete any video as admin (no ownership check). */
    public function deleteAsAdmin(int $videoId): void
    {
        $video = $this->findVideoOrFail($videoId);

        $this->deleteVideoFileFromDisk($video->storedPath);
        $this->videoRepo->delete($videoId);
    }

    // ──────────────────────────────────────────────
    //  Re-analysis
    // ──────────────────────────────────────────────

    /**
     * Queue a video for re-analysis at a different sampling rate.
     *
     * Resets the video status to "pending" so the background worker
     * picks it up and runs the analysis pipeline again.
     */
    public function reanalyze(int $userId, int $videoId, int $samplingRate): Video
    {
        $this->findUserVideoOrFail($this->videoRepo, $userId, $videoId);
        $this->validateSamplingRate($samplingRate);

        $this->videoRepo->resetForReanalysis($videoId, $samplingRate);

        return $this->findVideoOrFail($videoId);
    }

    // ──────────────────────────────────────────────
    //  Streaming
    // ──────────────────────────────────────────────

    /**
     * Resolve the file path, size, and content type for streaming a video.
     *
     * Verifies ownership and that the file exists on disk, then returns
     * a StreamInfo DTO the controller uses to send the HTTP response.
     */
    public function getStreamInfo(int $userId, int $videoId): StreamInfo
    {
        $video = $this->findUserVideoOrFail($this->videoRepo, $userId, $videoId);
        $filePath = PathResolver::resolveOrFail($video->storedPath);

        $fileSize = filesize($filePath);
        $contentType = $this->detectVideoContentType($filePath);

        return new StreamInfo(
            filePath: $filePath,
            fileSize: $fileSize,
            contentType: $contentType,
        );
    }

    // ──────────────────────────────────────────────
    //  Validation helpers
    // ──────────────────────────────────────────────

    /**
     * Check that the uploaded file is actually a video.
     * Uses the file's magic bytes (not the extension) to detect the real type.
     */
    private function validateFileType(string $temporaryFilePath): void
    {
        $fileInfo = new \finfo(FILEINFO_MIME_TYPE);
        $detectedMimeType = $fileInfo->file($temporaryFilePath);

        if (!in_array($detectedMimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw new ValidationException('Invalid file type. Allowed: MP4, WebM');
        }
    }

    /** Check that the file doesn't exceed the maximum allowed size. */
    private function validateFileSize(int $fileSizeInBytes): void
    {
        if ($fileSizeInBytes > AnalysisConfig::MAX_FILE_SIZE) {
            $maxSizeInMegabytes = AnalysisConfig::MAX_FILE_SIZE / self::BYTES_PER_MEGABYTE;
            throw new ValidationException("File too large. Maximum: {$maxSizeInMegabytes} MB");
        }
    }

    /** Check that the requested sampling rate is one of the allowed options. */
    private function validateSamplingRate(int $samplingRate): void
    {
        if (!in_array($samplingRate, AnalysisConfig::ALLOWED_SAMPLING_RATES, true)) {
            $allowedRates = implode(', ', AnalysisConfig::ALLOWED_SAMPLING_RATES);
            throw new ValidationException("Invalid sampling rate. Allowed: {$allowedRates}");
        }
    }

    // ──────────────────────────────────────────────
    //  Metadata extraction
    // ──────────────────────────────────────────────

    /** Use FFprobe to read the video's duration in seconds. */
    private function extractVideoDuration(string $temporaryFilePath): float
    {
        try {
            return $this->ffprobe->getDuration($temporaryFilePath);
        } catch (\RuntimeException $e) {
            // FFprobe returns empty/null output when the file is not a valid video
            // (unrecognised format, corrupt file). That is a user error — 400.
            // Any other failure (binary missing, disk error, unexpected output)
            // keeps its RuntimeException so BaseController maps it to a 500.
            $message = $e->getMessage();
            if (str_contains($message, 'Failed to read video duration')) {
                throw new ValidationException(
                    'Could not read video metadata. Please ensure the file is a valid video.'
                );
            }

            throw $e;
        }
    }

    // ──────────────────────────────────────────────
    //  Sampling rate adjustment
    // ──────────────────────────────────────────────

    /**
     * Lower the sampling rate if needed to stay within the frame limit.
     *
     * For example, a 60-minute video at 30fps would produce 108,000 frames,
     * which is way over our 10,000 frame cap. This function finds the highest
     * allowed rate that stays under the limit.
     */
    private function calculateSafeSamplingRate(float $durationInSeconds, int $requestedRate): int
    {
        $totalFramesAtRequestedRate = $durationInSeconds * $requestedRate;

        if ($totalFramesAtRequestedRate <= AnalysisConfig::MAX_TOTAL_FRAMES) {
            return $requestedRate;
        }

        return $this->findHighestAllowedRate($durationInSeconds);
    }

    /** Find the highest allowed sampling rate that keeps total frames within the cap. */
    private function findHighestAllowedRate(float $durationInSeconds): int
    {
        $maximumSafeRate = (int) floor(AnalysisConfig::MAX_TOTAL_FRAMES / $durationInSeconds);

        // Sort rates highest-first so we pick the best quality that fits
        $allowedRates = AnalysisConfig::ALLOWED_SAMPLING_RATES;
        rsort($allowedRates);

        foreach ($allowedRates as $rate) {
            if ($rate <= $maximumSafeRate) {
                return $rate;
            }
        }

        // If even the lowest rate is too high, use it anyway (best we can do)
        return end($allowedRates);
    }

    // ──────────────────────────────────────────────
    //  File storage
    // ──────────────────────────────────────────────

    /**
     * Move the uploaded temp file to permanent storage with a unique filename.
     *
     * Uses a UUID-style filename to prevent collisions and avoid exposing
     * original filenames on disk. Returns the relative path for the database.
     */
    private function moveUploadToPermanentStorage(string $temporaryFilePath, string $originalFilename): string
    {
        $fileExtension = pathinfo($originalFilename, PATHINFO_EXTENSION) ?: 'mp4';
        $uniqueFilename = bin2hex(random_bytes(AnalysisConfig::STORAGE_FILENAME_RANDOM_BYTES)) . '.' . $fileExtension;
        $storageDirectory = AnalysisConfig::appRoot() . '/storage/videos';

        FileSystem::ensureDirectoryExists($storageDirectory);
        FileSystem::ensureWritable($storageDirectory);

        $destinationPath = "{$storageDirectory}/{$uniqueFilename}";

        if (!move_uploaded_file($temporaryFilePath, $destinationPath)) {
            throw new \RuntimeException('Failed to move uploaded file to storage');
        }

        return "storage/videos/{$uniqueFilename}";
    }

    /** Delete the video file from disk if it still exists. */
    private function deleteVideoFileFromDisk(string $storedPath): void
    {
        $fullPath = PathResolver::resolve($storedPath);

        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }

    /** Determine the MIME type of a video file from its extension. */
    private function detectVideoContentType(string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        return self::MIME_TYPES_BY_EXTENSION[$extension] ?? 'application/octet-stream';
    }

    // ──────────────────────────────────────────────
    //  Lookup helpers
    // ──────────────────────────────────────────────

    /** Find a video by ID (with enriched data), or throw an error. */
    private function findVideoOrFail(int $videoId): Video
    {
        return $this->findOrFail($this->videoRepo->findById($videoId), 'Video not found');
    }
}
