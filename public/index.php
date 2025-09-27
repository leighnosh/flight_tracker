<?php declare(strict_types=1);

/**
 * Main entry point for the Flight Tracker API.
 * Handles HTTP requests, routes them to appropriate controllers, and manages autoloading.
 */

// Load Composer dependencies
require __DIR__ . '/../vendor/autoload.php';
// Load configuration settings
$config = require __DIR__ . '/../config/config.php';
// Load database connection (sets $pdo)
require __DIR__ . '/../db/db.php'; // sets $pdo

// Autoload classes from src/ directories
spl_autoload_register(function ($class) {
    $base = __DIR__ . '/../src/';
    // Check in controllers, models, utils, and middleware directories
    foreach (['controllers','models','utils','middleware'] as $dir) {
        $file = $base . $dir . '/' . $class . '.php';
        if (file_exists($file)) require $file;
    }
});

// Set response content type to JSON
header('Content-Type: application/json; charset=utf-8');

// Parse request details
$method = $_SERVER['REQUEST_METHOD'];
$path   = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/') ?: '/';
// Parse JSON body for POST/PUT/PATCH requests
$body   = in_array($method, ['POST','PUT','PATCH'])
    ? (json_decode(file_get_contents('php://input'), true) ?: [])
    : [];

// ---- Routes ----

// Root endpoint - basic status check
if ($method === 'GET' && $path === '/') {
    echo json_encode(['status' => 'ok', 'message' => 'Assignment done..']);
    exit;
}

// Health check endpoint - returns database name if configured
if ($method === 'GET' && $path === '/ping') {
    echo json_encode(['status' => 'ok', 'env_db' => $config['database']['db'] ?? null]);
    exit;
}

// Flights API - search flights
if ($method === 'GET' && $path === '/api/flights') {
    FlightController::search($pdo);
    exit;
}

// Auth API - user registration
if ($method === 'POST' && $path === '/api/auth/register') {
    AuthController::register($pdo, $body);
    exit;
}
// Auth API - user login
if ($method === 'POST' && $path === '/api/auth/login') {
    AuthController::login($pdo, $body, $config);
    exit;
}

// Bookings API (requires authentication) - create booking
if ($method === 'POST' && $path === '/api/bookings') {
    $auth = AuthMiddleware::requireAuth($config);
    BookingController::create($pdo, $auth, $body);
    exit;
}
// Bookings API (requires authentication) - get specific booking by ID
if ($method === 'GET' && preg_match('#^/api/bookings/(\d+)$#', $path, $m)) {
    $auth = AuthMiddleware::requireAuth($config);
    BookingController::get($pdo, $auth, (int)$m[1]);
    exit;
}

// Fallback for unmatched routes
http_response_code(404);
echo json_encode(['error' => 'Route not found','path'=>$path]);
