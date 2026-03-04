<?php

namespace App\DTOs;

class UpdateProfileDTO
{
    public function __construct(
        public readonly ?string $displayName
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            displayName: isset($data['displayName']) ? trim($data['displayName']) : null
        );
    }
}
