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
     */
    public function listUsers(UserFilterDTO $filters): PaginatedResultDTO;

    /**
     * Change a user's role and return the updated User model.
     */
    public function updateUserRole(int $id, string $role): User;
}
