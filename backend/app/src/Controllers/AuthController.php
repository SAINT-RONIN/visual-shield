<?php

namespace App\Controllers;

use App\Framework\BaseController;
use App\Framework\AuthMiddleware;
use App\Services\AuthService;
use App\Repositories\UserRepository;
use App\Repositories\TokenRepository;
use App\DTOs\RegisterDTO;
use App\DTOs\LoginDTO;
use App\DTOs\UpdateProfileDTO;

class AuthController extends BaseController
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService(new UserRepository(), new TokenRepository());
    }

    public function register(): void
    {
        try {
            $dto = RegisterDTO::fromArray($this->getJsonBody());
            $user = $this->authService->register($dto);
            $this->jsonResponse($user, 201);
        } catch (\InvalidArgumentException $e) {
            $this->jsonResponse(['error' => ['code' => 400, 'message' => $e->getMessage()]], 400);
        } catch (\Throwable $e) {
            $this->jsonResponse(['error' => ['code' => 500, 'message' => 'Internal server error']], 500);
        }
    }

    public function login(): void
    {
        try {
            $dto = LoginDTO::fromArray($this->getJsonBody());
            $result = $this->authService->login($dto);
            $this->jsonResponse($result, 200);
        } catch (\InvalidArgumentException $e) {
            $this->jsonResponse(['error' => ['code' => 400, 'message' => $e->getMessage()]], 400);
        } catch (\RuntimeException $e) {
            $this->jsonResponse(['error' => ['code' => 401, 'message' => $e->getMessage()]], 401);
        } catch (\Throwable $e) {
            $this->jsonResponse(['error' => ['code' => 500, 'message' => 'Internal server error']], 500);
        }
    }

    public function logout(): void
    {
        try {
            $token = AuthMiddleware::extractToken();
            $this->authService->logout($token);
            $this->jsonResponse(['message' => 'Logged out successfully'], 200);
        } catch (\Throwable $e) {
            $this->jsonResponse(['error' => ['code' => 500, 'message' => 'Internal server error']], 500);
        }
    }

    public function getProfile(): void
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            $user = $this->authService->getProfile($userId);
            $this->jsonResponse($user, 200);
        } catch (\Throwable $e) {
            $this->jsonResponse(['error' => ['code' => 500, 'message' => 'Internal server error']], 500);
        }
    }

    public function updateProfile(): void
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            $dto = UpdateProfileDTO::fromArray($this->getJsonBody());
            $user = $this->authService->updateProfile($userId, $dto);
            $this->jsonResponse($user, 200);
        } catch (\InvalidArgumentException $e) {
            $this->jsonResponse(['error' => ['code' => 400, 'message' => $e->getMessage()]], 400);
        } catch (\Throwable $e) {
            $this->jsonResponse(['error' => ['code' => 500, 'message' => 'Internal server error']], 500);
        }
    }
}
