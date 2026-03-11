<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Config\AnalysisConfig;
use App\Exceptions\ValidationException;

/**
 * Immutable value object representing a validated video upload request.
 *
 * Purpose: Validates the $_FILES upload entry and the user-chosen
 * sampling rate before the data reaches VideoController or the
 * storage layer. It maps PHP's UPLOAD_ERR_* constants to human-readable
 * error messages and enforces the allowed sampling rate whitelist
 * defined in AnalysisConfig.
 *
 * Why do I need it: PHP's file upload superglobal is an untyped array
 * with numeric error codes that mean nothing to end users. This DTO
 * centralises upload validation and error translation in one place,
 * keeping the controller free of low-level $_FILES handling. The
 * readonly properties guarantee that once validated, the file metadata
 * and sampling rate cannot be mutated before they reach the persistence
 * layer.
 */
class UploadVideoDTO
{
    /**
     * @param array $file         Validated $_FILES entry (tmp_name, name, size, etc.).
     * @param int   $samplingRate Frames-per-second rate from AnalysisConfig::ALLOWED_SAMPLING_RATES.
     */
    public function __construct(
        public readonly array $file,
        public readonly int $samplingRate,
    ) {}

    /**
     * Build an UploadVideoDTO from the raw $_FILES entry and $_POST data.
     *
     * Checks that a file was actually received, that the PHP upload
     * succeeded (UPLOAD_ERR_OK), and that the requested sampling rate
     * is in the allowed whitelist. Accepts both camelCase and snake_case
     * key names for the sampling rate.
     *
     * @param array $fileData Raw $_FILES['video'] entry.
     * @param array $postData Raw $_POST data containing samplingRate / sampling_rate.
     * @return self            Validated, immutable DTO.
     *
     * @throws \InvalidArgumentException If no file is received, upload failed, or sampling rate is invalid.
     */
    public static function fromRequest(array $fileData, array $postData): self
    {
        if (empty($fileData)) {
            throw new ValidationException('No video file received. Ensure the field name is "video".');
        }

        $uploadError = $fileData['error'] ?? UPLOAD_ERR_NO_FILE;

        if ($uploadError !== UPLOAD_ERR_OK) {
            throw new ValidationException(self::describeUploadError($uploadError));
        }

        // Accept both camelCase and snake_case key names
        $rate = (int) ($postData['samplingRate'] ?? $postData['sampling_rate'] ?? 0);

        if (!in_array($rate, AnalysisConfig::ALLOWED_SAMPLING_RATES, true)) {
            throw new ValidationException(
                'Invalid sampling rate. Allowed values: ' . implode(', ', AnalysisConfig::ALLOWED_SAMPLING_RATES)
            );
        }

        return new self($fileData, $rate);
    }

    /**
     * Map a PHP UPLOAD_ERR_* constant to a user-friendly error message.
     *
     * @param int $code One of the UPLOAD_ERR_* constants.
     * @return string   Human-readable description of the upload failure.
     */
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
