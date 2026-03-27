<?php

declare(strict_types=1);

namespace App\Utils;

class FileSystem
{
    /** Create a directory if it does not already exist. */
    public static function ensureDirectoryExists(string $path): void
    {
        if (is_dir($path)) {
            return;
        }

        $created = mkdir($path, 0755, true);

        if (!$created && !is_dir($path)) {
            throw new \RuntimeException('Failed to create directory: ' . $path);
        }
    }

    /** Throw if a directory is not writable. */
    public static function ensureWritable(string $path): void
    {
        if (!is_writable($path)) {
            throw new \RuntimeException('Directory is not writable: ' . $path);
        }
    }
}
