<?php

declare(strict_types=1);

namespace App\Framework;

use App\Repositories\UserRepository;
use App\Repositories\TokenRepository;
use App\Repositories\VideoRepository;
use App\Repositories\AnalysisResultRepository;
use App\Repositories\FlaggedSegmentRepository;
use App\Repositories\AnalysisDatapointRepository;
use App\Services\AdminService;
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
    private static ?UserRepository $userRepository = null;
    private static ?TokenRepository $tokenRepository = null;
    private static ?VideoRepository $videoRepository = null;
    private static ?AnalysisResultRepository $analysisResultRepository = null;
    private static ?FlaggedSegmentRepository $flaggedSegmentRepository = null;
    private static ?AnalysisDatapointRepository $analysisDatapointRepository = null;
    private static ?AdminService $adminService = null;
    private static ?AnalysisService $analysisService = null;
    private static ?AuthService $authService = null;
    private static ?VideoService $videoService = null;
    private static ?ReportService $reportService = null;
    private static ?FFprobeService $ffprobeService = null;

    // ──────────────────────────────────────────────
    //  Repositories (lazy singletons)
    // ──────────────────────────────────────────────

    /** Get the shared UserRepository instance. */
    public static function userRepository(): UserRepository
    {
        if (!self::$userRepository) {
            self::$userRepository = new UserRepository();
        }

        return self::$userRepository;
    }

    /** Get the shared TokenRepository instance. */
    public static function tokenRepository(): TokenRepository
    {
        if (!self::$tokenRepository) {
            self::$tokenRepository = new TokenRepository();
        }

        return self::$tokenRepository;
    }

    /** Get the shared VideoRepository instance. */
    public static function videoRepository(): VideoRepository
    {
        if (!self::$videoRepository) {
            self::$videoRepository = new VideoRepository();
        }

        return self::$videoRepository;
    }

    /** Get the shared AnalysisResultRepository instance. */
    public static function analysisResultRepository(): AnalysisResultRepository
    {
        if (!self::$analysisResultRepository) {
            self::$analysisResultRepository = new AnalysisResultRepository();
        }

        return self::$analysisResultRepository;
    }

    /** Get the shared FlaggedSegmentRepository instance. */
    public static function flaggedSegmentRepository(): FlaggedSegmentRepository
    {
        if (!self::$flaggedSegmentRepository) {
            self::$flaggedSegmentRepository = new FlaggedSegmentRepository();
        }

        return self::$flaggedSegmentRepository;
    }

    /** Get the shared AnalysisDatapointRepository instance. */
    public static function analysisDatapointRepository(): AnalysisDatapointRepository
    {
        if (!self::$analysisDatapointRepository) {
            self::$analysisDatapointRepository = new AnalysisDatapointRepository();
        }

        return self::$analysisDatapointRepository;
    }

    // ──────────────────────────────────────────────
    //  Services (lazy singletons, using cached repos)
    // ──────────────────────────────────────────────

    /** Get the shared AdminService instance. */
    public static function adminService(): AdminService
    {
        if (!self::$adminService) {
            self::$adminService = new AdminService(
                self::userRepository(),
            );
        }

        return self::$adminService;
    }

    /** Get the shared FFprobeService instance. */
    public static function ffprobeService(): FFprobeService
    {
        if (!self::$ffprobeService) {
            self::$ffprobeService = new FFprobeService();
        }

        return self::$ffprobeService;
    }

    /** Get the shared AnalysisService instance. */
    public static function analysisService(): AnalysisService
    {
        if (!self::$analysisService) {
            self::$analysisService = new AnalysisService(
                self::videoRepository(),
                self::analysisResultRepository(),
                self::flaggedSegmentRepository(),
                self::analysisDatapointRepository(),
                new FrameExtractor(),
                self::ffprobeService(),
                new FlashDetector(),
                new MotionDetector(),
            );
        }

        return self::$analysisService;
    }

    /** Get the shared AuthService instance. */
    public static function authService(): AuthService
    {
        if (!self::$authService) {
            self::$authService = new AuthService(
                self::userRepository(),
                self::tokenRepository(),
            );
        }

        return self::$authService;
    }

    /** Get the shared VideoService instance. */
    public static function videoService(): VideoService
    {
        if (!self::$videoService) {
            self::$videoService = new VideoService(
                self::videoRepository(),
                self::ffprobeService(),
            );
        }

        return self::$videoService;
    }

    /** Get the shared ReportService instance. */
    public static function reportService(): ReportService
    {
        if (!self::$reportService) {
            self::$reportService = new ReportService(
                self::videoRepository(),
                self::analysisResultRepository(),
                self::flaggedSegmentRepository(),
                self::analysisDatapointRepository(),
            );
        }

        return self::$reportService;
    }
}
