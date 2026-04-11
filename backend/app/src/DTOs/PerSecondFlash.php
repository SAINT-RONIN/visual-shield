<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Immutable value object holding the flash frequency for a single second.
 *
 * This is an output DTO produced by FlashDetector. It replaces the raw
 * associative array ['second' => int, 'frequency' => float] that was
 * previously used internally.
 */
class PerSecondFlash
{
    public function __construct(
        public readonly int $second,
        public readonly float $frequency,
    ) {}
}
