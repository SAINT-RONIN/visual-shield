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
    /**
     * @param string $token
     * @param User $user
     * @return void
     */
    public function __construct(
        public readonly string $token,
        public readonly User $user,
    ) {}
}
