<?php

namespace App\Services;

class FrameExtractor
{
    public function extract(string $videoPath, int $samplingRate, string $outputDir): array
    {
        $this->ensureDirectory($outputDir);
        $this->runFFmpeg($videoPath, $samplingRate, $outputDir);

        return $this->collectFramePaths($outputDir);
    }

    public function cleanup(string $outputDir): void
    {
        $files = glob($outputDir . '/frame_*.jpg');
        foreach ($files as $file) {
            unlink($file);
        }

        if (is_dir($outputDir)) {
            rmdir($outputDir);
        }
    }

    private function ensureDirectory(string $dir): void
    {
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            throw new \RuntimeException("Failed to create output directory: {$dir}");
        }
    }

    private function runFFmpeg(string $videoPath, int $samplingRate, string $outputDir): void
    {
        $safeInput = escapeshellarg($videoPath);
        $safeOutput = escapeshellarg($outputDir . '/frame_%05d.jpg');
        $safeRate = escapeshellarg((string) $samplingRate);

        $cmd = "ffmpeg -i {$safeInput} -vf fps={$safeRate} {$safeOutput} 2>&1";
        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \RuntimeException('FFmpeg frame extraction failed: ' . implode("\n", $output));
        }
    }

    private function collectFramePaths(string $outputDir): array
    {
        $files = glob($outputDir . '/frame_*.jpg');
        sort($files);

        if (empty($files)) {
            throw new \RuntimeException('No frames were extracted from the video');
        }

        return $files;
    }
}
