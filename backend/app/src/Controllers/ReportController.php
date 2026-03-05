<?php

namespace App\Controllers;

use App\Framework\BaseController;
use App\Services\ReportService;
use App\Repositories\VideoRepository;
use App\Repositories\AnalysisResultRepository;
use App\Repositories\FlaggedSegmentRepository;
use App\Repositories\AnalysisDatapointRepository;

class ReportController extends BaseController
{
    private ReportService $reportService;

    public function __construct()
    {
        $this->reportService = new ReportService(
            new VideoRepository(),
            new AnalysisResultRepository(),
            new FlaggedSegmentRepository(),
            new AnalysisDatapointRepository(),
        );
    }

    public function getReport(int $videoId): void
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            $report = $this->reportService->getReport($userId, $videoId);
            $this->jsonResponse($report, 200);
        } catch (\RuntimeException $e) {
            $code = $e->getCode() === 404 ? 404 : 500;
            $this->jsonResponse(['error' => ['code' => $code, 'message' => $e->getMessage()]], $code);
        } catch (\Throwable $e) {
            $this->jsonResponse(['error' => ['code' => 500, 'message' => 'Internal server error']], 500);
        }
    }

    public function exportJson(int $videoId): void
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            $report = $this->reportService->exportAsJson($userId, $videoId);
            header('Content-Disposition: attachment; filename="report_' . $videoId . '.json"');
            $this->jsonResponse($report, 200);
        } catch (\RuntimeException $e) {
            $code = $e->getCode() === 404 ? 404 : 500;
            $this->jsonResponse(['error' => ['code' => $code, 'message' => $e->getMessage()]], $code);
        } catch (\Throwable $e) {
            $this->jsonResponse(['error' => ['code' => 500, 'message' => 'Internal server error']], 500);
        }
    }

    public function exportCsv(int $videoId): void
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            $csv = $this->reportService->exportAsCsv($userId, $videoId);
            http_response_code(200);
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="report_' . $videoId . '.csv"');
            echo $csv;
            exit;
        } catch (\RuntimeException $e) {
            $code = $e->getCode() === 404 ? 404 : 500;
            $this->jsonResponse(['error' => ['code' => $code, 'message' => $e->getMessage()]], $code);
        } catch (\Throwable $e) {
            $this->jsonResponse(['error' => ['code' => 500, 'message' => 'Internal server error']], 500);
        }
    }
}
