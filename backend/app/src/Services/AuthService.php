<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Interfaces\TokenRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\Interfaces\AuthServiceInterface;
use App\DTOs\LoginDTO;
use App\DTOs\LoginResult;
use App\DTOs\RegisterDTO;
use App\DTOs\UpdateProfileDTO;
use App\Exceptions\UnauthorizedException;
use App\Exceptions\ValidationException;
use App\Models\User;
use App\Utils\JwtService;

/**
 * Handles sign-up, login, logout, and profile updates.
 *
 * This service keeps the auth rules in one place so controllers only have to
 * pass data in and send responses back out.
 */
class AuthService extends BaseService implements AuthServiceInterface
{
    /**
     * Create the service with its repository and JWT dependencies.
     *
     * @param UserRepositoryInterface $userRepo User repository for auth lookups and profile updates.
     * @param TokenRepositoryInterface $tokenRepo Repository used for JWT session revocation.
     * @param JwtService $jwtService JWT issuer and decoder utility.
     * @return void
     */
    public function __construct(
        private UserRepositoryInterface $userRepo,
        private TokenRepositoryInterface $tokenRepo,
        private JwtService $jwtService,
    ) {}

    //  Registration

    /**
     * This creates a new account after checking the username is free and
     * hashing the password so we never store sensitive credentials in plain
     * text anywhere in the database.
     *
     * @param RegisterDTO $dto Validated registration payload.
     * @return User Newly created user model.
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

    //  Login / Logout

    /**
     * This verifies the submitted credentials and, if they are correct,
     * creates the bearer token the frontend will attach to later requests
     * instead of sending the password every time.
     *
     * @param LoginDTO $dto Validated login credentials.
     * @return LoginResult Authenticated user plus issued JWT.
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
     *
     * @param string $token Raw JWT access token to revoke.
     * @return void
     */
    public function logout(string $token): void
    {
        $payload = $this->jwtService->decodeAccessToken($token);
        $this->tokenRepo->deleteByToken($payload['jti']);
    }

    //  Token resolution

    /**
     * Look up which user owns a given bearer token.
     *
     * Returns null if the token is invalid or expired â€” the caller
     * (middleware) uses this to reject unauthenticated requests.
     *
     * @param string $token Raw JWT access token.
     * @return User|null Authenticated user model, or null if the token is invalid.
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

        $user = $this->userRepo->findById($userId);

        if (!$user || !$user->isActive) {
            return null;
        }

        return $user;
    }

    //  Profile

    /**
     * This fetches the user's current profile data in one predictable place so
     * the controller does not need to know anything about repository lookups.
     *
     * @param int $userId User ID to load.
     * @return User Loaded user profile.
     */
    public function getProfile(int $userId): User
    {
        return $this->findUserOrFail($userId);
    }

    /**
     * This saves the editable profile fields and then returns the refreshed
     * user model, which is handy because the frontend can immediately work
     * with the new saved version.
     *
     * @param int $userId User ID to update.
     * @param UpdateProfileDTO $dto Validated profile update payload.
     * @return User Refreshed user profile after saving.
     */
    public function updateProfile(int $userId, UpdateProfileDTO $dto): User
    {
        $this->userRepo->updateProfile($userId, $dto->displayName);

        return $this->findUserOrFail($userId);
    }

    //  Lookup helpers

    /**
     * This is the shared internal helper for "find the user or stop here"
     * so the public auth methods do not all repeat the same null check.
     *
     * @param int $userId User ID to load.
     * @return User Loaded user model.
     */
    private function findUserOrFail(int $userId): User
    {
        return $this->findOrFail($this->userRepo->findById($userId), 'User not found');
    }

    //  Validation helpers

    /**
     * This protects registration from duplicate usernames, because the login
     * flow only works cleanly when one username points to one account.
     *
     * @param string $username Username to validate for uniqueness.
     * @return void
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
     *
     * @param string $username Username to authenticate.
     * @param string $password Plain-text password to verify.
     * @return User Authenticated user model.
     */
    private function verifyCredentials(string $username, string $password): User
    {
        $user = $this->userRepo->findByUsername($username);

        $credentialsAreValid = $user && password_verify($password, $user->passwordHash);

        if (!$credentialsAreValid) {
            throw new UnauthorizedException('Invalid username or password');
        }

        if (!$user->isActive) {
            throw new UnauthorizedException('This account has been deactivated');
        }

        return $user;
    }

    //  Token generation

    /**
     * This creates the random bearer token a logged-in user will carry on
     * future requests, and it also stores the expiry so the token is not valid
     * forever if it ever gets copied or leaked.
     *
     * @param int $userId User ID the access token belongs to.
     * @return string Signed JWT access token.
     */
    private function createJwtAccessToken(int $userId): string
    {
        $accessToken = $this->jwtService->issueAccessToken($userId);
        $this->tokenRepo->deleteExpiredTokens();
        $this->tokenRepo->store($userId, $accessToken['jti'], $accessToken['expiresAt']);

        return $accessToken['token'];
    }
}
