<?php

declare(strict_types=1);

namespace App\Exceptions;

class ForbiddenException extends AppException
{
    /**
     * @param string $message
     * @param int $code
     * @param ?\Throwable $previous
     * @return void
     */
    public function __construct(string $message = 'Forbidden', int $code = 403, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
