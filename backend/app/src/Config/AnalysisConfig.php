<?php

namespace App\Config;

class AnalysisConfig
{
    public const MAX_TOTAL_FRAMES = 10000;
    public const FLASH_THRESHOLD = 20;
    public const FLASH_FREQUENCY_DANGER = 3;
    public const MOTION_THRESHOLD = 30;
    public const ALLOWED_SAMPLING_RATES = [10, 15, 30, 60];
    public const MAX_FILE_SIZE = 524288000; // 500 MB
}
