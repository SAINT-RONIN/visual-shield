<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\VideoRepositoryInterface;
use App\Exceptions\NotFoundException;
use App\Models\Video;

abstract class BaseService
{
    /**
     * Return the entity if it is not null, otherwise throw NotFoundException.
     */
    protected function findOrFail(mixed $entity, string $message = 'Resource not found'): mixed
    {
        if ($entity === null) {
            throw new NotFoundException($message);
        }

        return $entity;
    }

    /**
     * Find a video owned by the given user, or throw NotFoundException.
     */
    protected function findUserVideoOrFail(VideoRepositoryInterface $repo, int $userId, int $videoId): Video
    {
        $video = $repo->findByIdAndUserId($videoId, $userId);

        return $this->findOrFail($video, 'Video not found');
    }
}
