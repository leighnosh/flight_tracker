<?php
// public/index.php
declare(strict_types=1);

// Composer autoload
require_once __DIR__ . '/../vendor/autoload.php';

// Load config
$config = require __DIR__ . '/../config/config.php';

// Simple autoloader for src/ files
spl_autoload_register(function ($class) {
    $base = __DIR__ . '/../src/';
    $paths = [
        $base . 'controllers/',
        $base . 'models/',
        $base . 'utils/',
    ];
    foreach ($paths as $p) {
        $file = $p . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Always respond JSON
header('Content-Type: application/json; charset=utf-8');

// Parse request
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = rtrim($uri, '/');
if ($path === '') $path = '/';

// Health check
if ($path === '/ping' || $path === '/') {
    echo json_encode(['status' => 'ok', 'env_db' => $config['database']['db'] ?? null]);
    exit;
}


// GET /api/flights
if ($path === '/api/flights' && $method === 'GET') {
    require_once __DIR__ . '/../db/db.php'; // sets $pdo
    require_once __DIR__ . '/../src/controllers/FlightController.php';
    FlightController::search($pdo);
    exit;
}

// POST /api/auth/register
if ($path === '/api/auth/register' && $method === 'POST') {
    require_once __DIR__ . '/../db/db.php';
    require_once __DIR__ . '/../src/controllers/AuthController.php';
    $body = json_decode(file_get_contents('php://input'), true) ?: [];
    AuthController::register($pdo, $body, $config);
    exit;
}

// Fallback for unknown routes
http_response_code(404);
echo json_encode(['error' => 'Route not found', 'path' => $path]);
exit;
