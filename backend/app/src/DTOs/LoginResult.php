<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\User;

/**
 * Typed result from a successful login — the bearer token and the
 * authenticated user.  Replaces the raw ['token' => …, 'user' => …]
 * array that previously leaked across the service→controller boundary.
 */
class LoginResult
{
    public function __construct(
        public readonly string $token,
        public readonly User $user,
    ) {}
}
