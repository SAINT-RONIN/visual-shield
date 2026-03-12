<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\AnalysisConfig;
use App\DTOs\LoginDTO;
use App\DTOs\LoginResult;
use App\DTOs\RegisterDTO;
use App\DTOs\UpdateProfileDTO;
use App\Exceptions\NotFoundException;
use App\Exceptions\UnauthorizedException;
use App\Exceptions\ValidationException;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Repositories\TokenRepository;

/**
 * Handles user authentication and profile management.
 *
 * This service is responsible for:
 *   - Registration: create a new account with a securely hashed password
 *   - Login: verify credentials and issue a bearer token
 *   - Logout: revoke a bearer token
 *   - Profile: retrieve and update user profile info
 *
 * Passwords are hashed with Argon2id (the strongest PHP algorithm).
 * Tokens are random 64-character hex strings that expire after 24 hours.
 */
class AuthService
{
    public function __construct(
        private UserRepository $userRepo,
        private TokenRepository $tokenRepo,
    ) {}

    // ──────────────────────────────────────────────
    //  Registration
    // ──────────────────────────────────────────────

    /**
     * Register a new user account.
     *
     * @throws \InvalidArgumentException If the username is already taken.
     */
    public function register(RegisterDTO $dto): User
    {
        $this->ensureUsernameIsAvailable($dto->username);

        $hashedPassword = password_hash($dto->password, PASSWORD_ARGON2ID);

        // First user becomes admin, all others are viewers
        $role = $this->userRepo->countAll() === 0 ? 'admin' : 'viewer';

        $newUserId = $this->userRepo->create($dto->username, $hashedPassword, $dto->displayName, $role);

        return $this->findUserOrFail($newUserId);
    }

    // ──────────────────────────────────────────────
    //  Login / Logout
    // ──────────────────────────────────────────────

    /**
     * Log a user in by verifying their credentials and issuing a token.
     *
     * @throws \RuntimeException If the username or password is wrong.
     */
    public function login(LoginDTO $dto): LoginResult
    {
        $user = $this->verifyCredentials($dto->username, $dto->password);
        $token = $this->createBearerToken($user->id);

        return new LoginResult($token, $user);
    }

    /** Log a user out by deleting their bearer token from the database. */
    public function logout(string $token): void
    {
        $this->tokenRepo->deleteByToken($token);
    }

    // ──────────────────────────────────────────────
    //  Token resolution
    // ──────────────────────────────────────────────

    /**
     * Look up which user owns a given bearer token.
     *
     * Returns null if the token is invalid or expired — the caller
     * (middleware) uses this to reject unauthenticated requests.
     */
    public function getUserFromToken(string $token): ?User
    {
        $tokenRecord = $this->tokenRepo->findValidToken($token);

        if (!$tokenRecord) {
            return null;
        }

        return $this->userRepo->findById($tokenRecord->userId);
    }

    // ──────────────────────────────────────────────
    //  Profile
    // ──────────────────────────────────────────────

    /** Get a user's profile by their ID. */
    public function getProfile(int $userId): User
    {
        return $this->findUserOrFail($userId);
    }

    /** Update a user's display name and return their updated profile. */
    public function updateProfile(int $userId, UpdateProfileDTO $dto): User
    {
        $this->userRepo->updateProfile($userId, $dto->displayName);

        return $this->findUserOrFail($userId);
    }

    // ──────────────────────────────────────────────
    //  Lookup helpers
    // ──────────────────────────────────────────────

    /** Find a user by ID, or throw if they don't exist. */
    private function findUserOrFail(int $userId): User
    {
        $user = $this->userRepo->findById($userId);

        if (!$user) {
            throw new NotFoundException('User not found');
        }

        return $user;
    }

    // ──────────────────────────────────────────────
    //  Validation helpers
    // ──────────────────────────────────────────────

    /** Throw an error if the username is already taken by another account. */
    private function ensureUsernameIsAvailable(string $username): void
    {
        if ($this->userRepo->findByUsername($username)) {
            throw new ValidationException('Username is already taken');
        }
    }

    /**
     * Check that the username exists and the password matches.
     *
     * Returns the User on success, throws on failure.
     */
    private function verifyCredentials(string $username, string $password): User
    {
        $user = $this->userRepo->findByUsername($username);

        $credentialsAreValid = $user && password_verify($password, $user->passwordHash);

        if (!$credentialsAreValid) {
            throw new UnauthorizedException('Invalid username or password');
        }

        return $user;
    }

    // ──────────────────────────────────────────────
    //  Token generation
    // ──────────────────────────────────────────────

    /**
     * Generate a new bearer token and save it to the database.
     *
     * The token is a cryptographically secure 64-character hex string
     * that expires 24 hours from now.
     */
    private function createBearerToken(int $userId): string
    {
        $token = bin2hex(random_bytes(AnalysisConfig::TOKEN_RANDOM_BYTES));
        $expiryHours = AnalysisConfig::TOKEN_EXPIRY_HOURS;
        $expiryTimestamp = strtotime("+{$expiryHours} hours");
        $expiresAt = date('Y-m-d H:i:s', $expiryTimestamp);

        $this->tokenRepo->store($userId, $token, $expiresAt);

        return $token;
    }
}
