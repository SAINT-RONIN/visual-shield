<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

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

// Config routes (public, no auth required)
$router->get('/api/config', function () {
    \App\Framework\ServiceRegistry::configController()->getConfig();
});

// Auth routes
$router->post('/api/auth/register', function () {
    \App\Framework\ServiceRegistry::authController()->register();
});

$router->post('/api/auth/login', function () {
    \App\Framework\ServiceRegistry::authController()->login();
});

$router->post('/api/auth/logout', function () {
    \App\Framework\ServiceRegistry::authController()->logout();
});

$router->get('/api/users/me', function () {
    \App\Framework\ServiceRegistry::authController()->getProfile();
});

$router->put('/api/users/me', function () {
    \App\Framework\ServiceRegistry::authController()->updateProfile();
});

// Video routes
$router->post('/api/videos', function () {
    \App\Framework\ServiceRegistry::videoController()->upload();
});

$router->get('/api/videos', function () {
    \App\Framework\ServiceRegistry::videoController()->getAll();
});

$router->get('/api/videos/(\d+)', function ($id) {
    \App\Framework\ServiceRegistry::videoController()->getOne((int) $id);
});

$router->patch('/api/videos/(\d+)', function ($id) {
    \App\Framework\ServiceRegistry::videoController()->update((int) $id);
});

$router->delete('/api/videos/(\d+)', function ($id) {
    \App\Framework\ServiceRegistry::videoController()->delete((int) $id);
});

$router->put('/api/videos/(\d+)/reanalyze', function ($id) {
    \App\Framework\ServiceRegistry::videoController()->reanalyze((int) $id);
});

$router->get('/api/videos/(\d+)/stream', function ($id) {
    \App\Framework\ServiceRegistry::videoController()->stream((int) $id);
});

// Report routes
$router->get('/api/videos/(\d+)/report', function ($id) {
    \App\Framework\ServiceRegistry::reportController()->getReport((int) $id);
});

$router->get('/api/videos/(\d+)/report/json', function ($id) {
    \App\Framework\ServiceRegistry::reportController()->exportJson((int) $id);
});

$router->get('/api/videos/(\d+)/report/csv', function ($id) {
    \App\Framework\ServiceRegistry::reportController()->exportCsv((int) $id);
});

$router->get('/api/videos/(\d+)/segments', function ($id) {
    \App\Framework\ServiceRegistry::reportController()->getSegments((int) $id);
});

$router->get('/api/videos/(\d+)/datapoints', function ($id) {
    \App\Framework\ServiceRegistry::reportController()->getDatapoints((int) $id);
});

// Admin routes
$router->get('/api/admin/users', function () {
    \App\Framework\ServiceRegistry::adminController()->listUsers();
});

$router->patch('/api/admin/users/(\d+)/role', function ($id) {
    \App\Framework\ServiceRegistry::adminController()->updateUserRole((int) $id);
});

$router->run();
