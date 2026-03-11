<?php

namespace App\Framework;

class BaseController
{
    /**
     * Execute a controller action with standardised error handling.
     *
     * Catches domain exceptions and maps them to HTTP status codes:
     *   - InvalidArgumentException → 400 Bad Request
     *   - RuntimeException with code 404 → 404 Not Found
     *   - RuntimeException (other) → uses the exception's code or 500
     *   - Any other Throwable → 500 Internal Server Error
     *
     * This eliminates the identical try/catch blocks that were duplicated
     * across every controller method.
     */
    protected function handleRequest(callable $action): void
    {
        try {
            $action();
        } catch (\InvalidArgumentException $e) {
            $this->errorResponse(400, $e->getMessage());
        } catch (\RuntimeException $e) {
            $code = $this->mapRuntimeExceptionCode($e);
            $message = $code === 500 ? 'Internal server error' : $e->getMessage();
            $this->errorResponse($code, $message);
        } catch (\Throwable $e) {
            $this->errorResponse(500, 'Internal server error');
        }
    }

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

    /** Send a JSON error response with a consistent structure. */
    protected function errorResponse(int $code, string $message): void
    {
        $this->jsonResponse(['error' => ['code' => $code, 'message' => $message]], $code);
    }

    /**
     * Map a RuntimeException to an HTTP status code.
     *
     * RuntimeExceptions with code 401 or 404 keep their code.
     * Everything else becomes 500.
     */
    private function mapRuntimeExceptionCode(\RuntimeException $e): int
    {
        $exceptionCode = $e->getCode();

        if ($exceptionCode === 401 || $exceptionCode === 404) {
            return $exceptionCode;
        }

        return 500;
    }
}
