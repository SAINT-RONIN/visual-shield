<?php

declare(strict_types=1);

namespace App\Utils;

/**
 * GD-based pixel analysis utility for extracted video frames.
 *
 * Purpose: Provides low-level image metrics -- average luminance and
 * per-pixel frame difference -- that FlashDetector and MotionDetector
 * consume to decide whether a frame pair contains a flash or significant
 * motion.
 *
 * Why do I need it: The detectors need numeric scores derived from raw
 * pixel data, but they should not contain GD calls themselves. This
 * utility isolates all GD/pixel math so the detectors stay focused on
 * threshold logic, and the expensive pixel-sampling strategy (every 4th
 * pixel via PIXEL_SAMPLE_STEP) is defined in one place.
 */
class ImageAnalyzer
{
    /**
     * Sample every Nth pixel in both X and Y directions.
     * A step of 4 means ~1/16th of all pixels are evaluated,
     * giving a 16x speed-up with negligible accuracy loss on typical video frames.
     */
    private const PIXEL_SAMPLE_STEP = 4;

    /**
     * Compute the average luminance of a single frame image.
     *
     * Loads the JPEG at the given path, samples every PIXEL_SAMPLE_STEP-th
     * pixel, converts each to luminance via BT.601, and returns the mean.
     *
     * @param string $imagePath Absolute path to a JPEG frame.
     * @return float Average luminance in the 0-255 range.
     */
    public static function calculateAverageLuminance(string $imagePath): float
    {
        $image = self::loadImage($imagePath);
        $luminance = self::computeLuminanceFromImage($image);
        imagedestroy($image);

        return $luminance;
    }

    /**
     * Compute the mean per-channel pixel difference between two frames.
     *
     * Loads both images, iterates over sampled pixels, computes the average
     * absolute difference across R, G, B channels, and returns the overall
     * mean. Higher values indicate more visual change (motion).
     *
     * @param string $imagePath1 Absolute path to the first JPEG frame.
     * @param string $imagePath2 Absolute path to the second JPEG frame.
     * @return float Mean per-channel difference in the 0-255 range.
     */
    public static function calculateFrameDifference(string $imagePath1, string $imagePath2): float
    {
        $image1 = self::loadImage($imagePath1);
        $image2 = self::loadImage($imagePath2);

        $width = min(imagesx($image1), imagesx($image2));
        $height = min(imagesy($image1), imagesy($image2));

        $totalDiff = 0.0;
        $sampleCount = 0;

        for ($y = 0; $y < $height; $y += self::PIXEL_SAMPLE_STEP) {
            for ($x = 0; $x < $width; $x += self::PIXEL_SAMPLE_STEP) {
                $totalDiff += self::pixelDifference(
                    imagecolorat($image1, $x, $y),
                    imagecolorat($image2, $x, $y)
                );
                $sampleCount++;
            }
        }

        imagedestroy($image1);
        imagedestroy($image2);

        return $sampleCount > 0 ? $totalDiff / $sampleCount : 0.0;
    }

    /**
     * Analyse two frames in a single pass: luminance for both + motion score.
     *
     * Combines calculateAverageLuminance and calculateFrameDifference into
     * one loop so each pixel pair is read only once, cutting GD overhead
     * roughly in half compared to calling both methods separately.
     *
     * @param string $imagePath1 Absolute path to the first JPEG frame.
     * @param string $imagePath2 Absolute path to the second JPEG frame.
     * @return array{luminance1: float, luminance2: float, motionIntensity: float}
     */
    public static function analyzeFramePair(string $imagePath1, string $imagePath2): array
    {
        $image1 = self::loadImage($imagePath1);
        $image2 = self::loadImage($imagePath2);

        $width = min(imagesx($image1), imagesx($image2));
        $height = min(imagesy($image1), imagesy($image2));

        $totalLum1 = 0.0;
        $totalLum2 = 0.0;
        $totalDiff = 0.0;
        $sampleCount = 0;

        for ($y = 0; $y < $height; $y += self::PIXEL_SAMPLE_STEP) {
            for ($x = 0; $x < $width; $x += self::PIXEL_SAMPLE_STEP) {
                $rgb1 = imagecolorat($image1, $x, $y);
                $rgb2 = imagecolorat($image2, $x, $y);

                $totalLum1 += self::rgbToLuminance($rgb1);
                $totalLum2 += self::rgbToLuminance($rgb2);
                $totalDiff += self::pixelDifference($rgb1, $rgb2);
                $sampleCount++;
            }
        }

        imagedestroy($image1);
        imagedestroy($image2);

        if ($sampleCount === 0) {
            return ['luminance1' => 0.0, 'luminance2' => 0.0, 'motionIntensity' => 0.0];
        }

        return [
            'luminance1' => $totalLum1 / $sampleCount,
            'luminance2' => $totalLum2 / $sampleCount,
            'motionIntensity' => $totalDiff / $sampleCount,
        ];
    }

