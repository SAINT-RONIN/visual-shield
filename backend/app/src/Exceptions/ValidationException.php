<?php

declare(strict_types=1);

namespace App\Exceptions;

class ValidationException extends \RuntimeException
{
    public function __construct(string $message = 'Validation failed', int $code = 400, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
