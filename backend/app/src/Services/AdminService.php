<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\AdminServiceInterface;
use App\Exceptions\NotFoundException;
use App\Models\User;
use App\Repositories\UserRepository;

/**
 * Business logic for admin-only user management operations.
 *
 * Keeps the AdminController as a thin HTTP layer by encapsulating
 * the domain rules here — e.g. the null-check after a role update
 * belongs to business logic, not to the repository.
 */
class AdminService implements AdminServiceInterface
{
    public function __construct(
        private UserRepository $userRepository,
    ) {}

    /**
     * Return all registered users ordered by creation date.
     *
     * @return User[]
     */
    public function listUsers(): array
    {
        return $this->userRepository->findAll();
    }

    /**
     * Change a user's role and return the updated User model.
     *
     * @throws NotFoundException If no user with the given ID exists.
     */
    public function updateUserRole(int $id, string $role): User
    {
        $user = $this->userRepository->updateRole($id, $role);

        if (!$user) {
            throw new NotFoundException('User not found');
        }

        return $user;
    }
}
