<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Repositories\VideoRepository;
use App\Repositories\AnalysisResultRepository;
use App\Repositories\FlaggedSegmentRepository;
use App\Repositories\AnalysisDatapointRepository;
use App\Services\AnalysisService;
use App\Services\FrameExtractor;
use App\Services\FFprobeService;
use App\Services\FlashDetector;
use App\Services\MotionDetector;

$videoRepo = new VideoRepository();
$analysisService = new AnalysisService(
    $videoRepo,
    new AnalysisResultRepository(),
    new FlaggedSegmentRepository(),
    new AnalysisDatapointRepository(),
    new FrameExtractor(),
    new FFprobeService(),
    new FlashDetector(),
    new MotionDetector(),
);

echo "Worker started. Waiting for queued videos...\n";

while (true) {
    $video = $videoRepo->findNextQueued();

    if (!$video) {
        sleep(5);
        continue;
    }

    $videoId = (int) $video['id'];
    echo "Processing video #{$videoId}: {$video['original_name']}\n";

    $videoRepo->updateStatus($videoId, 'processing');
    $videoRepo->updateProgress($videoId, 5, 'Starting analysis...');

    try {
        $analysisService->analyze($videoId, function (int $pct, string $msg) use ($videoRepo, $videoId) {
            $videoRepo->updateProgress($videoId, $pct, $msg);
        });
        $videoRepo->updateProgress($videoId, 100, 'Completed');
        $videoRepo->updateStatus($videoId, 'completed');
        echo "Video #{$videoId} completed.\n";
    } catch (\Throwable $e) {
        $videoRepo->updateError($videoId, $e->getMessage());
        $videoRepo->updateStatus($videoId, 'failed');
        echo "Video #{$videoId} failed: {$e->getMessage()}\n";
    }
}
