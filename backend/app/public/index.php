<?php

require_once __DIR__ . '/../vendor/autoload.php';

// CORS headers
$corsOrigin = getenv('CORS_ORIGIN') ?: '*';
header("Access-Control-Allow-Origin: {$corsOrigin}");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$router = new \Bramus\Router\Router();

$router->get('/api/health', function () {
    echo json_encode(['status' => 'ok']);
});

$router->get('/api/health/db', function () {
    try {
        \App\Framework\Database::getInstance();
        echo json_encode(['database' => 'connected']);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => ['code' => 500, 'message' => 'Database connection failed']]);
    }
});

// Auth routes
$auth = new \App\Controllers\AuthController();

$router->post('/api/register', function () use ($auth) {
    $auth->register();
});

$router->post('/api/login', function () use ($auth) {
    $auth->login();
});

$router->post('/api/logout', function () use ($auth) {
    $auth->logout();
});

$router->get('/api/users/me', function () use ($auth) {
    $auth->getProfile();
});

$router->put('/api/users/me', function () use ($auth) {
    $auth->updateProfile();
});

$router->run();