    /**
     * Load a JPEG image from disk into a GdImage resource.
     *
     * @param string $path Absolute path to the JPEG file.
     * @return \GdImage The loaded image resource.
     * @throws \RuntimeException If GD cannot decode the file.
     */
    private static function loadImage(string $path): \GdImage
    {
        $image = @imagecreatefromjpeg($path);

        if ($image === false) {
            throw new \RuntimeException("Failed to load image: {$path}");
        }

        return $image;
    }

    /**
     * Compute mean luminance from an already-loaded GdImage.
     *
     * Iterates over sampled pixels, converts each to luminance via
     * rgbToLuminance, and returns the arithmetic mean.
     *
     * @param \GdImage $image The loaded image resource.
     * @return float Average luminance in the 0-255 range.
     */
    private static function computeLuminanceFromImage(\GdImage $image): float
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $totalLuminance = 0.0;
        $sampleCount = 0;

        for ($y = 0; $y < $height; $y += self::PIXEL_SAMPLE_STEP) {
            for ($x = 0; $x < $width; $x += self::PIXEL_SAMPLE_STEP) {
                $totalLuminance += self::rgbToLuminance(imagecolorat($image, $x, $y));
                $sampleCount++;
            }
        }

        return $sampleCount > 0 ? $totalLuminance / $sampleCount : 0.0;
    }

    /**
     * Convert a packed RGB integer to perceived luminance using BT.601.
     *
     * GD's imagecolorat() returns a single int where bits 23-16 hold red,
     * 15-8 hold green, and 7-0 hold blue. The bit-shifting extracts each
     * channel:
     *   - ($rgb >> 16) & 0xFF  -- shift right 16 bits to move red into
     *     the lowest byte, then mask to isolate 8 bits.
     *   - ($rgb >> 8) & 0xFF   -- same for green (shift 8 bits).
     *   - $rgb & 0xFF          -- blue is already in the lowest byte.
     *
     * The BT.601 weights (0.299 R, 0.587 G, 0.114 B) model human
     * perception where green contributes most to perceived brightness.
     *
     * @param int $rgb Packed 24-bit RGB value from imagecolorat().
     * @return float Luminance in the 0-255 range.
     */
    private static function rgbToLuminance(int $rgb): float
    {
        // Shift right 16 bits to isolate the red channel (bits 23-16)
        $r = ($rgb >> 16) & 0xFF;
        // Shift right 8 bits to isolate the green channel (bits 15-8)
        $g = ($rgb >> 8) & 0xFF;
        // Mask the lowest 8 bits to isolate the blue channel (bits 7-0)
        $b = $rgb & 0xFF;

        return 0.299 * $r + 0.587 * $g + 0.114 * $b;
    }

    /**
     * Compute the mean per-channel absolute difference between two pixels.
     *
     * Each packed RGB int is split into R, G, B via bit-shifting (see
     * rgbToLuminance for a detailed explanation of the bit layout).
     * The absolute difference of each channel is averaged to produce a
     * single 0-255 score representing how much the pixel changed.
     *
     * @param int $rgb1 Packed 24-bit RGB value of the first pixel.
     * @param int $rgb2 Packed 24-bit RGB value of the second pixel.
     * @return float Mean absolute channel difference (0-255).
     */
    private static function pixelDifference(int $rgb1, int $rgb2): float
    {
        // Extract R, G, B from first pixel (see rgbToLuminance for bit layout)
        $r1 = ($rgb1 >> 16) & 0xFF;
        $g1 = ($rgb1 >> 8) & 0xFF;
        $b1 = $rgb1 & 0xFF;

        // Extract R, G, B from second pixel
        $r2 = ($rgb2 >> 16) & 0xFF;
        $g2 = ($rgb2 >> 8) & 0xFF;
        $b2 = $rgb2 & 0xFF;

        return (abs($r1 - $r2) + abs($g1 - $g2) + abs($b1 - $b2)) / 3.0;
    }
}
