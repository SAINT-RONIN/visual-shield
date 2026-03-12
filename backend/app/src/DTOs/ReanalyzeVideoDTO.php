<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Config\AnalysisConfig;
use App\Exceptions\ValidationException;

/**
 * Immutable value object representing a validated re-analysis request.
 *
 * Parses the sampling rate from the JSON request body and validates it
 * against the allowed whitelist. If no sampling rate is provided, it
 * defaults to 15 fps (a reasonable middle ground for most videos).
 */
class ReanalyzeVideoDTO
{
    /** Default sampling rate when the user doesn't specify one. */
    private const DEFAULT_SAMPLING_RATE = 15;

    public function __construct(
        public readonly int $samplingRate,
    ) {}

    /**
     * Build a ReanalyzeVideoDTO from the raw JSON request body.
     *
     * Accepts both camelCase ("samplingRate") and snake_case ("sampling_rate")
     * key names. Falls back to 15fps if neither is provided.
     *
     * @param  array $body The decoded JSON request body.
     * @return self  Validated, immutable DTO.
     *
     * @throws \InvalidArgumentException If the sampling rate isn't in the allowed list.
     */
    public static function fromArray(array $body): self
    {
        $rawRate = $body['samplingRate'] ?? $body['sampling_rate'] ?? self::DEFAULT_SAMPLING_RATE;
        $rate = (int) $rawRate;

        if (!in_array($rate, AnalysisConfig::ALLOWED_SAMPLING_RATES, true)) {
            $allowedRates = implode(', ', AnalysisConfig::ALLOWED_SAMPLING_RATES);
            throw new ValidationException("Invalid sampling rate. Allowed: {$allowedRates}");
        }

        return new self($rate);
    }
}
