<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Contracts\AdminServiceInterface;
use App\DTOs\UpdateUserRoleDTO;
use App\Framework\BaseController;
use App\Framework\ServiceRegistry;
use App\Models\User;

/**
 * HTTP layer for admin-only endpoints (user management).
 *
 * Every method calls requireRole('admin') to enforce authorization.
 */
class AdminController extends BaseController
{
    private AdminServiceInterface $adminService;

    public function __construct()
    {
        $this->adminService = ServiceRegistry::adminService();
    }

    /** List all users (admin only). */
    public function listUsers(): void
    {
        $this->handleRequest(function () {
            $this->requireAdmin();

            $users = $this->adminService->listUsers();

            $this->jsonResponse([
                'data' => array_map(fn(User $user) => $user->toApiArray(), $users),
            ]);
        });
    }

    /** Update a user's role (admin only). */
    public function updateUserRole(int $id): void
    {
        $this->handleRequest(function () use ($id) {
            $this->requireAdmin();

            $dto = UpdateUserRoleDTO::fromArray($this->getJsonBody());
            $user = $this->adminService->updateUserRole($id, $dto->role);

            $this->jsonResponse(['data' => $user->toApiArray()]);
        });
    }

    private function requireAdmin(): void
    {
        $this->getAuthenticatedUserId();
        $this->requireRole('admin');
    }
}
