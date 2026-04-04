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
 * Handles the business rules around uploaded videos.
 *
 * This service sits between the controllers and repositories so the rest of
 * the app can treat upload, deletion, re-analysis, and streaming as one
 * consistent workflow instead of a bunch of scattered steps.
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

    /**
     * Create the service with its video repository and metadata utility.
     *
     * @param VideoRepository $videoRepo Repository used for video CRUD and queue state.
     * @param FFprobe $ffprobe Utility used to inspect uploaded video metadata.
     * @return void
     */
    public function __construct(
        private VideoRepository $videoRepo,
        private FFprobe $ffprobe,
    ) {}

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    //  Upload
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * This is the full upload flow from the service point of view: validate
     * the file, read the metadata we need, store it safely on disk, and save
     * the matching database record the worker will later pick up.
     *
     * @param int $userId The ID of the user uploading the video.
     * @param UploadVideoDTO $dto Contains the uploaded file info and requested sampling rate.
     * @return Video Newly created queued video model.
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

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    //  Retrieval
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * This returns the current user's video list in a shape the frontend can
     * page through, sort, and filter without the controller having to glue
     * those pieces together by hand.
     *
     * @param int $userId Authenticated user whose videos should be listed.
     * @param VideoFilterDTO $filters Validated filter, sort, and pagination options.
     * @return PaginatedResultDTO Paginated collection of matching videos.
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

    /**
     * This updates a video's editable metadata after first checking that the
     * user actually owns the video they are trying to change.
     *
     * @param int $userId Authenticated owner of the video.
     * @param int $videoId Video ID to update.
     * @param string $originalName New original name to persist.
     * @return Video Refreshed video model.
     */
    public function updateMetadata(int $userId, int $videoId, string $originalName): Video
    {
        $this->findUserVideoOrFail($this->videoRepo, $userId, $videoId);
        $this->videoRepo->updateOriginalName($videoId, $originalName);

        return $this->findVideoOrFail($videoId);
    }

    /**
     * This loads one video for the current user and treats "not found" and
     * "not yours" as the same outcome, which is safer than confirming another
     * user's video exists.
     *
     * @param int $userId Authenticated owner of the video.
     * @param int $videoId Video ID to load.
     * @return Video Matching video model.
     */
    public function getOneForUser(int $userId, int $videoId): Video
    {
        return $this->findUserVideoOrFail($this->videoRepo, $userId, $videoId);
    }

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    //  Deletion
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * This deletes both the stored video file and its database row so we do
     * not leave orphaned files behind after a user removes an upload.
     *
     * @param int $userId Authenticated owner of the video.
     * @param int $videoId Video ID to delete.
     * @return void
     */
    public function delete(int $userId, int $videoId): void
    {
        $video = $this->findUserVideoOrFail($this->videoRepo, $userId, $videoId);

        $this->deleteVideoFileFromDisk($video->storedPath);
        $this->videoRepo->delete($videoId);
    }

    /**
     * This is the admin version of deletion, which skips the normal ownership
     * check because moderators sometimes need to remove someone else's upload.
     *
     * @param int $videoId Video ID to delete.
     * @return void
     */
    public function deleteAsAdmin(int $videoId): void
    {
        $video = $this->findVideoOrFail($videoId);

        $this->deleteVideoFileFromDisk($video->storedPath);
        $this->videoRepo->delete($videoId);
    }

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    //  Re-analysis
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * This sends an already uploaded video back through the analysis pipeline
     * with a different sampling rate, which is useful when the user wants a
     * faster result or a more detailed re-check.
     *
     * @param int $userId Authenticated owner of the video.
     * @param int $videoId Video ID to requeue.
     * @param int $samplingRate Newly requested sampling rate.
     * @return Video Refreshed video model after queue reset.
     */
    public function reanalyze(int $userId, int $videoId, int $samplingRate): Video
    {
        $this->findUserVideoOrFail($this->videoRepo, $userId, $videoId);
        $this->validateSamplingRate($samplingRate);

        $this->videoRepo->resetForReanalysis($videoId, $samplingRate);

        return $this->findVideoOrFail($videoId);
    }

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    //  Streaming
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * This prepares everything the controller needs to stream a video back to
     * the browser, including the resolved path, file size, and content type.
     * We keep that lookup here because the ownership check still matters even
     * when the user only wants to play the file.
     *
     * @param int $userId Authenticated owner of the video.
     * @param int $videoId Video ID to stream.
     * @return StreamInfo File path and metadata needed for streaming.
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

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    //  Validation helpers
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * This checks the file's real MIME type instead of trusting the filename,
     * because a renamed non-video file should never enter the analysis flow
     * just because it ends with something like ".mp4".
     *
     * @param string $temporaryFilePath Temporary uploaded file path to inspect.
     * @return void
     */
    private function validateFileType(string $temporaryFilePath): void
    {
        $fileInfo = new \finfo(FILEINFO_MIME_TYPE);
        $detectedMimeType = $fileInfo->file($temporaryFilePath);

        if (!in_array($detectedMimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw new ValidationException('Invalid file type. Allowed: MP4, WebM');
        }
    }

    /**
     * This enforces the upload size limit early so we do not waste storage,
     * metadata work, or analysis time on files that are already too large.
     *
     * @param int $fileSizeInBytes Uploaded file size in bytes.
     * @return void
     */
    private function validateFileSize(int $fileSizeInBytes): void
    {
        if ($fileSizeInBytes > AnalysisConfig::MAX_FILE_SIZE) {
            $maxSizeInMegabytes = AnalysisConfig::MAX_FILE_SIZE / self::BYTES_PER_MEGABYTE;
            throw new ValidationException("File too large. Maximum: {$maxSizeInMegabytes} MB");
        }
    }

    /**
     * This keeps the sampling rate inside the small set of values the app
     * actually supports, which helps the UI, config, and worker all stay in
     * sync about what is allowed.
     *
     * @param int $samplingRate Requested sampling rate in frames per second.
     * @return void
     */
    private function validateSamplingRate(int $samplingRate): void
    {
        if (!in_array($samplingRate, AnalysisConfig::ALLOWED_SAMPLING_RATES, true)) {
            $allowedRates = implode(', ', AnalysisConfig::ALLOWED_SAMPLING_RATES);
            throw new ValidationException("Invalid sampling rate. Allowed: {$allowedRates}");
        }
    }

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    //  Metadata extraction
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * This asks FFprobe for the video's duration and turns unreadable-video
     * cases into a clean validation error the frontend can show to the user.
     *
     * @param string $temporaryFilePath Temporary uploaded file path to inspect.
     * @return float Duration in seconds.
     */
    private function extractVideoDuration(string $temporaryFilePath): float
    {
        try {
            return $this->ffprobe->getDuration($temporaryFilePath);
        } catch (\RuntimeException $e) {
            // FFprobe returns empty/null output when the file is not a valid video
            // (unrecognised format, corrupt file). That is a user error â€” 400.
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

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    //  Sampling rate adjustment
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * This protects the system from trying to extract an unrealistic number of
     * frames from long videos, because more frames means more files, more CPU,
     * and much slower analysis once we pass the safe cap.
     *
     * @param float $durationInSeconds Video duration in seconds.
     * @param int $requestedRate Requested sampling rate in frames per second.
     * @return int Safe sampling rate to use.
     */
    private function calculateSafeSamplingRate(float $durationInSeconds, int $requestedRate): int
    {
        $totalFramesAtRequestedRate = $durationInSeconds * $requestedRate;

        if ($totalFramesAtRequestedRate <= AnalysisConfig::MAX_TOTAL_FRAMES) {
            return $requestedRate;
        }

        return $this->findHighestAllowedRate($durationInSeconds);
    }

    /**
     * This walks the allowed rates from highest to lowest so we keep as much
     * detail as possible while still staying inside the global frame limit.
     *
     * @param float $durationInSeconds Video duration in seconds.
     * @return int Highest allowed sampling rate that stays within limits.
     */
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

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    //  File storage
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * This moves the uploaded temp file into the app's storage area using a
     * random filename so we avoid collisions and do not expose the original
     * user-supplied name on disk.
     *
     * @param string $temporaryFilePath Temporary uploaded file path to move.
     * @param string $originalFilename Original client-side filename.
     * @return string Relative storage path of the moved file.
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

    /**
     * This removes the physical video file if it is still present, which keeps
     * deletion consistent between the database and the filesystem.
     *
     * @param string $storedPath Relative storage path of the video file.
     * @return void
     */
    private function deleteVideoFileFromDisk(string $storedPath): void
    {
        $fullPath = PathResolver::resolve($storedPath);

        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }

    /**
     * This maps a stored video file to the content type the browser expects
     * when we stream it back, which helps playback work correctly.
     *
     * @param string $filePath Absolute path to the stored video file.
     * @return string MIME type suitable for the response header.
     */
    private function detectVideoContentType(string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        return self::MIME_TYPES_BY_EXTENSION[$extension] ?? 'application/octet-stream';
    }

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    //  Lookup helpers
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * This is the internal "load any video or fail" helper we use in the few
     * cases where ownership is not part of the lookup, such as admin actions.
     *
     * @param int $videoId Video ID to load.
     * @return Video Matching video model.
     */
    private function findVideoOrFail(int $videoId): Video
    {
        return $this->findOrFail($this->videoRepo->findById($videoId), 'Video not found');
    }
}
