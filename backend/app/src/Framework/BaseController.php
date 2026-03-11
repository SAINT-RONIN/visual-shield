<?php

declare(strict_types=1);

namespace App\Framework;

use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Exceptions\UnauthorizedException;
use App\Exceptions\ValidationException;

class BaseController
{
    /** HTTP status codes that RuntimeException codes are allowed to map to directly. */
    private const ALLOWED_HTTP_ERROR_CODES = [400, 401, 403, 404];

    /**
     * Execute a controller action with standardised error handling.
     *
     * Catches domain exceptions and maps them to HTTP status codes:
     *   - ValidationException → 400 Bad Request
     *   - UnauthorizedException → 401 Unauthorized
     *   - ForbiddenException → 403 Forbidden
     *   - NotFoundException → 404 Not Found
     *   - InvalidArgumentException → 400 Bad Request (legacy)
     *   - RuntimeException → uses the exception's code (allowlist) or 500 (legacy)
     *   - Any other Throwable → 500 Internal Server Error
     *
     * This eliminates the identical try/catch blocks that were duplicated
     * across every controller method.
     */
    protected function handleRequest(callable $action): void
    {
        try {
            $action();
        } catch (ValidationException $e) {
            $this->errorResponse(400, $e->getMessage());
        } catch (UnauthorizedException $e) {
            $this->errorResponse(401, $e->getMessage());
        } catch (ForbiddenException $e) {
            $this->errorResponse(403, $e->getMessage());
        } catch (NotFoundException $e) {
            $this->errorResponse(404, $e->getMessage());
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

    /** Send a JSON response with the given status code and terminate. */
    protected function jsonResponse(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /** Decode the request body as JSON, throwing on invalid or empty input. */
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

    /** Authenticate the request and return the user's ID. */
    protected function getAuthenticatedUserId(): int
    {
        return AuthMiddleware::authenticate();
    }

    /** Get the role of the currently authenticated user. */
    protected function getAuthenticatedUserRole(): string
    {
        return AuthMiddleware::getAuthenticatedUserRole();
    }

    /** Require a specific role, throwing 403 if the user doesn't have it. */
    protected function requireRole(string $role): void
    {
        if ($this->getAuthenticatedUserRole() !== $role) {
            throw new ForbiddenException('Forbidden: insufficient permissions');
        }
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

        if (in_array($exceptionCode, self::ALLOWED_HTTP_ERROR_CODES, true)) {
            return $exceptionCode;
        }

        return 500;
    }
}
