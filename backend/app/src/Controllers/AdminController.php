<?php

declare(strict_types=1);

namespace App\Controllers;

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
    /** Roles that can be assigned via the updateUserRole endpoint. */
    private const ASSIGNABLE_ROLES = ['admin', 'viewer'];

    /** List all users (admin only). */
    public function listUsers(): void
    {
        $this->handleRequest(function () {
            $this->getAuthenticatedUserId();
            $this->requireRole('admin');

            $users = ServiceRegistry::userRepository()->findAll();

            $this->jsonResponse([
                'data' => array_map(fn(User $user) => $user->toApiArray(), $users),
            ]);
        });
    }

    /** Update a user's role (admin only). */
    public function updateUserRole(int $id): void
    {
        $this->handleRequest(function () use ($id) {
            $this->getAuthenticatedUserId();
            $this->requireRole('admin');

            $input = $this->getJsonBody();
            $role = $input['role'] ?? '';

            if (!in_array($role, self::ASSIGNABLE_ROLES, true)) {
                $allowedRoles = implode(', ', self::ASSIGNABLE_ROLES);
                throw new \InvalidArgumentException("Invalid role. Allowed: {$allowedRoles}");
            }

            $user = ServiceRegistry::userRepository()->updateRole($id, $role);

            $this->jsonResponse(['data' => $user->toApiArray()]);
        });
    }
}
