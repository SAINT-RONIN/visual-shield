<?php

declare(strict_types=1);

namespace App\Exceptions;

class AppException extends \RuntimeException
{
    /**
     * @param string $message
     * @param int $code
     * @param ?\Throwable $previous
     * @return void
     */
    public function __construct(string $message = 'Application error', int $code = 500, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
