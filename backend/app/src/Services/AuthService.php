<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\AuthServiceInterface;
use App\DTOs\LoginDTO;
use App\DTOs\LoginResult;
use App\DTOs\RegisterDTO;
use App\DTOs\UpdateProfileDTO;
use App\Exceptions\UnauthorizedException;
use App\Exceptions\ValidationException;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Repositories\TokenRepository;
use App\Utils\JwtService;

/**
 * Handles sign-up, login, logout, and profile updates.
 *
 * This service keeps the auth rules in one place so controllers only have to
 * pass data in and send responses back out.
 */
class AuthService extends BaseService implements AuthServiceInterface
{
    public function __construct(
        private UserRepository $userRepo,
        private TokenRepository $tokenRepo,
        private JwtService $jwtService,
    ) {}

    // ──────────────────────────────────────────────
    //  Registration
    // ──────────────────────────────────────────────

    /**
     * This creates a new account after checking the username is free and
     * hashing the password so we never store sensitive credentials in plain
     * text anywhere in the database.
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
     * This verifies the submitted credentials and, if they are correct,
     * creates the bearer token the frontend will attach to later requests
     * instead of sending the password every time.
     *
     * @throws \RuntimeException If the username or password is wrong.
     */
    public function login(LoginDTO $dto): LoginResult
    {
        $user = $this->verifyCredentials($dto->username, $dto->password);
        $token = $this->createJwtAccessToken($user->id);

        return new LoginResult($token, $user);
    }

    /**
     * This logs the user out by removing the token they were using, which
     * makes that token useless immediately instead of only disappearing from
     * the browser that happened to store it.
     */
    public function logout(string $token): void
    {
        $payload = $this->jwtService->decodeAccessToken($token);
        $this->tokenRepo->deleteByToken($payload['jti']);
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
        try {
            $payload = $this->jwtService->decodeAccessToken($token);
        } catch (\RuntimeException) {
            return null;
        }

        $userId = (int) $payload['sub'];
        $tokenRecord = $this->tokenRepo->findValidToken($payload['jti']);

        if (!$tokenRecord || $tokenRecord->userId !== $userId) {
            return null;
        }

        return $this->userRepo->findById($userId);
    }

    // ──────────────────────────────────────────────
    //  Profile
    // ──────────────────────────────────────────────

    /**
     * This fetches the user's current profile data in one predictable place so
     * the controller does not need to know anything about repository lookups.
     */
    public function getProfile(int $userId): User
    {
        return $this->findUserOrFail($userId);
    }

    /**
     * This saves the editable profile fields and then returns the refreshed
     * user model, which is handy because the frontend can immediately work
     * with the new saved version.
     */
    public function updateProfile(int $userId, UpdateProfileDTO $dto): User
    {
        $this->userRepo->updateProfile($userId, $dto->displayName);

        return $this->findUserOrFail($userId);
    }

    // ──────────────────────────────────────────────
    //  Lookup helpers
    // ──────────────────────────────────────────────

    /**
     * This is the shared internal helper for "find the user or stop here"
     * so the public auth methods do not all repeat the same null check.
     */
    private function findUserOrFail(int $userId): User
    {
        return $this->findOrFail($this->userRepo->findById($userId), 'User not found');
    }

    // ──────────────────────────────────────────────
    //  Validation helpers
    // ──────────────────────────────────────────────

    /**
     * This protects registration from duplicate usernames, because the login
     * flow only works cleanly when one username points to one account.
     */
    private function ensureUsernameIsAvailable(string $username): void
    {
        if ($this->userRepo->findByUsername($username)) {
            throw new ValidationException('Username is already taken');
        }
    }

    /**
     * This is the real credential check behind login and it only succeeds
     * when both the username exists and the submitted password matches the
     * stored hash for that user.
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
     * This creates the random bearer token a logged-in user will carry on
     * future requests, and it also stores the expiry so the token is not valid
     * forever if it ever gets copied or leaked.
     */
    private function createJwtAccessToken(int $userId): string
    {
        $accessToken = $this->jwtService->issueAccessToken($userId);
        $this->tokenRepo->deleteExpiredTokens();
        $this->tokenRepo->store($userId, $accessToken['jti'], $accessToken['expiresAt']);

        return $accessToken['token'];
    }
}
