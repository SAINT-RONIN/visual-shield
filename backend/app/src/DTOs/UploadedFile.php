<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Typed value object wrapping a single PHP file upload entry.
 *
 * Replaces the raw $_FILES array that was previously carried inside
 * UploadVideoDTO, so that every layer accesses file metadata through
 * typed properties instead of string-keyed array access.
 */
final readonly class UploadedFile
{
    /**
     * @param string $tmpName
     * @param string $name
     * @param int $size
     * @param string $type
     * @param int $error
     * @return void
     */
    public function __construct(
        public string $tmpName,
        public string $name,
        public int $size,
        public string $type,
        public int $error,
    ) {}

    /**
     * Build an UploadedFile from a raw $_FILES entry array.
     *
     * @param array $file A single entry from $_FILES (e.g. $_FILES['video']).
     */
    /**
     * @param array $file
     * @return self
     */
    public static function fromFilesArray(array $file): self
    {
        return new self(
            tmpName: $file['tmp_name'],
            name: $file['name'],
            size: (int) $file['size'],
            type: $file['type'],
            error: (int) $file['error'],
        );
    }
}
