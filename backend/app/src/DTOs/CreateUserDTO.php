<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Exceptions\ValidationException;

/**
 * Input DTO for admin-created user accounts.
 *
 * Unlike RegisterDTO, the role is explicitly chosen by the admin
 * rather than inferred from whether this is the first registered user.
 */
final readonly class CreateUserDTO
{
    private const ASSIGNABLE_ROLES = ['admin', 'member'];

    public function __construct(
        public readonly string $username,
        public readonly string $password,
        public readonly ?string $displayName,
        public readonly string $role,
    ) {}

    // Throws ValidationException if any required field is missing or the role is not assignable.
    public static function fromArray(array $data): self
    {
        if (empty($data['username']) || !is_string($data['username'])) {
            throw new ValidationException('Username is required');
        }

        if (empty($data['password']) || !is_string($data['password'])) {
            throw new ValidationException('Password is required');
        }

        $role = $data['role'] ?? '';
        if (!in_array($role, self::ASSIGNABLE_ROLES, true)) {
            $allowedRoles = implode(', ', self::ASSIGNABLE_ROLES);
            throw new ValidationException("Invalid role. Allowed: {$allowedRoles}");
        }

        $rawDisplayName = $data['displayName'] ?? '';
        $displayName = is_string($rawDisplayName) && trim($rawDisplayName) !== ''
            ? trim($rawDisplayName)
            : null;

        return new self(
            username: trim($data['username']),
            password: $data['password'],
            displayName: $displayName,
            role: $role,
        );
    }
}
