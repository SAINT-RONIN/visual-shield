<?php

declare(strict_types=1);

namespace App\Contracts;

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
     * Change a user's role and return the updated User model.
     *
     * @param int $id The user ID to update.
     * @param string $role The new role to assign.
     * @return User Updated user model after the role change.
     */
    public function updateUserRole(int $id, string $role): User;
}
