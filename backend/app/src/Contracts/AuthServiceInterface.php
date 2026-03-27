<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\LoginDTO;
use App\DTOs\LoginResult;
use App\DTOs\RegisterDTO;
use App\DTOs\UpdateProfileDTO;
use App\Models\User;

interface AuthServiceInterface
{
    /**
     * Register a new user account.
     */
    public function register(RegisterDTO $dto): User;

    /**
     * Log a user in by verifying their credentials and issuing a token.
     */
    public function login(LoginDTO $dto): LoginResult;

    /** Log a user out by deleting their bearer token from the database. */
    public function logout(string $token): void;

    /**
     * Look up which user owns a given bearer token.
     */
    public function getUserFromToken(string $token): ?User;

    /** Get a user's profile by their ID. */
    public function getProfile(int $userId): User;

    /** Update a user's display name and return their updated profile. */
    public function updateProfile(int $userId, UpdateProfileDTO $dto): User;
}
