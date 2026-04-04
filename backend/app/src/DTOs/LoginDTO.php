<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Exceptions\ValidationException;

/**
 * Immutable value object representing a validated login request.
 *
 * Purpose: Encapsulates and validates the POST /auth/login payload,
 * ensuring that username and password are present and correctly typed
 * before credentials reach the authentication layer.
 *
 * Why do I need it: Separating input validation from authentication logic
 * keeps AuthController thin and AuthService focused on credential
 * verification. The DTO guarantees that by the time AuthService receives
 * it, both fields are non-empty strings with the username already trimmed,
 * eliminating redundant defensive checks deeper in the call stack.
 */
class LoginDTO
{
    /**
     * @param string $username
     * @param string $password
     * @return void
     */
    public function __construct(
        public readonly string $username,
        public readonly string $password
    ) {}

    /**
     * Build a LoginDTO from a raw associative array (decoded JSON body).
     *
     * Validates that 'username' and 'password' keys exist as non-empty
     * strings, trims the username, and returns an immutable instance.
     *
     * @param array $data Raw request payload (e.g. from json_decode).
     * @return self       Validated, immutable DTO.
     *
     * @throws ValidationException If username or password is missing/invalid.
     */
    public static function fromArray(array $data): self
    {
        if (empty($data['username']) || !is_string($data['username'])) {
            throw new ValidationException('Username is required');
        }

        if (empty($data['password']) || !is_string($data['password'])) {
            throw new ValidationException('Password is required');
        }

        return new self(
            username: trim($data['username']),
            password: $data['password']
        );
    }
}
