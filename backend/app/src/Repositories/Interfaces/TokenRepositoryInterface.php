<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Models\Token;

interface TokenRepositoryInterface
{
    /**
     * @param int $userId
     * @param string $token
     * @param string $expiresAt
     * @return void
     */
    public function store(int $userId, string $token, string $expiresAt): void;

    /**
     * @param string $token
     * @return ?Token
     */
    public function findValidToken(string $token): ?Token;

    /**
     * @param string $token
     * @return void
     */
    public function deleteByToken(string $token): void;

    /**
     * @return void
     */
    public function deleteExpiredTokens(): void;
}
