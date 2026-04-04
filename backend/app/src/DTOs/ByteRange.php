<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class ByteRange
{
    /**
     * @param int $start
     * @param int $end
     * @return void
     */
    public function __construct(
        public int $start,
        public int $end,
    ) {}
}
