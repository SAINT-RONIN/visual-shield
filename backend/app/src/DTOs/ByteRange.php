<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class ByteRange
{
    public function __construct(
        public int $start,
        public int $end,
    ) {}
}
