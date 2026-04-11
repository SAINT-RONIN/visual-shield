<?php

declare(strict_types=1);

namespace App\Framework;

use App\Exceptions\AppException;
use App\Exceptions\ForbiddenException;

class BaseController
{
    /** HTTP status codes that RuntimeException codes are allowed to map to directly. */
    private const ALLOWED_HTTP_ERROR_CODES = [400, 401, 403, 404];

    /**
     * Execute a controller action with standardised error handling.
     *
     * Catches domain exceptions and maps them to HTTP status codes:
     *   - AppException (and subclasses) â†’ uses the exception's code directly
     *   - InvalidArgumentException â†’ 400 Bad Request (legacy)
     *   - RuntimeException â†’ uses the exception's code (allowlist) or 500 (legacy)
     *   - Any other Throwable â†’ 500 Internal Server Error
     *
     * This eliminates the identical try/catch blocks that were duplicated
     * across every controller method.
     *
     * @param callable $action Controller action to execute.
     * @return void
     */
    protected function handleRequest(callable $action): void
    {
        try {
            $action();
        } catch (AppException $e) {
            $this->errorResponse($e->getCode(), $e->getMessage());
        } catch (\InvalidArgumentException $e) {
            $this->errorResponse(400, $e->getMessage());
        } catch (\PDOException $e) {
            $this->errorResponse(503, 'Service temporarily unavailable');
        } catch (\RuntimeException $e) {
            $code = $this->mapRuntimeExceptionCode($e);
            $message = $code === 500 ? 'Internal server error' : $e->getMessage();
            $this->errorResponse($code, $message);
        } catch (\Throwable $e) {
            $this->errorResponse(500, 'Internal server error');
        }
    }

    /**
     * Send a JSON response with the given status code and terminate.
     *
     * @param mixed $data Data to JSON-encode.
     * @param int $status HTTP status code to send.
     * @return void
     */
    protected function jsonResponse(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Decode the request body as JSON, throwing on invalid or empty input.
     *
     * @return array<string, mixed>|list<mixed> Decoded JSON payload.
     */
    protected function getJsonBody(): array
    {
        $body = file_get_contents('php://input');

        if ($body === false || trim($body) === '') {
            return [];
        }

        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON body');
        }

        if (!is_array($data)) {
            throw new \InvalidArgumentException('JSON body must be an object or array');
        }

        return $data;
    }

    /**
     * Authenticate the request and return the user's ID.
     *
     * @return int Authenticated user ID.
     */
    protected function getAuthenticatedUserId(): int
    {
        return AuthMiddleware::authenticate();
    }

    /**
     * Get the role of the currently authenticated user.
     *
     * @return string Authenticated user's role.
     */
    protected function getAuthenticatedUserRole(): string
    {
        return AuthMiddleware::getAuthenticatedUserRole();
    }

    /**
     * Require a specific role, throwing 403 if the user doesn't have it.
     *
     * @param string $role Required role name.
     * @return void
     */
    protected function requireRole(string $role): void
    {
        if ($this->getAuthenticatedUserRole() !== $role) {
            throw new ForbiddenException('Forbidden: insufficient permissions');
        }
    }

    /**
     * Send a JSON error response with a consistent structure.
     *
     * @param int $code HTTP status code to send.
     * @param string $message Human-readable error message.
     * @return void
     */
    protected function errorResponse(int $code, string $message): void
    {
        $this->jsonResponse(['error' => ['code' => $code, 'message' => $message]], $code);
    }

    /**
     * Map a RuntimeException to an HTTP status code.
     *
     * RuntimeExceptions with code 401 or 404 keep their code.
     * Everything else becomes 500.
     *
     * @param \RuntimeException $e Runtime exception to translate.
     * @return int HTTP status code to send.
     */
    private function mapRuntimeExceptionCode(\RuntimeException $e): int
    {
        $exceptionCode = $e->getCode();

        if (\in_array($exceptionCode, self::ALLOWED_HTTP_ERROR_CODES, true)) {
            return $exceptionCode;
        }

        return 500;
    }
}
