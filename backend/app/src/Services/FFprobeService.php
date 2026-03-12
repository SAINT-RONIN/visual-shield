<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\VideoResolution;

/**
 * Reads video metadata using the FFprobe command-line tool.
 *
 * FFprobe is part of the FFmpeg suite. It can inspect video files and
 * report things like duration, resolution, codec, etc. This service
 * wraps the shell commands so the rest of the app doesn't need to know
 * about FFprobe's command-line flags.
 */
class FFprobeService
{
    /**
     * Get the total duration of a video file in seconds.
     *
     * Example: a 2-minute video returns 120.0
     */
    public function getDuration(string $filePath): float
    {
        $command = "ffprobe -v error -show_entries format=duration -of csv=p=0";
        $rawOutput = $this->runCommand($command, $filePath, 'video duration');

        return (float) trim($rawOutput);
    }

    /**
     * Get the width and height of the video in pixels.
     *
     * Example: a 1080p video returns VideoResolution(width: 1920, height: 1080)
     */
    public function getResolution(string $filePath): VideoResolution
    {
        $command = "ffprobe -v error -select_streams v:0 -show_entries stream=width,height -of csv=p=0:s=x";
        $rawOutput = $this->runCommand($command, $filePath, 'video resolution');

        return $this->parseResolutionOutput($rawOutput);
    }

    // ──────────────────────────────────────────────
    //  Helpers
    // ──────────────────────────────────────────────

    /**
     * Run an FFprobe command and return its output.
     *
     * The file path is escaped to prevent shell injection attacks.
     */
    private function runCommand(string $baseCommand, string $filePath, string $metadataDescription): string
    {
        $safeFilePath = escapeshellarg($filePath);
        $fullCommand = "{$baseCommand} {$safeFilePath}";
        $output = shell_exec($fullCommand);

        if ($output === null || trim($output) === '') {
            throw new \RuntimeException("Failed to read {$metadataDescription}");
        }

        return $output;
    }

    /**
     * Parse FFprobe's resolution output (e.g. "1920x1080") into a VideoResolution DTO.
     */
    private function parseResolutionOutput(string $rawOutput): VideoResolution
    {
        $parts = explode('x', trim($rawOutput));

        return new VideoResolution(
            width: (int) $parts[0],
            height: (int) $parts[1],
        );
    }
}
