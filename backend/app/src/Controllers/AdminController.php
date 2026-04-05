<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\Interfaces\AdminServiceInterface;
use App\DTOs\UpdateUserRoleDTO;
use App\DTOs\UserFilterDTO;
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

    /**
     * Create the controller with its admin service dependency.
     *
     * @return void
     */
    public function __construct()
    {
        $this->adminService = ServiceRegistry::adminService();
    }

    /**
     * List all users (admin only).
     *
     * @return void
     */
    public function listUsers(): void
    {
        $this->handleRequest(function () {
            $this->requireAdmin();
            $filters = UserFilterDTO::fromQuery($_GET);

            $result = $this->adminService->listUsers($filters);

            $this->jsonResponse([
                'data' => array_map(fn(User $user) => $user->toApiArray(), $result->items),
                'pagination' => [
                    'total' => $result->total,
                    'limit' => $result->limit,
                    'offset' => $result->offset,
                ],
                'summary' => [
                    'adminCount' => $this->adminService->countAdmins(),
                ],
            ]);
        });
    }

    /**
     * Update a user's role (admin only).
     *
     * @param int $id User ID to update.
     * @return void
     */
    public function updateUserRole(int $id): void
    {
        $this->handleRequest(function () use ($id) {
            $this->requireAdmin();

            $dto = UpdateUserRoleDTO::fromArray($this->getJsonBody());
            $user = $this->adminService->updateUserRole($id, $dto->role);

            $this->jsonResponse(['data' => $user->toApiArray()]);
        });
    }

    /**
     * Enforce admin access for the current request.
     *
     * @return void
     */
    private function requireAdmin(): void
    {
        $this->getAuthenticatedUserId();
        $this->requireRole('admin');
    }
}
