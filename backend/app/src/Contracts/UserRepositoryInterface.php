<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\User;

interface UserRepositoryInterface
{
    public function findByUsername(string $username): ?User;

    public function findById(int $id): ?User;

    public function create(string $username, string $passwordHash, ?string $displayName, string $role = 'viewer'): int;

    public function updateProfile(int $id, ?string $displayName): void;

    public function countAll(): int;

    /** @return User[] */
    public function findAll(): array;

    public function updateRole(int $id, string $role): ?User;
}
