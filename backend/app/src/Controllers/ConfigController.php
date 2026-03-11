<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Config\AnalysisConfig;
use App\Framework\BaseController;

/**
 * Exposes application constants so the frontend doesn't hardcode them.
 *
 * This is a public (unauthenticated) endpoint -- the values are not
 * sensitive and the frontend needs them before the user logs in
 * (e.g. max upload size shown on the login/upload page).
 */
class ConfigController extends BaseController
{
    /** Return all frontend-relevant configuration constants. */
    public function getConfig(): void
    {
        $this->handleRequest(function () {
            $this->jsonResponse([
                'data' => [
                    'maxUploadSize' => AnalysisConfig::MAX_FILE_SIZE,
                    'flashDangerThreshold' => AnalysisConfig::FLASH_FREQUENCY_DANGER,
                    'motionThreshold' => AnalysisConfig::MOTION_THRESHOLD,
                    'luminanceMax' => AnalysisConfig::LUMINANCE_MAX,
                ]
            ]);
        });
    }
}
