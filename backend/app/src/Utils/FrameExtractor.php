<?php

declare(strict_types=1);

namespace App\Utils;

/**
 * Extracts individual JPEG frames from a video using FFmpeg.
 *
 * When we analyze a video, we can't look at the video file directly â€”
 * we need individual image files (frames) that we can compare to each other.
 * This utility uses FFmpeg to take "screenshots" of the video at regular
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
     * This is the main step that turns a video into a stack of image frames we can actually inspect one by one.
     * We need it because the flash and motion checks work by comparing still frames, not by reading the raw video file directly.
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
        FileSystem::ensureDirectoryExists($outputDirectory);
        $this->runFFmpegFrameExtraction($videoPath, $samplingRate, $outputDirectory);

        return $this->collectExtractedFramePaths($outputDirectory);
    }

    /**
     * This clears out the temporary frame images after analysis is done so the server does not slowly fill up with leftovers.
     * We need it because frame extraction can create a lot of files very quickly, especially on longer videos.
     */
    public function cleanup(string $outputDirectory): void
    {
        $this->deleteAllFrameFiles($outputDirectory);
        $this->removeDirectoryIfEmpty($outputDirectory);
    }

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    //  Directory management
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * This removes the now-empty frame folder once all of the JPEGs are gone.
     * We need it to keep the storage area tidy instead of leaving behind hundreds of empty temp directories over time.
     */
    private function removeDirectoryIfEmpty(string $directoryPath): void
    {
        if (is_dir($directoryPath)) {
            rmdir($directoryPath);
        }
    }

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    //  FFmpeg execution
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * This is the place where we actually call FFmpeg and ask it to grab frames at a fixed rate from the video.
     * We need it because FFmpeg is the tool doing the heavy lifting here, while the PHP side just prepares a safe command and handles the result.
     */
    private function runFFmpegFrameExtraction(string $videoPath, int $samplingRate, string $outputDirectory): void
    {
        $safeInputPath = escapeshellarg($videoPath);
        $safeOutputPattern = escapeshellarg($outputDirectory . '/frame_%05d.jpg');
        // $samplingRate is typed int â€” cast directly; escapeshellarg would add quotes
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

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    //  File collection
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * This gathers the frame files FFmpeg created and puts them in the exact order they should be analyzed.
     * We need that ordering because comparing the wrong frame sequence would give us meaningless flash and motion results.
     */
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

    /**
     * This deletes every extracted frame image from the temp folder once we no longer need them.
     * We need it because those files are only useful during analysis and would just waste disk space afterward.
     */
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
