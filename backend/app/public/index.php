<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Framework\ServiceRegistry;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;

// CORS headers
$corsOrigin = getenv('CORS_ORIGIN') ?: '*';
header("Access-Control-Allow-Origin: {$corsOrigin}");
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$dispatcher = simpleDispatcher(function (RouteCollector $r) {
    // Health
    $r->addRoute('GET', '/api/health', ['health', 'status']);
    $r->addRoute('GET', '/api/health/db', ['health', 'db']);

    // Config (public)
    $r->addRoute('GET', '/api/config', ['configController', 'getConfig']);

    // Auth
    $r->addRoute('POST', '/api/session', ['authController', 'login']);
    $r->addRoute('DELETE', '/api/session', ['authController', 'logout']);
    $r->addRoute('POST', '/api/auth/register', ['authController', 'register']);
    $r->addRoute('POST', '/api/auth/login', ['authController', 'login']);
    $r->addRoute('POST', '/api/auth/logout', ['authController', 'logout']);
    $r->addRoute('GET', '/api/users/me', ['authController', 'getProfile']);
    $r->addRoute('PUT', '/api/users/me', ['authController', 'updateProfile']);
    $r->addRoute('GET', '/api/users', ['adminController', 'listUsers']);
    $r->addRoute('PATCH', '/api/users/{id:\d+}', ['adminController', 'updateUserRole']);

    // Videos
    $r->addRoute('POST', '/api/videos', ['videoController', 'upload']);
    $r->addRoute('GET', '/api/videos', ['videoController', 'getAll']);
    $r->addRoute('GET', '/api/videos/{id:\d+}', ['videoController', 'getOne']);
    $r->addRoute('PUT', '/api/videos/{id:\d+}', ['videoController', 'update']);
    $r->addRoute('PATCH', '/api/videos/{id:\d+}', ['videoController', 'update']);
    $r->addRoute('DELETE', '/api/videos/{id:\d+}', ['videoController', 'delete']);
    $r->addRoute('PUT', '/api/videos/{id:\d+}/reanalyze', ['videoController', 'reanalyze']);
    $r->addRoute('GET', '/api/videos/{id:\d+}/stream', ['videoController', 'stream']);

    // Reports
    $r->addRoute('GET', '/api/videos/{id:\d+}/report', ['reportController', 'getReport']);
    $r->addRoute('GET', '/api/videos/{id:\d+}/report/json', ['reportController', 'exportJson']);
    $r->addRoute('GET', '/api/videos/{id:\d+}/report/csv', ['reportController', 'exportCsv']);
    $r->addRoute('GET', '/api/videos/{id:\d+}/segments', ['reportController', 'getSegments']);
    $r->addRoute('GET', '/api/videos/{id:\d+}/datapoints', ['reportController', 'getDatapoints']);

    // Admin
    $r->addRoute('GET', '/api/admin/users', ['adminController', 'listUsers']);
    $r->addRoute('PATCH', '/api/admin/users/{id:\d+}/role', ['adminController', 'updateUserRole']);
});

$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = strtok($_SERVER['REQUEST_URI'], '?');

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        http_response_code(404);
        echo json_encode(['error' => ['code' => 404, 'message' => 'Route not found']]);
        break;

    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        http_response_code(405);
        echo json_encode(['error' => ['code' => 405, 'message' => 'Method not allowed']]);
        break;

    case FastRoute\Dispatcher::FOUND:
        [$_, $handler, $vars] = $routeInfo;
        [$registryMethod, $method] = $handler;

        // Health routes are inline (no controller)
        if ($registryMethod === 'health') {
            if ($method === 'status') {
                echo json_encode(['status' => 'ok']);
            } elseif ($method === 'db') {
                try {
                    \App\Framework\Database::getInstance();
                    echo json_encode(['database' => 'connected']);
                } catch (\Throwable $e) {
                    http_response_code(500);
                    echo json_encode(['error' => ['code' => 500, 'message' => 'Database connection failed']]);
                }
            }
            break;
        }

        // All other routes go through ServiceRegistry
        $controller = ServiceRegistry::$registryMethod();
        $args = array_map('intval', array_values($vars));
        $controller->$method(...$args);
        break;
}
