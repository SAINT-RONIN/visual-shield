<?php

namespace App\DTOs;

class RegisterDTO
{
    public function __construct(
        public readonly string $username,
        public readonly string $password,
        public readonly ?string $displayName = null
    ) {}

    public static function fromArray(array $data): self
    {
        if (empty($data['username']) || !is_string($data['username'])) {
            throw new \InvalidArgumentException('Username is required');
        }

        if (empty($data['password']) || !is_string($data['password'])) {
            throw new \InvalidArgumentException('Password is required');
        }

        return new self(
            username: trim($data['username']),
            password: $data['password'],
            displayName: isset($data['displayName']) ? trim($data['displayName']) : null
        );
    }
}
