<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Exceptions\ValidationException;

/**
 * Input DTO for an admin force-resetting another user's password.
 *
 * No current password required — admin privilege is enforced at the
 * controller level via requireRole('admin').
 */
final readonly class AdminResetPasswordDTO
{
    public function __construct(
        public string $newPassword,
    ) {}

    // Throws ValidationException if the new password is missing or too short.
    public static function fromArray(array $data): self
    {
        if (empty($data['newPassword']) || !is_string($data['newPassword'])) {
            throw new ValidationException('New password is required');
        }

        if (strlen($data['newPassword']) < 8) {
            throw new ValidationException('New password must be at least 8 characters');
        }

        return new self(newPassword: $data['newPassword']);
    }
}
