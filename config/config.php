<?php
declare(strict_types=1);

/**
 * Configuration file for the Flight Tracker API.
 * Loads environment variables from .env file if present, provides an env() helper function,
 * and returns configuration array for database and JWT settings.
 * Designed to be defensive and work in various environments (local, Railway, etc.).
 */

// Path to the .env file
$envPath = __DIR__ . '/../.env';

// Load environment variables from .env if the file exists
if (file_exists($envPath)) {
    // Use safeLoad to avoid throwing errors if .env is missing or malformed
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->safeLoad();
}

/**
 * Helper function to retrieve environment variables.
 * Checks $_ENV, $_SERVER, and getenv() in order, returning default if not found.
 * Function is guarded to prevent redeclaration errors if included multiple times.
 */
if (!function_exists('env')) {
    function env(string $name, $default = null) {
        // Check $_ENV first (most common for loaded .env vars)
        if (array_key_exists($name, $_ENV)) {
            return $_ENV[$name];
        }
        // Check $_SERVER as fallback
        if (array_key_exists($name, $_SERVER)) {
            return $_SERVER[$name];
        }
        // Finally check getenv()
        $val = getenv($name);
        if ($val !== false && $val !== null) {
            return $val;
        }
        // Return default if not found
        return $default;
    }
}

// Database configuration: Prefer DB_* env vars (local), fallback to Railway's MYSQL* vars
$dbHost = env('DB_HOST', env('MYSQLHOST', '127.0.0.1'));
$dbPort = env('DB_PORT', env('MYSQLPORT', '3306'));
$dbName = env('DB_NAME', env('MYSQLDATABASE', 'railway'));
$dbUser = env('DB_USER', env('MYSQLUSER', 'root'));
$dbPass = env('DB_PASS', env('MYSQLPASSWORD', ''));

// JWT secret: Use JWT_SECRET env var, fallback to default (should be changed in production)
$jwtSecret = env('JWT_SECRET', env('JWT_SECRET', 'change-me-in-prod'));

// Return configuration array
return [
    'database' => [
        'host' => $dbHost,
        'port' => $dbPort,
        'db' => $dbName,
        'username' => $dbUser,
        'password' => $dbPass,
    ],
    'jwt_secret' => $jwtSecret,
];
