<?php

declare(strict_types=1);

namespace App\Exceptions;

class NotFoundException extends AppException
{
    /**
     * @param string $message
     * @param int $code
     * @param ?\Throwable $previous
     * @return void
     */
    public function __construct(string $message = 'Resource not found', int $code = 404, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
