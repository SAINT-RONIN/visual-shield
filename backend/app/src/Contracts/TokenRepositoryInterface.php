<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Token;

interface TokenRepositoryInterface
{
    public function store(int $userId, string $token, string $expiresAt): void;

    public function findValidToken(string $token): ?Token;

    public function deleteByToken(string $token): void;

    public function deleteExpiredTokens(): void;
}
