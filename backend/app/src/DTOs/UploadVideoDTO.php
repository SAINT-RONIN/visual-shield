<?php

namespace App\DTOs;

use App\Config\AnalysisConfig;

class UploadVideoDTO
{
    public function __construct(
        public readonly array $file,
        public readonly int $samplingRate,
    ) {}

    public static function fromRequest(array $fileData, array $postData): self
    {
        if (empty($fileData) || ($fileData['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \InvalidArgumentException('No valid file uploaded');
        }

        $rate = (int) ($postData['samplingRate'] ?? 0);

        if (!in_array($rate, AnalysisConfig::ALLOWED_SAMPLING_RATES, true)) {
            throw new \InvalidArgumentException('Invalid sampling rate. Allowed: ' . implode(', ', AnalysisConfig::ALLOWED_SAMPLING_RATES));
        }

        return new self($fileData, $rate);
    }
}
