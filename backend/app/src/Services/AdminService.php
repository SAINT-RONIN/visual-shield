<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\AdminServiceInterface;
use App\Models\User;
use App\Repositories\UserRepository;

/**
 * Handles admin-only user management actions.
 *
 * Keeps the AdminController as a thin HTTP layer by encapsulating
 * the domain rules here — e.g. the null-check after a role update
 * belongs to business logic, not to the repository.
 */
class AdminService extends BaseService implements AdminServiceInterface
{
    public function __construct(
        private UserRepository $userRepository,
    ) {}

    /**
     * This returns the user list for the admin area so the controller does not
     * need to know anything about how the records are fetched or ordered.
     *
     * @return User[]
     */
    public function listUsers(): array
    {
        return $this->userRepository->findAll();
    }

    /**
     * This changes a user's role and immediately returns the updated model so
     * the admin UI can reflect the new state without doing another lookup.
     *
     * @throws NotFoundException If no user with the given ID exists.
     */
    public function updateUserRole(int $id, string $role): User
    {
        return $this->findOrFail($this->userRepository->updateRole($id, $role), 'User not found');
    }
}
