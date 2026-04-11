<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\DTOs\UserFilterDTO;
use App\Models\User;

interface UserRepositoryInterface
{
    /**
     * Look up a user by their unique username.
     *
     * @param string $username Username to search for.
     * @return User|null Matching user, or null if none exists.
     */
    public function findByUsername(string $username): ?User;

    /**
     * Look up a user by primary key.
     *
     * @param int $id User ID to load.
     * @return User|null Matching user, or null if none exists.
     */
    public function findById(int $id): ?User;

    /**
     * Create a new user record.
     *
     * @param string $username Unique username to store.
     * @param string $passwordHash Argon2id password hash.
     * @param string|null $displayName Optional public display name.
     * @param string $role Initial role assigned to the user.
     * @return int Newly created user ID.
     */
    public function create(string $username, string $passwordHash, ?string $displayName, string $role = 'member'): int;

    /**
     * Update a user's editable profile fields.
     *
     * @param int $id User ID to update.
     * @param string|null $displayName New display name value.
     * @return void
     */
    public function updateProfile(int $id, ?string $displayName): void;

    /**
     * Count all users in the database.
     *
     * @return int Total number of users.
     */
    public function countAll(): int;

    /**
     * Count users for a specific role value.
     *
     * @param string $role Role name to count.
     * @return int Number of users with that role.
     */
    public function countByRole(string $role): int;

    /**
     * Retrieve users with optional admin filters.
     *
     * @param UserFilterDTO $filters Validated filter, sort, and pagination options.
     * @return User[] Matching users.
     */
    public function findAll(UserFilterDTO $filters): array;

    /**
     * Count users matching the current admin filters.
     *
     * @param UserFilterDTO $filters Validated filter options.
     * @return int Number of matching users.
     */
    public function countAllFiltered(UserFilterDTO $filters): int;

    /**
     * Update a user's role and return the updated record.
     *
     * @param int $id User ID to update.
     * @param string $role New role value.
     * @return User|null Updated user, or null if the user does not exist.
     */
    public function updateRole(int $id, string $role): ?User;

    /**
     * Update a user's active status and return the updated record.
     *
     * @param int $id User ID to update.
     * @param bool $isActive New active status.
     * @return User|null Updated user, or null if the user does not exist.
     */
    public function updateStatus(int $id, bool $isActive): ?User;

    /**
     * Count active users with the given role.
     *
     * @param string $role Role name to count.
     * @return int Number of active users with that role.
     */
    public function countActiveByRole(string $role): int;
}
