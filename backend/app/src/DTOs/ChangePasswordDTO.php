<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Exceptions\ValidationException;

/**
 * Input DTO for an authenticated user changing their own password.
 *
 * Requires the current password so the user's identity is re-confirmed
 * before the change is accepted — even though they are already logged in.
 */
final readonly class ChangePasswordDTO
{
    public function __construct(
        public string $currentPassword,
        public string $newPassword,
    ) {}

    // Throws ValidationException if either field is missing or the new password is too short.
    public static function fromArray(array $data): self
    {
        if (empty($data['currentPassword']) || !is_string($data['currentPassword'])) {
            throw new ValidationException('Current password is required');
        }

        if (empty($data['newPassword']) || !is_string($data['newPassword'])) {
            throw new ValidationException('New password is required');
        }

        if (strlen($data['newPassword']) < 8) {
            throw new ValidationException('New password must be at least 8 characters');
        }

        return new self(
            currentPassword: $data['currentPassword'],
            newPassword: $data['newPassword'],
        );
    }
}
