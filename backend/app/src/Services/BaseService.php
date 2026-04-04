<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\VideoRepositoryInterface;
use App\Exceptions\NotFoundException;
use App\Models\Video;

/**
 * Shared helper methods for services.
 *
 * This base class keeps the small but repeated lookup rules in one place so
 * each service can stay focused on its own business logic.
 */
abstract class BaseService
{
    /**
     * This is the common "load it or fail" helper the other services use after
     * repository lookups, because null usually means the requested resource
     * should stop the current action instead of being passed around.
     *
     * @param mixed $entity Entity returned from a repository lookup.
     * @param string $message Not-found message to use when the entity is null.
     * @return mixed Loaded entity when it exists.
     */
    protected function findOrFail(mixed $entity, string $message = 'Resource not found'): mixed
    {
        if ($entity === null) {
            throw new NotFoundException($message);
        }

        return $entity;
    }

    /**
     * This checks both existence and ownership in one step, which is something
     * the video and report flows need constantly to avoid exposing another
     * user's uploads by accident.
     *
     * @param VideoRepositoryInterface $repo Video repository used for the ownership check.
     * @param int $userId Authenticated owner ID.
     * @param int $videoId Video ID to load.
     * @return Video Matching video model.
     */
    protected function findUserVideoOrFail(VideoRepositoryInterface $repo, int $userId, int $videoId): Video
    {
        $video = $repo->findByIdAndUserId($videoId, $userId);

        return $this->findOrFail($video, 'Video not found');
    }
}
