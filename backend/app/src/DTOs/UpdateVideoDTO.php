<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Validated input for updating video metadata (e.g. renaming the title).
 */
final class UpdateVideoDTO
{
    public function __construct(
        public readonly string $originalName,
    ) {}

    /**
     * Build from a decoded JSON body.
     *
     * @throws \InvalidArgumentException If the required field is missing or empty.
     */
    public static function fromArray(array $data): self
    {
        if (empty($data['originalName']) || !is_string($data['originalName'])) {
            throw new \InvalidArgumentException('originalName is required');
        }

        return new self(
            originalName: trim($data['originalName']),
        );
    }
}
