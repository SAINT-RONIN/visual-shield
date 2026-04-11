<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\DTOs\CreateUserDTO;
use App\DTOs\PaginatedResultDTO;
use App\DTOs\UserFilterDTO;
use App\Models\User;

interface AdminServiceInterface
{
    /**
     * Return a paginated admin user list with optional filters.
     *
     * @param UserFilterDTO $filters Validated admin user filters and pagination settings.
     * @return PaginatedResultDTO Paginated collection of matching users.
     */
    public function listUsers(UserFilterDTO $filters): PaginatedResultDTO;

    /**
     * Count how many admin accounts currently exist.
     *
     * @return int Current admin account count.
     */
    public function countAdmins(): int;

    /**
     * Change a user's role and return the updated User model.
     *
     * @param int $id The user ID to update.
     * @param string $role The new role to assign.
     * @return User Updated user model after the role change.
     */
    public function updateUserRole(int $id, string $role): User;

    /**
     * Deactivate a user account.
     *
     * @param int $id User ID to deactivate.
     * @return User Updated user model after deactivation.
     */
    public function deactivateUser(int $id): User;

    /**
     * Activate a previously deactivated user account.
     *
     * @param int $id User ID to activate.
     * @return User Updated user model after activation.
     */
    public function activateUser(int $id): User;

    /**
     * Create a new user account with an explicitly assigned role.
     *
     * @param CreateUserDTO $dto Validated user creation payload.
     * @return User The newly created user model.
     */
    public function createUser(CreateUserDTO $dto): User;
}
