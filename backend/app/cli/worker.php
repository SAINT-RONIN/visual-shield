<?php

declare(strict_types=1);

/*
* This idea I learned it from Linux class CRON JOBS.
 * This file is the background worker for video analysis.
 * We need it because checking a video's frames, flash events, and motion can take a while, and we do not want the upload request to sit there waiting for all of that work to finish.
 * Instead, uploads are saved first, marked as queued, and this worker keeps running in the background looking for the next video that is ready to be processed.
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Framework\ServiceRegistry;

echo "Worker started. Waiting for queued videos...\n";

/*
 * This loop is intentionally endless because the worker is meant to behave
 * like a small background service rather than a one-time script.
 * In Docker, it just stays alive, keeps checking for new work, and handles
 * videos one by one as users upload them.
 *
 * The service is resolved lazily inside the loop rather than at startup so
 * that a database connection failure during boot (which is common in Docker
 * when MySQL is still initialising) does not crash the process. Instead the
 * worker waits, retries, and starts processing as soon as the database
 * becomes available.
 */
while (true) {
    try {
        /*
         * Resolving the service here means the first successful DB connection
         * initialises the singleton; every subsequent iteration reuses it.
         * If the DB is not yet reachable, the PDOException is caught below
         * and the worker sleeps before retrying.
         */
        $analysisService = ServiceRegistry::analysisService();

        /*
         * Ask the analysis service for the oldest video that is still waiting
         * in the queue. If nothing is queued right now, it returns null
         * instead of throwing an error, which makes the idle case easy.
         */
        $video = $analysisService->dequeueNextVideo();

        if (!$video) {
            /*
             * When there is no work to do, pause for a few seconds before
             * checking again so the worker does not hammer the database in a
             * tight loop and waste CPU for no reason.
             */
            sleep(5);
            continue;
        }

        $videoId = $video->id;
        echo "Processing video #{$videoId}: {$video->originalName}\n";

        /*
         * processVideo() handles the real pipeline: it moves the video's
         * status to processing, extracts frames, runs the analysis, saves the
         * results, and marks the video as completed if everything goes well.
         */
        $analysisService->processVideo($videoId);
        echo "Video #{$videoId} completed.\n";

    } catch (\PDOException $e) {
        /*
         * Catch database connection errors separately so the worker logs a
         * clear message and keeps retrying rather than crashing. This handles
         * the common Docker startup race where MySQL is not yet reachable when
         * the worker container first comes up.
         */
        echo "Database unavailable: {$e->getMessage()}. Retrying in 5 seconds...\n";
        sleep(5);

    } catch (\Throwable $e) {
        /*
         * Catch any other error so one broken or invalid video does not kill
         * the whole worker process. The service already marks the video as
         * failed, and this catch lets the worker log the problem and keep
         * moving on to the next queued job.
         */
        $label = isset($videoId) ? "Video #{$videoId}" : "Worker";
        echo "{$label} failed: {$e->getMessage()}\n";
        unset($videoId);
    }
}
