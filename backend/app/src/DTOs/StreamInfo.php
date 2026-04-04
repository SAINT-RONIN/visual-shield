<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Immutable value object containing the metadata needed to stream a video.
 *
 * Returned by VideoService::getStreamInfo() so the controller can send
 * the correct HTTP headers and file content without the service touching
 * any HTTP globals.
 */
class StreamInfo
{
    /**
     * @param string $filePath
     * @param int $fileSize
     * @param string $contentType
     * @return void
     */
    public function __construct(
        public readonly string $filePath,
        public readonly int $fileSize,
        public readonly string $contentType,
    ) {}
}
