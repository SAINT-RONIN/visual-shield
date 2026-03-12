<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class FrameFlashTags
{
    public function __construct(
        /** @var array<int, int> */
        public array $tags,
    ) {}

    public function isFlash(int $frameIndex): bool
    {
        return ($this->tags[$frameIndex] ?? 0) === 1;
    }

    public function count(): int
    {
        return array_sum($this->tags);
    }
}
