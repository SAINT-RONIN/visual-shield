<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\AdminServiceInterface;
use App\DTOs\PaginatedResultDTO;
use App\DTOs\UserFilterDTO;
use App\Exceptions\ValidationException;
use App\Models\User;
use App\Repositories\UserRepository;

/**
 * Handles admin-only user management actions.
 *
 * Keeps the AdminController as a thin HTTP layer by encapsulating
 * the domain rules here â€” e.g. the null-check after a role update
 * belongs to business logic, not to the repository.
 */
class AdminService extends BaseService implements AdminServiceInterface
{
    /**
     * Create the service with its user repository dependency.
     *
     * @param UserRepository $userRepository Repository used for admin user management.
     * @return void
     */
    public function __construct(
        private UserRepository $userRepository,
    ) {}

    /**
     * This returns the user list for the admin area so the controller does not
     * need to know anything about how the records are fetched or ordered.
     *
     * @param UserFilterDTO $filters Validated admin user filters and pagination settings.
     * @return PaginatedResultDTO Paginated collection of matching users.
     */
    public function listUsers(UserFilterDTO $filters): PaginatedResultDTO
    {
        $users = $this->userRepository->findAll($filters);
        $total = $this->userRepository->countAllFiltered($filters);

        return new PaginatedResultDTO(
            items: $users,
            total: $total,
            limit: $filters->limit,
            offset: $filters->offset,
        );
    }

    /**
     * Count how many admin accounts currently exist.
     *
     * @return int Current admin account count.
     */
    public function countAdmins(): int
    {
        return $this->userRepository->countByRole('admin');
    }

    /**
     * This changes a user's role and immediately returns the updated model so
     * the admin UI can reflect the new state without doing another lookup.
     *
     * @param int $id User ID to update.
     * @param string $role New role value to persist.
     * @return User Updated user model after the role change.
     * @throws \App\Exceptions\NotFoundException If no user with the given ID exists.
     * @throws ValidationException If this would remove the final admin account.
     */
    public function updateUserRole(int $id, string $role): User
    {
        $user = $this->findOrFail($this->userRepository->findById($id), 'User not found');

        if ($user->role === $role) {
            return $user;
        }

        if ($user->role === 'admin' && $role !== 'admin' && $this->countAdmins() <= 1) {
            throw new ValidationException('At least one admin account must remain');
        }

        return $this->findOrFail($this->userRepository->updateRole($id, $role), 'User not found');
    }
}
