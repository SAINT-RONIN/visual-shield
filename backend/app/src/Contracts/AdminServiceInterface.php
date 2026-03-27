<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\User;

interface AdminServiceInterface
{
    /**
     * Return all registered users ordered by creation date.
     *
     * @return User[]
     */
    public function listUsers(): array;

    /**
     * Change a user's role and return the updated User model.
     */
    public function updateUserRole(int $id, string $role): User;
}
