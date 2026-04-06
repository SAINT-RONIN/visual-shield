<?php

declare(strict_types=1);

namespace App\Config;

/**
 * Central configuration constants for the video analysis pipeline.
 *
 * Purpose: Provides a single source of truth for thresholds, limits, and
 * allowed values used across FlashDetector, MotionDetector, VideoService,
 * and AnalysisService, ensuring consistent behaviour without magic numbers
 * scattered throughout the codebase.
 *
 * Why do I need it: Hard-coding thresholds in each detector or service
 * would make tuning difficult and create subtle inconsistencies when two
 * classes use different values for the same logical limit. Centralising
 * them here lets you adjust the entire pipeline from one file.
 */
class AnalysisConfig
{
    /** Maximum number of frames the pipeline will extract from a single video. */
    public const MAX_TOTAL_FRAMES = 10000;

    /** Luminance delta (0-255) between consecutive frames above which a flash is flagged. */
    public const FLASH_THRESHOLD = 20;

    /** Number of flashes per second that constitutes a dangerous frequency (per WCAG/Harding). */
    public const FLASH_FREQUENCY_DANGER = 3;

    /** Per-channel pixel difference (0-255) above which motion is considered significant. */
    public const MOTION_THRESHOLD = 30;

    /** Sampling rates (fps) the user is allowed to choose for frame extraction. */
    public const ALLOWED_SAMPLING_RATES = [10, 15, 30, 60];

    /** Maximum allowed upload file size in bytes (500 MB). */
    public const MAX_FILE_SIZE = 524288000; // 500 MB

    /** Maximum luminance value (8-bit colour depth). */
    public const LUMINANCE_MAX = 255;

    /** Number of bytes used to generate a bearer token (produces a 64-char hex string). */
    public const TOKEN_RANDOM_BYTES = 32;

    /** How long a bearer token stays valid, in hours. */
    public const TOKEN_EXPIRY_HOURS = 24;

    /** Number of bytes used to generate a unique storage filename (produces a 32-char hex string). */
    public const STORAGE_FILENAME_RANDOM_BYTES = 16;


    /** Flashes/sec above this value are classified as "high" severity. */
    public const FLASH_SEVERITY_HIGH = 10;

    /** Flashes/sec above this value are classified as "medium" severity. */
    public const FLASH_SEVERITY_MEDIUM = 5;


    /** Motion intensity above this value is classified as "high" severity. */
    public const MOTION_SEVERITY_HIGH = 120;

    /** Motion intensity above this value is classified as "medium" severity. */
    public const MOTION_SEVERITY_MEDIUM = 60;


    /** Flash event count above this is flagged as "danger" risk color. */
    public const FLASH_COUNT_DANGER = 50;

    /** Flash event count above this is flagged as "warning" risk color. */
    public const FLASH_COUNT_WARNING = 20;


    /** Minimum number of items per page. */
    public const PAGINATION_MIN_LIMIT = 1;

    /** Maximum number of items per page. */
    public const PAGINATION_MAX_LIMIT = 100;

    /**
     * Get the absolute path to the app root directory (backend/app/).
     *
     * Used by VideoService and AnalysisService to resolve storage paths.
     * Centralised here so the relative path only appears in one place.
     *
     * @return string Absolute backend app root path.
     */
    public static function appRoot(): string
    {
        return __DIR__ . '/../..';
    }
}
