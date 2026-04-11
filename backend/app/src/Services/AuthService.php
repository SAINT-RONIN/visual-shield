<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Interfaces\TokenRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\Interfaces\AuthServiceInterface;
use App\DTOs\ChangePasswordDTO;
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
    public function __construct(
        private UserRepositoryInterface $userRepo,
        private TokenRepositoryInterface $tokenRepo,
        private JwtService $jwtService,
    ) {}

    //  Registration

    // Checks the username is free then hashes the password before persisting.
    public function register(RegisterDTO $dto): User
    {
        $this->ensureUsernameIsAvailable($dto->username);

        $hashedPassword = password_hash($dto->password, PASSWORD_ARGON2ID);

        // First user becomes admin, all others are members
        $role = $this->userRepo->countAll() === 0 ? 'admin' : 'member';

        $newUserId = $this->userRepo->create($dto->username, $hashedPassword, $dto->displayName, $role);

        return $this->findUserOrFail($newUserId);
    }

    //  Login / Logout

    // Verifies credentials and issues a bearer token for subsequent requests.
    public function login(LoginDTO $dto): LoginResult
    {
        $user = $this->verifyCredentials($dto->username, $dto->password);
        $token = $this->createJwtAccessToken($user->id);

        return new LoginResult($token, $user);
    }

    // Removes the token server-side so it becomes useless immediately.
    public function logout(string $token): void
    {
        $payload = $this->jwtService->decodeAccessToken($token);
        $this->tokenRepo->deleteByToken($payload['jti']);
    }

    //  Token resolution

    // Returns null if the token is invalid or expired; middleware uses this to reject requests.
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

    // Fetches the current profile so the controller stays free of repository lookups.
    public function getProfile(int $userId): User
    {
        return $this->findUserOrFail($userId);
    }

    // Saves editable profile fields and returns the refreshed user model.
    public function updateProfile(int $userId, UpdateProfileDTO $dto): User
    {
        $this->userRepo->updateProfile($userId, $dto->displayName);

        return $this->findUserOrFail($userId);
    }

    // Verifies the current password before replacing it with the new one.
    public function changePassword(int $userId, ChangePasswordDTO $dto): void
    {
        $user = $this->findUserOrFail($userId);

        if (!password_verify($dto->currentPassword, $user->passwordHash)) {
            throw new ValidationException('Current password is incorrect');
        }

        $hashedPassword = password_hash($dto->newPassword, PASSWORD_ARGON2ID);
        $this->userRepo->updatePassword($userId, $hashedPassword);
    }

    //  Lookup helpers

    // Shared internal helper — avoids repeating the null check across auth methods.
    private function findUserOrFail(int $userId): User
    {
        return $this->findOrFail($this->userRepo->findById($userId), 'User not found');
    }

    //  Validation helpers

    // Protects registration from duplicate usernames.
    private function ensureUsernameIsAvailable(string $username): void
    {
        if ($this->userRepo->findByUsername($username)) {
            throw new ValidationException('Username is already taken');
        }
    }

    // Succeeds only when the username exists and the password matches the stored hash.
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

    // Issues the JWT and persists the expiry so it can be revoked if leaked.
    private function createJwtAccessToken(int $userId): string
    {
        $accessToken = $this->jwtService->issueAccessToken($userId);
        $this->tokenRepo->deleteExpiredTokens();
        $this->tokenRepo->store($userId, $accessToken['jti'], $accessToken['expiresAt']);

        return $accessToken['token'];
    }
}
