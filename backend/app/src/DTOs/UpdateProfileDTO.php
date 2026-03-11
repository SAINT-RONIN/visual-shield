<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Immutable value object wrapping profile update fields.
 *
 * Purpose: Encapsulates the PUT /auth/profile payload so the controller
 * can hand a single typed object to the service layer instead of a
 * loose associative array.
 *
 * Why do I need it: Profile updates may carry optional, nullable fields
 * (currently only displayName). Wrapping them in a DTO makes the
 * contract explicit — callers know exactly which fields are updatable,
 * values are trimmed consistently, and adding new profile fields later
 * requires only a new promoted property rather than scattering array
 * key checks across multiple files.
 */
class UpdateProfileDTO
{
    /**
     * @param string|null $displayName Trimmed display name, or null to clear it.
     */
    public function __construct(
        public readonly ?string $displayName
    ) {}

    /**
     * Build an UpdateProfileDTO from a raw associative array (decoded JSON body).
     *
     * Trims the displayName if present; passes null otherwise.
     *
     * @param array $data Raw request payload (e.g. from json_decode).
     * @return self       Immutable DTO ready for the service layer.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            displayName: isset($data['displayName']) ? trim($data['displayName']) : null
        );
    }
}
