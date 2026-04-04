<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\User;

/**
 * Typed result from a successful login.
 *
 * Carries the signed JWT access token plus the authenticated user payload the
 * frontend needs immediately after login.
 */
class LoginResult
{
    public function __construct(
        public readonly string $token,
        public readonly User $user,
    ) {}
}
