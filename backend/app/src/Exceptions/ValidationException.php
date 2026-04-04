<?php

declare(strict_types=1);

namespace App\Exceptions;

class ValidationException extends AppException
{
    /**
     * @param string $message
     * @param int $code
     * @param ?\Throwable $previous
     * @return void
     */
    public function __construct(string $message = 'Validation failed', int $code = 400, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
