<?php

declare(strict_types=1);

namespace App\Controllers;

use App\DTOs\ReportDTO;
use App\DTOs\SegmentFilterDTO;
use App\Framework\BaseController;
use App\Framework\ServiceRegistry;
use App\Models\AnalysisDatapoint;
use App\Models\FlaggedSegment;
use App\Services\ReportService;
use App\Utils\RiskLevel;

/**
 * HTTP layer for analysis report endpoints (view, export JSON, export CSV).
 *
 * Translates HTTP requests into ReportService calls, sets the correct
 * Content-Type and Content-Disposition headers for downloads, and lets
 * BaseController::handleRequest() map exceptions to HTTP status codes.
 *
 * Serialisation (toApiArray()) happens exclusively here — never in the
 * service layer or in DTOs.
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
            $this->jsonResponse($this->buildReportPayload($report), 200);
        });
    }

    /** Download the full report as a JSON file attachment. */
    public function exportJson(int $videoId): void
    {
        $this->handleRequest(function () use ($videoId) {
            $userId = $this->getAuthenticatedUserId();
            $report = $this->reportService->exportAsJson($userId, $videoId);
            header('Content-Disposition: attachment; filename="report_' . $videoId . '.json"');
            $this->jsonResponse($this->buildReportPayload($report), 200);
        });
    }

    /** Download flagged segments as a CSV file attachment. */
    public function exportCsv(int $videoId): void
    {
        $this->handleRequest(function () use ($videoId) {
            $userId = $this->getAuthenticatedUserId();
            $segments = $this->reportService->exportAsCsv($userId, $videoId);

            $stream = fopen('php://temp', 'r+');
            fputcsv($stream, ['Start Time', 'End Time', 'Type', 'Severity', 'Metric Value']);
            foreach ($segments as $segment) {
                fputcsv($stream, $segment->toCsvRow());
            }
            rewind($stream);
            $csv = stream_get_contents($stream);
            fclose($stream);

            http_response_code(200);
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="report_' . $videoId . '.csv"');
            echo $csv;
            exit;
        });
    }

    /** Return all flagged segments for a video. */
    public function getSegments(int $videoId): void
    {
        $this->handleRequest(function () use ($videoId) {
            $userId = $this->getAuthenticatedUserId();
            $segmentFilters = SegmentFilterDTO::fromQuery($_GET);
            $segments = $this->reportService->getSegments($userId, $videoId, $segmentFilters);
            $this->jsonResponse([
                'data' => array_map(fn(FlaggedSegment $segment) => $segment->toApiArray(), $segments),
            ]);
        });
    }

    /** Return all per-second analysis datapoints for a video. */
    public function getDatapoints(int $videoId): void
    {
        $this->handleRequest(function () use ($videoId) {
            $userId = $this->getAuthenticatedUserId();
            $datapoints = $this->reportService->getDatapoints($userId, $videoId);
            $this->jsonResponse([
                'data' => array_map(fn(AnalysisDatapoint $datapoint) => $datapoint->toApiArray(), $datapoints),
            ]);
        });
    }

    // ──────────────────────────────────────────────
    //  Response assembly (presentation layer)
    // ──────────────────────────────────────────────

    /**
     * Assemble the full JSON payload for a report response.
     *
     * This is presentation logic and belongs exclusively in the controller.
     * toApiArray() is called here on every model — never in the service or DTO.
     */
    private function buildReportPayload(ReportDTO $report): array
    {
        return [
            'video' => $this->buildVideoSection($report),
            'summary' => $this->buildSummarySection($report),
            'segments' => array_map(fn(FlaggedSegment $segment) => $segment->toApiArray(), $report->segments),
            'charts' => $this->buildChartsSection($report),
        ];
    }

    /** Build the video metadata section of the report payload. */
    private function buildVideoSection(ReportDTO $report): array
    {
        return [
            'id' => $report->video->id,
            'originalName' => $report->video->originalName,
            'duration' => $report->video->durationSeconds ?? 0.0,
            'samplingRate' => $report->video->samplingRate,
            'effectiveSamplingRate' => $report->analysisResult?->effectiveSamplingRate ?? $report->video->samplingRate,
            'uploadedAt' => $report->video->createdAt,
            'status' => $report->video->status,
        ];
    }

    /** Build the aggregate summary section including risk levels. */
    private function buildSummarySection(ReportDTO $report): array
    {
        $totalFlash = $report->analysisResult?->totalFlashEvents ?? 0;
        $highestFreq = $report->analysisResult?->highestFlashFrequency ?? 0.0;
        $avgMotion = $report->analysisResult?->averageMotionIntensity ?? 0.0;

        return [
            'totalFlashEvents' => $totalFlash,
            'highestFlashFrequency' => $highestFreq,
            'averageMotionIntensity' => $avgMotion,
            'overallRiskLevel' => $report->calculateRiskLevel(),
            'flashEventsRisk' => RiskLevel::colorForFlashCount($totalFlash),
            'flashFrequencyRisk' => RiskLevel::colorForFlashFrequency($highestFreq),
            'motionIntensityRisk' => RiskLevel::colorForMotionIntensity($avgMotion),
            'samplingRateRisk' => 'safe',
        ];
    }

    /**
     * Split AnalysisDatapoint models into three Chart.js-compatible time series.
     *
     * Arrays of scalar data are acceptable within a single method's local scope.
     * These never cross a service boundary — they are produced and returned in one step.
     */
    private function buildChartsSection(ReportDTO $report): array
    {
        $flashFrequency = [];
        $motionIntensity = [];
        $luminance = [];

        foreach ($report->datapoints as $datapoint) {
            $flashFrequency[] = ['time' => $datapoint->timePoint, 'frequency' => $datapoint->flashFrequency];
            $motionIntensity[] = ['time' => $datapoint->timePoint, 'intensity' => $datapoint->motionIntensity];
            $luminance[] = [
                'time' => $datapoint->timePoint,
                'luminance' => $datapoint->luminance,
                'flashDetected' => $datapoint->flashDetected,
            ];
        }

        return [
            'flashFrequency' => $flashFrequency,
            'motionIntensity' => $motionIntensity,
            'luminance' => $luminance,
        ];
    }
}
