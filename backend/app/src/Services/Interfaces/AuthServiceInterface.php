<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\DTOs\ChangePasswordDTO;
use App\DTOs\LoginDTO;
use App\DTOs\LoginResult;
use App\DTOs\RegisterDTO;
use App\DTOs\UpdateProfileDTO;
use App\Models\User;

interface AuthServiceInterface
{
    /**
     * Register a new user account.
     *
     * @param RegisterDTO $dto Validated registration payload.
     * @return User Newly created user model.
     */
    public function register(RegisterDTO $dto): User;

    /**
     * Log a user in by verifying their credentials and issuing a JWT.
     *
     * @param LoginDTO $dto Login credentials.
     * @return LoginResult Authenticated user plus issued JWT.
     */
    public function login(LoginDTO $dto): LoginResult;

    /**
     * Log a user out by revoking the current JWT session.
     *
     * @param string $token Raw JWT access token to revoke.
     * @return void
     */
    public function logout(string $token): void;

    /**
     * Look up which user owns a given JWT access token.
     *
     * @param string $token Raw JWT access token.
     * @return User|null Authenticated user model, or null if the token is invalid.
     */
    public function getUserFromToken(string $token): ?User;

    /**
     * Get a user's profile by their ID.
     *
     * @param int $userId User ID to load.
     * @return User Loaded user profile.
     */
    public function getProfile(int $userId): User;

    /**
     * Update a user's display name and return their updated profile.
     *
     * @param int $userId User ID to update.
     * @param UpdateProfileDTO $dto Validated profile update payload.
     * @return User Refreshed user profile after saving.
     */
    public function updateProfile(int $userId, UpdateProfileDTO $dto): User;

    /**
     * Change an authenticated user's own password after verifying their current one.
     *
     * @param int $userId Authenticated user ID.
     * @param ChangePasswordDTO $dto Validated password change payload.
     * @return void
     */
    public function changePassword(int $userId, ChangePasswordDTO $dto): void;
}
