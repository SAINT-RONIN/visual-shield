<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Exceptions\ValidationException;

/**
 * Immutable value object representing a validated user registration request.
 *
 * Purpose: Encapsulates and validates the POST /auth/register payload,
 * ensuring that username and password are present and correctly typed
 * before the data reaches AuthController or UserRepository.
 *
 * Why do I need it: Without this DTO the controller would contain inline
 * validation logic, mixing HTTP concerns with business rules. By
 * centralising validation in fromArray(), the controller stays thin,
 * error messages are consistent, and every consumer is guaranteed to
 * receive trimmed, non-empty credentials via readonly promoted properties.
 */
class RegisterDTO
{
    public function __construct(
        public readonly string $username,
        public readonly string $password,
        public readonly ?string $displayName = null
    ) {}

    // Validates 'username' and 'password' as non-empty strings, trims the username
    // and optional displayName. Throws ValidationException if invalid.
    public static function fromArray(array $data): self
    {
        if (empty($data['username']) || !is_string($data['username'])) {
            throw new ValidationException('Username is required');
        }

        if (empty($data['password']) || !is_string($data['password'])) {
            throw new ValidationException('Password is required');
        }

        $displayName = isset($data['displayName']) ? trim($data['displayName']) : null;

        return new self(
            username: trim($data['username']),
            password: $data['password'],
            displayName: $displayName,
        );
    }
}
