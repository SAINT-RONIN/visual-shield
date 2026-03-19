<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Extracts individual JPEG frames from a video using FFmpeg.
 *
 * When we analyze a video, we can't look at the video file directly —
 * we need individual image files (frames) that we can compare to each other.
 * This service uses FFmpeg to take "screenshots" of the video at regular
 * intervals and saves them as numbered JPEG files.
 *
 * Example: at 10fps, a 5-second video produces 50 frame files:
 *   frame_00001.jpg, frame_00002.jpg, ..., frame_00050.jpg
 *
 * The caller MUST call cleanup() after analysis to delete the temporary files.
 */
class FrameExtractor
{
    /**
     * Extract frames from a video at the given sampling rate.
     *
     * @param  string $videoPath         Absolute path to the source video file.
     * @param  int    $samplingRate      How many frames per second to extract.
     * @param  string $outputDirectory   Directory to write frame JPEGs into.
     * @return string[] Sorted list of absolute paths to the extracted frame files.
     *
     * @throws \RuntimeException If directory creation or FFmpeg fails.
     */
    public function extract(string $videoPath, int $samplingRate, string $outputDirectory): array
    {
        $this->ensureDirectoryExists($outputDirectory);
        $this->runFFmpegFrameExtraction($videoPath, $samplingRate, $outputDirectory);

        return $this->collectExtractedFramePaths($outputDirectory);
    }

    /** Delete all extracted frame files and remove the output directory. */
    public function cleanup(string $outputDirectory): void
    {
        $this->deleteAllFrameFiles($outputDirectory);
        $this->removeDirectoryIfEmpty($outputDirectory);
    }

    // ──────────────────────────────────────────────
    //  Directory management
    // ──────────────────────────────────────────────

    /** Create the output directory if it doesn't already exist. */
    private function ensureDirectoryExists(string $directoryPath): void
    {
        if (is_dir($directoryPath)) {
            return;
        }

        $created = mkdir($directoryPath, 0755, true);

        if (!$created) {
            throw new \RuntimeException("Failed to create output directory: {$directoryPath}");
        }
    }

    private function removeDirectoryIfEmpty(string $directoryPath): void
    {
        if (is_dir($directoryPath)) {
            rmdir($directoryPath);
        }
    }

    // ──────────────────────────────────────────────
    //  FFmpeg execution
    // ──────────────────────────────────────────────

    /**
     * Run FFmpeg to extract frames using the "fps" video filter.
     *
     * All arguments are escaped to prevent shell injection.
     * Output filenames use zero-padded 5-digit numbering (frame_00001.jpg)
     * so they sort correctly in alphabetical order.
     */
    private function runFFmpegFrameExtraction(string $videoPath, int $samplingRate, string $outputDirectory): void
    {
        $safeInputPath = escapeshellarg($videoPath);
        $safeOutputPattern = escapeshellarg($outputDirectory . '/frame_%05d.jpg');
        // $samplingRate is typed int — cast directly; escapeshellarg would add quotes
        // that break the fps= filter syntax (fps='10' is invalid).
        $safeSamplingRate = (int) $samplingRate;

        // "2>&1" redirects FFmpeg's error messages so we can capture them if it fails
        $command = "ffmpeg -i {$safeInputPath} -vf fps={$safeSamplingRate} {$safeOutputPattern} 2>&1";
        exec($command, $commandOutput, $exitCode);

        if ($exitCode !== 0) {
            $errorDetails = implode("\n", $commandOutput);
            throw new \RuntimeException("FFmpeg frame extraction failed: {$errorDetails}");
        }
    }

    // ──────────────────────────────────────────────
    //  File collection
    // ──────────────────────────────────────────────

    /** Find all extracted frame files in the output directory, sorted by name. */
    private function collectExtractedFramePaths(string $outputDirectory): array
    {
        $framePaths = glob($outputDirectory . '/frame_*.jpg');

        if ($framePaths === false) {
            throw new \RuntimeException("Failed to read frame files from directory: {$outputDirectory}");
        }

        sort($framePaths);

        if (empty($framePaths)) {
            throw new \RuntimeException('No frames were extracted from the video');
        }

        return $framePaths;
    }

    /** Delete every frame_*.jpg file in the output directory. */
    private function deleteAllFrameFiles(string $outputDirectory): void
    {
        $frameFiles = glob($outputDirectory . '/frame_*.jpg');

        if ($frameFiles === false) {
            return;
        }

        foreach ($frameFiles as $filePath) {
            unlink($filePath);
        }
    }
}
