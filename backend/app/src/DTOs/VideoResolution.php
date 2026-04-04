<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Immutable value object representing the pixel dimensions of a video stream.
 *
 * Replaces the raw associative array that FFprobeService::getResolution()
 * previously returned, so callers get typed access instead of array keys.
 */
final readonly class VideoResolution
{
    /**
     * @param int $width
     * @param int $height
     * @return void
     */
    public function __construct(
        public int $width,
        public int $height,
    ) {}
}
