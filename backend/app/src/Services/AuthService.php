<?php

namespace App\Services;

use App\DTOs\RegisterDTO;
use App\DTOs\LoginDTO;
use App\DTOs\UpdateProfileDTO;
use App\Repositories\UserRepository;
use App\Repositories\TokenRepository;

class AuthService
{
    public function __construct(
        private UserRepository $userRepo,
        private TokenRepository $tokenRepo
    ) {}

    public function register(RegisterDTO $dto): array
    {
        $this->ensureUsernameAvailable($dto->username);
        $passwordHash = password_hash($dto->password, PASSWORD_ARGON2ID);
        $userId = $this->userRepo->create($dto->username, $passwordHash, $dto->displayName);
        return $this->formatUser($this->userRepo->findById($userId));
    }

    public function login(LoginDTO $dto): array
    {
        $user = $this->userRepo->findByUsername($dto->username);
        if (!$user || !password_verify($dto->password, $user['password_hash'])) {
            throw new \RuntimeException('Invalid username or password');
        }

        $token = $this->generateToken();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
        $this->tokenRepo->store($user['id'], $token, $expiresAt);

        return ['token' => $token, 'user' => $this->formatUser($user)];
    }

    public function logout(string $token): void
    {
        $this->tokenRepo->deleteByToken($token);
    }

    public function getUserFromToken(string $token): ?array
    {
        $tokenRecord = $this->tokenRepo->findValidToken($token);
        if (!$tokenRecord) {
            return null;
        }
        return $this->userRepo->findById($tokenRecord['user_id']);
    }

    public function getProfile(int $userId): array
    {
        $user = $this->userRepo->findById($userId);
        if (!$user) {
            throw new \RuntimeException('User not found');
        }
        return $this->formatUser($user);
    }

    public function updateProfile(int $userId, UpdateProfileDTO $dto): array
    {
        $this->userRepo->updateProfile($userId, $dto->displayName);
        return $this->formatUser($this->userRepo->findById($userId));
    }

    private function ensureUsernameAvailable(string $username): void
    {
        if ($this->userRepo->findByUsername($username)) {
            throw new \InvalidArgumentException('Username is already taken');
        }
    }

    private function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    private function formatUser(array $user): array
    {
        return [
            'id' => $user['id'],
            'username' => $user['username'],
            'displayName' => $user['display_name'],
            'createdAt' => $user['created_at'],
            'updatedAt' => $user['updated_at'],
        ];
    }
}
