<?php

declare(strict_types=1);

namespace App\Framework;

use App\Repositories\Interfaces\AnalysisDatapointRepositoryInterface;
use App\Repositories\Interfaces\AnalysisResultRepositoryInterface;
use App\Repositories\Interfaces\FlaggedSegmentRepositoryInterface;
use App\Repositories\Interfaces\TokenRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Interfaces\VideoRepositoryInterface;
use App\Services\Interfaces\AdminServiceInterface;
use App\Services\Interfaces\AnalysisServiceInterface;
use App\Services\Interfaces\AuthServiceInterface;
use App\Services\Interfaces\ReportServiceInterface;
use App\Services\Interfaces\VideoServiceInterface;
use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\ConfigController;
use App\Controllers\ReportController;
use App\Controllers\VideoController;
use App\Repositories\UserRepository;
use App\Repositories\TokenRepository;
use App\Repositories\VideoRepository;
use App\Repositories\AnalysisResultRepository;
use App\Repositories\FlaggedSegmentRepository;
use App\Repositories\AnalysisDatapointRepository;
use App\Services\AdminService;
use App\Services\AnalysisService;
use App\Services\AuthService;
use App\Services\ReportService;
use App\Services\VideoService;
use App\Utils\FFprobe;
use App\Utils\FlashDetector;
use App\Utils\FrameExtractor;
use App\Utils\JwtService;
use App\Utils\MotionDetector;

/**
 * Lightweight service registry that wires dependencies in one place.
 *
 * Each service is created once (lazy singleton) and reused across all
 * controllers and middleware. This eliminates the scattered "new Service(
 * new Repo(), new Repo())" calls that were duplicated in every consumer.
 *
 * Not a full DI container â€” just a simple, explicit factory. Every
 * dependency is visible and traceable. No magic, no auto-wiring.
 */
class ServiceRegistry
{
    private static ?AdminController $adminController = null;
    private static ?AuthController $authController = null;
    private static ?ConfigController $configController = null;
    private static ?ReportController $reportController = null;
    private static ?VideoController $videoController = null;
    private static ?UserRepositoryInterface $userRepository = null;
    private static ?TokenRepositoryInterface $tokenRepository = null;
    private static ?VideoRepositoryInterface $videoRepository = null;
    private static ?AnalysisResultRepositoryInterface $analysisResultRepository = null;
    private static ?FlaggedSegmentRepositoryInterface $flaggedSegmentRepository = null;
    private static ?AnalysisDatapointRepositoryInterface $analysisDatapointRepository = null;
    private static ?AdminServiceInterface $adminService = null;
    private static ?AnalysisServiceInterface $analysisService = null;
    private static ?AuthServiceInterface $authService = null;
    private static ?VideoServiceInterface $videoService = null;
    private static ?ReportServiceInterface $reportService = null;
    private static ?FFprobe $ffprobeService = null;
    private static ?JwtService $jwtService = null;

    //  Repositories (lazy singletons)

    /**
     * Get the shared UserRepository instance.
     *
     * @return UserRepositoryInterface Shared repository instance.
     */
    public static function userRepository(): UserRepositoryInterface
    {
        if (!self::$userRepository) {
            self::$userRepository = new UserRepository();
        }

        return self::$userRepository;
    }

    /**
     * Get the shared TokenRepository instance.
     *
     * @return TokenRepositoryInterface Shared repository instance.
     */
    public static function tokenRepository(): TokenRepositoryInterface
    {
        if (!self::$tokenRepository) {
            self::$tokenRepository = new TokenRepository();
        }

        return self::$tokenRepository;
    }

    /**
     * Get the shared VideoRepository instance.
     *
     * @return VideoRepositoryInterface Shared repository instance.
     */
    public static function videoRepository(): VideoRepositoryInterface
    {
        if (!self::$videoRepository) {
            self::$videoRepository = new VideoRepository();
        }

        return self::$videoRepository;
    }

    /**
     * Get the shared AnalysisResultRepository instance.
     *
     * @return AnalysisResultRepositoryInterface Shared repository instance.
     */
    public static function analysisResultRepository(): AnalysisResultRepositoryInterface
    {
        if (!self::$analysisResultRepository) {
            self::$analysisResultRepository = new AnalysisResultRepository();
        }

        return self::$analysisResultRepository;
    }

