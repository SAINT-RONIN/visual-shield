<?php

declare(strict_types=1);

namespace App\DTOs;

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
    /**
     * @param string      $username    Trimmed, non-empty username.
     * @param string      $password    Raw password (never trimmed — whitespace may be intentional).
     * @param string|null $displayName Optional display name, trimmed if provided.
     */
    public function __construct(
        public readonly string $username,
        public readonly string $password,
        public readonly ?string $displayName = null
    ) {}

    /**
     * Build a RegisterDTO from a raw associative array (decoded JSON body).
     *
     * Validates that 'username' and 'password' keys exist as non-empty
     * strings, trims the username and optional displayName, and returns
     * an immutable instance ready for the service layer.
     *
     * @param array $data Raw request payload (e.g. from json_decode).
     * @return self       Validated, immutable DTO.
     *
     * @throws \InvalidArgumentException If username or password is missing/invalid.
     */
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
