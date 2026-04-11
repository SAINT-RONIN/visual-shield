<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Interfaces\VideoRepositoryInterface;
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
    // Common "load it or fail" helper — null means stop the action, not pass it around.
    protected function findOrFail(mixed $entity, string $message = 'Resource not found'): mixed
    {
        if ($entity === null) {
            throw new NotFoundException($message);
        }

        return $entity;
    }

    // Checks existence and ownership in one step to avoid exposing another user's uploads.
    protected function findUserVideoOrFail(VideoRepositoryInterface $repo, int $userId, int $videoId): Video
    {
        $video = $repo->findByIdAndUserId($videoId, $userId);

        return $this->findOrFail($video, 'Video not found');
    }
}
