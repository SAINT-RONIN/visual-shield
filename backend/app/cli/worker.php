<?php

declare(strict_types=1);

/*
 * This file is the background worker for video analysis.
 * We need it because checking a video's frames, flash events, and motion can take a while, and we do not want the upload request to sit there waiting for all of that work to finish.
 * Instead, uploads are saved first, marked as queued, and this worker keeps running in the background looking for the next video that is ready to be processed.
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Framework\ServiceRegistry;

/*
 * The service registry gives us the fully wired AnalysisService with all of
 * its dependencies already connected, so the worker itself can stay very small
 * and focus only on the job loop.
 */
$analysisService = ServiceRegistry::analysisService();

echo "Worker started. Waiting for queued videos...\n";

/*
 * This loop is intentionally endless because the worker is meant to behave
 * like a small background service rather than a one-time script.
 * In Docker, it just stays alive, keeps checking for new work, and handles
 * videos one by one as users upload them.
 */
while (true) {
    /*
     * Ask the analysis service for the oldest video that is still waiting in
     * the queue. If nothing is queued right now, it returns null instead of
     * throwing an error, which makes the idle case easy to handle.
     */
    $video = $analysisService->dequeueNextVideo();

    if (!$video) {
        /*
         * When there is no work to do, we pause for a few seconds before
         * checking again so the worker does not hammer the database in a tight
         * loop and waste CPU for no reason.
         */
        sleep(5);
        continue;
    }

    $videoId = $video->id;
    echo "Processing video #{$videoId}: {$video->originalName}\n";

    try {
        /*
         * processVideo() handles the real pipeline: it moves the video's
         * status to processing, extracts frames, runs the analysis, saves the
         * results, and marks the video as completed if everything goes well.
         */
        $analysisService->processVideo($videoId);
        echo "Video #{$videoId} completed.\n";
    } catch (\Throwable $e) {
        /*
         * We catch errors here so one broken or invalid video does not kill
         * the whole worker process. The service already marks the video as
         * failed, and this catch lets the worker log the problem and keep
         * moving on to the next queued job.
         */
        echo "Video #{$videoId} failed: {$e->getMessage()}\n";
    }
}
