<?php

declare(strict_types=1);

namespace App\Utils;

use App\DTOs\VideoResolution;

/**
 * Reads video metadata using the FFprobe command-line tool.
 *
 * FFprobe is part of the FFmpeg suite. It can inspect video files and
 * report things like duration, resolution, codec, etc. This utility
 * wraps the shell commands so the rest of the app doesn't need to know
 * about FFprobe's command-line flags.
 */
class FFprobe
{
    /**
     * This asks FFprobe for the video's total duration so the app knows how long the upload really is.
     * We need that number to validate uploads, cap unsafe sampling rates, and make the report timeline line up with the actual video length.
     */
    public function getDuration(string $filePath): float
    {
        $command = "ffprobe -v error -show_entries format=duration -of csv=p=0";
        $rawOutput = $this->runCommand($command, $filePath, 'video duration');

        return (float) trim($rawOutput);
    }

    /**
     * This asks FFprobe for the video's width and height so we know the real size of the source footage.
     * We need it whenever the app has to make decisions that depend on the video's dimensions instead of guessing from the filename or extension.
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
     * This is the shared helper that runs FFprobe safely and gives the calling method back the raw result.
     * We need one place for this so every FFprobe call uses the same escaping, error handling, and shell behavior instead of duplicating that logic everywhere.
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
     * This turns FFprobe's raw resolution text into a proper DTO the rest of the app can trust and use.
     * We need it because passing around loose strings like "1920x1080" makes the code harder to validate and easier to misuse later.
     */
    private function parseResolutionOutput(string $rawOutput): VideoResolution
    {
        $parts = explode('x', trim($rawOutput));

        if (count($parts) !== 2) {
            throw new \RuntimeException(
                "Unexpected FFprobe resolution output format: expected 'WxH', got '{$rawOutput}'"
            );
        }

        return new VideoResolution(
            width: (int) $parts[0],
            height: (int) $parts[1],
        );
    }
}
