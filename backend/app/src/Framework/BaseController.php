<?php

namespace App\Framework;

class BaseController
{
    protected function jsonResponse(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function getJsonBody(): array
    {
        $body = file_get_contents('php://input');
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON body');
        }

        return $data;
    }

    protected function getAuthenticatedUserId(): int
    {
        return AuthMiddleware::authenticate();
    }
}
