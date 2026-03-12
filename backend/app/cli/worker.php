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

    try {
        $analysisService->processVideo($videoId);
        echo "Video #{$videoId} completed.\n";
    } catch (\Throwable $e) {
        echo "Video #{$videoId} failed: {$e->getMessage()}\n";
    }
}
