<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Framework\ServiceRegistry;

$videoRepo = ServiceRegistry::videoRepository();
$analysisService = ServiceRegistry::analysisService();

echo "Worker started. Waiting for queued videos...\n";

while (true) {
    $video = $videoRepo->findNextQueued();

    if (!$video) {
        sleep(5);
        continue;
    }

    $videoId = $video->id;
    echo "Processing video #{$videoId}: {$video->originalName}\n";

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
