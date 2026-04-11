<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\Interfaces\AdminServiceInterface;
use App\DTOs\CreateUserDTO;
use App\DTOs\PaginatedResultDTO;
use App\DTOs\UserFilterDTO;
use App\Exceptions\ValidationException;
use App\Models\User;

/**
 * Handles admin-only user management actions.
 *
 * Keeps the AdminController as a thin HTTP layer by encapsulating
 * the domain rules here â€” e.g. the null-check after a role update
 * belongs to business logic, not to the repository.
 */
class AdminService extends BaseService implements AdminServiceInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    // Returns the paginated user list for the admin area.
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

    // Counts how many admin accounts currently exist.
    public function countAdmins(): int
    {
        return $this->userRepository->countByRole('admin');
    }

    // Returns the updated model immediately; throws ValidationException if this would remove the last admin.
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

    // Throws ValidationException if this would deactivate the last active admin.
    public function deactivateUser(int $id): User
    {
        $user = $this->findOrFail($this->userRepository->findById($id), 'User not found');

        if (!$user->isActive) {
            return $user;
        }

        if ($user->role === 'admin' && $this->userRepository->countActiveByRole('admin') <= 1) {
            throw new ValidationException('At least one active admin account must remain');
        }

        return $this->findOrFail($this->userRepository->updateStatus($id, false), 'User not found');
    }

    // Re-activates a previously deactivated user account.
    public function activateUser(int $id): User
    {
        $user = $this->findOrFail($this->userRepository->findById($id), 'User not found');

        if ($user->isActive) {
            return $user;
        }

        return $this->findOrFail($this->userRepository->updateStatus($id, true), 'User not found');
    }

    // Creates a new user with an explicitly assigned role; throws ValidationException if the username is taken.
    public function createUser(CreateUserDTO $dto): User
    {
        if ($this->userRepository->findByUsername($dto->username)) {
            throw new ValidationException('Username is already taken');
        }

        $hashedPassword = password_hash($dto->password, PASSWORD_ARGON2ID);
        $newUserId = $this->userRepository->create($dto->username, $hashedPassword, $dto->displayName, $dto->role);

        return $this->findOrFail($this->userRepository->findById($newUserId), 'User not found');
    }
}
