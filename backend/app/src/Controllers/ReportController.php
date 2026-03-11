<?php

declare(strict_types=1);

namespace App\Controllers;

use App\DTOs\SegmentFilterDTO;
use App\Framework\BaseController;
use App\Framework\ServiceRegistry;
use App\Services\ReportService;

/**
 * HTTP layer for analysis report endpoints (view, export JSON, export CSV).
 *
 * Translates HTTP requests into ReportService calls, sets the correct
 * Content-Type and Content-Disposition headers for downloads, and lets
 * BaseController::handleRequest() map exceptions to HTTP status codes.
 */
class ReportController extends BaseController
{
    private ReportService $reportService;

    public function __construct()
    {
        $this->reportService = ServiceRegistry::reportService();
    }

    /** Return the full analysis report for a video as JSON. */
    public function getReport(int $videoId): void
    {
        $this->handleRequest(function () use ($videoId) {
            $userId = $this->getAuthenticatedUserId();
            $segmentFilters = SegmentFilterDTO::fromQuery($_GET);
            $report = $this->reportService->getReport($userId, $videoId, $segmentFilters);
            $this->jsonResponse($report->toArray(), 200);
        });
    }

    /** Download the full report as a JSON file attachment. */
    public function exportJson(int $videoId): void
    {
        $this->handleRequest(function () use ($videoId) {
            $userId = $this->getAuthenticatedUserId();
            $report = $this->reportService->exportAsJson($userId, $videoId);
            header('Content-Disposition: attachment; filename="report_' . $videoId . '.json"');
            $this->jsonResponse($report->toArray(), 200);
        });
    }

    /** Download flagged segments as a CSV file attachment. */
    public function exportCsv(int $videoId): void
    {
        $this->handleRequest(function () use ($videoId) {
            $userId = $this->getAuthenticatedUserId();
            $csv = $this->reportService->exportAsCsv($userId, $videoId);
            http_response_code(200);
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="report_' . $videoId . '.csv"');
            echo $csv;
            exit;
        });
    }
}
