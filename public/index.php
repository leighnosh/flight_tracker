<?php declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
$config = require __DIR__ . '/../config/config.php';
require __DIR__ . '/../db/db.php'; // sets $pdo

// autoload from src/
spl_autoload_register(function ($class) {
    $base = __DIR__ . '/../src/';
    foreach (['controllers','models','utils','middleware'] as $dir) {
        $file = $base . $dir . '/' . $class . '.php';
        if (file_exists($file)) require $file;
    }
});

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$path   = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/') ?: '/';
$body   = in_array($method, ['POST','PUT','PATCH'])
    ? (json_decode(file_get_contents('php://input'), true) ?: [])
    : [];

// ---- Routes ----

if ($method === 'GET' && $path === '/') {
    echo json_encode(['status' => 'ok', 'message' => 'Assignment done..']);
    exit;
}

// Health check
if ($method === 'GET' && $path === '/ping') {
    echo json_encode(['status' => 'ok', 'env_db' => $config['database']['db'] ?? null]);
    exit;
}

// Flights
if ($method === 'GET' && $path === '/api/flights') {
    FlightController::search($pdo);
    exit;
}

// Auth
if ($method === 'POST' && $path === '/api/auth/register') {
    AuthController::register($pdo, $body);
    exit;
}
if ($method === 'POST' && $path === '/api/auth/login') {
    AuthController::login($pdo, $body, $config);
    exit;
}

// Bookings (authenticated)
if ($method === 'POST' && $path === '/api/bookings') {
    $auth = AuthMiddleware::requireAuth($config);
    BookingController::create($pdo, $auth, $body);
    exit;
}
if ($method === 'GET' && preg_match('#^/api/bookings/(\d+)$#', $path, $m)) {
    $auth = AuthMiddleware::requireAuth($config);
    BookingController::get($pdo, $auth, (int)$m[1]);
    exit;
}

// Fallback
http_response_code(404);
echo json_encode(['error' => 'Route not found','path'=>$path]);
