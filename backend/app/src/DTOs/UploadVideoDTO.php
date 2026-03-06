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
        if (empty($fileData)) {
            throw new \InvalidArgumentException('No video file received. Ensure the field name is "video".');
        }

        $uploadError = $fileData['error'] ?? UPLOAD_ERR_NO_FILE;

        if ($uploadError !== UPLOAD_ERR_OK) {
            throw new \InvalidArgumentException(self::describeUploadError($uploadError));
        }

        // Accept both camelCase and snake_case key names
        $rate = (int) ($postData['samplingRate'] ?? $postData['sampling_rate'] ?? 0);

        if (!in_array($rate, AnalysisConfig::ALLOWED_SAMPLING_RATES, true)) {
            throw new \InvalidArgumentException(
                'Invalid sampling rate. Allowed values: ' . implode(', ', AnalysisConfig::ALLOWED_SAMPLING_RATES)
            );
        }

        return new self($fileData, $rate);
    }

    private static function describeUploadError(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE   => 'File exceeds the server upload_max_filesize limit.',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds the form MAX_FILE_SIZE limit.',
            UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server is missing a temporary upload folder.',
            UPLOAD_ERR_CANT_WRITE => 'Server failed to write the file to disk.',
            UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the upload.',
            default               => "Upload failed with error code {$code}.",
        };
    }
}
