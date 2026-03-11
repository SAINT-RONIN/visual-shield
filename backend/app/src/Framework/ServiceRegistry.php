<?php

namespace App\Framework;

use App\Repositories\UserRepository;
use App\Repositories\TokenRepository;
use App\Repositories\VideoRepository;
use App\Repositories\AnalysisResultRepository;
use App\Repositories\FlaggedSegmentRepository;
use App\Repositories\AnalysisDatapointRepository;
use App\Services\AnalysisService;
use App\Services\AuthService;
use App\Services\FFprobeService;
use App\Services\FlashDetector;
use App\Services\FrameExtractor;
use App\Services\MotionDetector;
use App\Services\ReportService;
use App\Services\VideoService;

/**
 * Lightweight service registry that wires dependencies in one place.
 *
 * Each service is created once (lazy singleton) and reused across all
 * controllers and middleware. This eliminates the scattered "new Service(
 * new Repo(), new Repo())" calls that were duplicated in every consumer.
 *
 * Not a full DI container — just a simple, explicit factory. Every
 * dependency is visible and traceable. No magic, no auto-wiring.
 */
class ServiceRegistry
{
    private static ?VideoRepository $videoRepository = null;
    private static ?AnalysisService $analysisService = null;
    private static ?AuthService $authService = null;
    private static ?VideoService $videoService = null;
    private static ?ReportService $reportService = null;

    public static function videoRepository(): VideoRepository
    {
        if (!self::$videoRepository) {
            self::$videoRepository = new VideoRepository();
        }

        return self::$videoRepository;
    }

    public static function analysisService(): AnalysisService
    {
        if (!self::$analysisService) {
            self::$analysisService = new AnalysisService(
                new VideoRepository(),
                new AnalysisResultRepository(),
                new FlaggedSegmentRepository(),
                new AnalysisDatapointRepository(),
                new FrameExtractor(),
                new FFprobeService(),
                new FlashDetector(),
                new MotionDetector(),
            );
        }

        return self::$analysisService;
    }

    public static function authService(): AuthService
    {
        if (!self::$authService) {
            self::$authService = new AuthService(
                new UserRepository(),
                new TokenRepository(),
            );
        }

        return self::$authService;
    }

    public static function videoService(): VideoService
    {
        if (!self::$videoService) {
            self::$videoService = new VideoService(
                new VideoRepository(),
                new FFprobeService(),
            );
        }

        return self::$videoService;
    }

    public static function reportService(): ReportService
    {
        if (!self::$reportService) {
            self::$reportService = new ReportService(
                new VideoRepository(),
                new AnalysisResultRepository(),
                new FlaggedSegmentRepository(),
                new AnalysisDatapointRepository(),
            );
        }

        return self::$reportService;
    }
}
