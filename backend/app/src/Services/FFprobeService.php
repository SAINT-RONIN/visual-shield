<?php

namespace App\Services;

class FFprobeService
{
    public function getDuration(string $filePath): float
    {
        $safe = escapeshellarg($filePath);
        $cmd = "ffprobe -v error -show_entries format=duration -of csv=p=0 {$safe}";
        $output = shell_exec($cmd);

        if ($output === null || trim($output) === '') {
            throw new \RuntimeException('Failed to read video duration');
        }

        return (float) trim($output);
    }

    public function getResolution(string $filePath): array
    {
        $safe = escapeshellarg($filePath);
        $cmd = "ffprobe -v error -select_streams v:0 -show_entries stream=width,height -of csv=p=0:s=x {$safe}";
        $output = shell_exec($cmd);

        if ($output === null || trim($output) === '') {
            throw new \RuntimeException('Failed to read video resolution');
        }

        $parts = explode('x', trim($output));

        return ['width' => (int) $parts[0], 'height' => (int) $parts[1]];
    }
}
