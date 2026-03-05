<?php

namespace App\Utils;

class ImageAnalyzer
{
    private const PIXEL_SAMPLE_STEP = 4;

    public static function calculateAverageLuminance(string $imagePath): float
    {
        $image = self::loadImage($imagePath);
        $luminance = self::computeLuminanceFromImage($image);
        imagedestroy($image);

        return $luminance;
    }

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

    // Analyzes two frames in one pass: luminance for both + motion score.
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

    private static function loadImage(string $path): \GdImage
    {
        $image = @imagecreatefromjpeg($path);

        if ($image === false) {
            throw new \RuntimeException("Failed to load image: {$path}");
        }

        return $image;
    }

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

    private static function rgbToLuminance(int $rgb): float
    {
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;

        return 0.299 * $r + 0.587 * $g + 0.114 * $b;
    }

    private static function pixelDifference(int $rgb1, int $rgb2): float
    {
        $r1 = ($rgb1 >> 16) & 0xFF;
        $g1 = ($rgb1 >> 8) & 0xFF;
        $b1 = $rgb1 & 0xFF;

        $r2 = ($rgb2 >> 16) & 0xFF;
        $g2 = ($rgb2 >> 8) & 0xFF;
        $b2 = $rgb2 & 0xFF;

        return (abs($r1 - $r2) + abs($g1 - $g2) + abs($b1 - $b2)) / 3.0;
    }
}
