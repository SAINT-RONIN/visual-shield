<?php

declare(strict_types=1);

namespace App\Utils;

use App\Config\AnalysisConfig;
use App\Exceptions\NotFoundException;

class PathResolver
{
    /** Convert a relative stored_path to an absolute filesystem path. */
    /**
     * @param string $storedPath
     * @return string
     */
    public static function resolve(string $storedPath): string
    {
        return AnalysisConfig::appRoot() . '/' . $storedPath;
    }

    /** Resolve and verify the file exists, or throw NotFoundException. */
    /**
     * @param string $storedPath
     * @return string
     */
    public static function resolveOrFail(string $storedPath): string
    {
        $fullPath = self::resolve($storedPath);

        if (!file_exists($fullPath)) {
            throw new NotFoundException('File not found on disk');
        }

        return $fullPath;
    }
}
