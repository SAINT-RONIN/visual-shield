<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Exceptions\ValidationException;

/**
 * Input DTO for the admin update-user-role endpoint.
 *
 * Centralises role validation so that AdminController stays free of
 * inline validation logic and the allowed-roles list lives in one place.
 */
final readonly class UpdateUserRoleDTO
{
    private const ASSIGNABLE_ROLES = ['admin', 'member'];

    public function __construct(
        public string $role,
    ) {}

    // Throws ValidationException if the role is missing or not in the allowed list.
    public static function fromArray(array $data): self
    {
        $role = $data['role'] ?? '';

        if (!in_array($role, self::ASSIGNABLE_ROLES, true)) {
            $allowedRoles = implode(', ', self::ASSIGNABLE_ROLES);
            throw new ValidationException("Invalid role. Allowed: {$allowedRoles}");
        }

        return new self(role: $role);
    }
}
