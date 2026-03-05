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

    try {
        $analysisService->analyze($videoId);
        $videoRepo->updateStatus($videoId, 'completed');
        echo "Video #{$videoId} completed.\n";
    } catch (\Throwable $e) {
        $videoRepo->updateStatus($videoId, 'failed');
        echo "Video #{$videoId} failed: {$e->getMessage()}\n";
    }
}