    /**
     * Get the shared FlaggedSegmentRepository instance.
     *
     * @return FlaggedSegmentRepositoryInterface Shared repository instance.
     */
    public static function flaggedSegmentRepository(): FlaggedSegmentRepositoryInterface
    {
        if (!self::$flaggedSegmentRepository) {
            self::$flaggedSegmentRepository = new FlaggedSegmentRepository();
        }

        return self::$flaggedSegmentRepository;
    }

    /**
     * Get the shared AnalysisDatapointRepository instance.
     *
     * @return AnalysisDatapointRepositoryInterface Shared repository instance.
     */
    public static function analysisDatapointRepository(): AnalysisDatapointRepositoryInterface
    {
        if (!self::$analysisDatapointRepository) {
            self::$analysisDatapointRepository = new AnalysisDatapointRepository();
        }

        return self::$analysisDatapointRepository;
    }

    //  Services (lazy singletons, using cached repos)

    /**
     * Get the shared AdminService instance.
     *
     * @return AdminServiceInterface Shared service instance.
     */
    public static function adminService(): AdminServiceInterface
    {
        if (!self::$adminService) {
            self::$adminService = new AdminService(
                self::userRepository(),
            );
        }

        return self::$adminService;
    }

    /**
     * Get the shared FFprobe instance.
     *
     * @return FFprobe Shared utility instance.
     */
    public static function ffprobeService(): FFprobe
    {
        if (!self::$ffprobeService) {
            self::$ffprobeService = new FFprobe();
        }

        return self::$ffprobeService;
    }

    /**
     * Get the shared JwtService instance.
     *
     * @return JwtService Shared utility instance.
     */
    public static function jwtService(): JwtService
    {
        if (!self::$jwtService) {
            self::$jwtService = new JwtService();
        }

        return self::$jwtService;
    }

    /**
     * Get the shared AnalysisService instance.
     *
     * @return AnalysisServiceInterface Shared service instance.
     */
    public static function analysisService(): AnalysisServiceInterface
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

    /**
     * Get the shared AuthService instance.
     *
     * @return AuthServiceInterface Shared service instance.
     */
    public static function authService(): AuthServiceInterface
    {
        if (!self::$authService) {
            self::$authService = new AuthService(
                self::userRepository(),
                self::tokenRepository(),
                self::jwtService(),
            );
        }

        return self::$authService;
    }

    /**
     * Get the shared VideoService instance.
     *
     * @return VideoServiceInterface Shared service instance.
     */
    public static function videoService(): VideoServiceInterface
    {
        if (!self::$videoService) {
            self::$videoService = new VideoService(
                self::videoRepository(),
                self::ffprobeService(),
            );
        }

        return self::$videoService;
    }

    /**
     * Get the shared ReportService instance.
     *
     * @return ReportServiceInterface Shared service instance.
     */
    public static function reportService(): ReportServiceInterface
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

    //  Controllers (lazy singletons)

    /**
     * Get the shared AdminController instance.
     *
     * @return AdminController Shared controller instance.
     */
    public static function adminController(): AdminController
    {
        if (!self::$adminController) {
            self::$adminController = new AdminController();
        }

        return self::$adminController;
    }

    /**
     * Get the shared AuthController instance.
     *
     * @return AuthController Shared controller instance.
     */
    public static function authController(): AuthController
    {
        if (!self::$authController) {
            self::$authController = new AuthController();
        }

        return self::$authController;
    }

    /**
     * Get the shared ConfigController instance.
     *
     * @return ConfigController Shared controller instance.
     */
    public static function configController(): ConfigController
    {
        if (!self::$configController) {
            self::$configController = new ConfigController();
        }

        return self::$configController;
    }

    /**
     * Get the shared ReportController instance.
     *
     * @return ReportController Shared controller instance.
     */
    public static function reportController(): ReportController
    {
        if (!self::$reportController) {
            self::$reportController = new ReportController();
        }

        return self::$reportController;
    }

    /**
     * Get the shared VideoController instance.
     *
     * @return VideoController Shared controller instance.
     */
    public static function videoController(): VideoController
    {
        if (!self::$videoController) {
            self::$videoController = new VideoController();
        }

        return self::$videoController;
    }
}
