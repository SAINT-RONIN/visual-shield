<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Contracts\AuthServiceInterface;
use App\Framework\BaseController;
use App\Framework\AuthMiddleware;
use App\Framework\ServiceRegistry;
use App\DTOs\RegisterDTO;
use App\DTOs\LoginDTO;
use App\DTOs\UpdateProfileDTO;

/**
 * HTTP layer for authentication endpoints (register, login, logout, profile).
 *
 * Accepts HTTP requests, parses them into typed DTOs, delegates business
 * logic to AuthService, and lets BaseController::handleRequest() map
 * exceptions to HTTP status codes.
 */
class AuthController extends BaseController
{
    private AuthServiceInterface $authService;

    public function __construct()
    {
        $this->authService = ServiceRegistry::authService();
    }

    /** Register a new user account from a JSON request body. */
    public function register(): void
    {
        $this->handleRequest(function () {
            $dto = RegisterDTO::fromArray($this->getJsonBody());
            $user = $this->authService->register($dto);
            $this->jsonResponse(['data' => $user->toApiArray()], 201);
        });
    }

    /** Authenticate a user and return a bearer token. */
    public function login(): void
    {
        $this->handleRequest(function () {
            $dto = LoginDTO::fromArray($this->getJsonBody());
            $result = $this->authService->login($dto);
            $this->jsonResponse([
                'token' => $result->token,
                'user' => $result->user->toApiArray(),
            ], 200);
        });
    }

    /** Revoke the current bearer token, logging the user out. */
    public function logout(): void
    {
        $this->handleRequest(function () {
            $token = AuthMiddleware::extractToken();
            $this->authService->logout($token);
            $this->jsonResponse(['message' => 'Logged out successfully'], 200);
        });
    }

    /** Return the authenticated user's profile. */
    public function getProfile(): void
    {
        $this->handleRequest(function () {
            $userId = $this->getAuthenticatedUserId();
            $user = $this->authService->getProfile($userId);
            $this->jsonResponse(['data' => $user->toApiArray()], 200);
        });
    }

    /** Update the authenticated user's display name. */
    public function updateProfile(): void
    {
        $this->handleRequest(function () {
            $userId = $this->getAuthenticatedUserId();
            $dto = UpdateProfileDTO::fromArray($this->getJsonBody());
            $user = $this->authService->updateProfile($userId, $dto);
            $this->jsonResponse(['data' => $user->toApiArray()], 200);
        });
    }
}
