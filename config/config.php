<?php
declare(strict_types=1);

// config/config.php
// Defensive, env-agnostic config that won't throw if .env missing,
// and avoids redeclaring helper functions.

$envPath = __DIR__ . '/../.env';

if (file_exists($envPath)) {
    // Use safeLoad so it won't throw if .env is missing
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->safeLoad();
}

/**
 * env helper
 * - Checks $_ENV, $_SERVER, getenv()
 * - Returns $default when not found
 * Guarded with function_exists to avoid "Cannot redeclare" errors.
 */
if (!function_exists('env')) {
    function env(string $name, $default = null) {
        // Prefer superglobals, then getenv()
        if (array_key_exists($name, $_ENV)) {
            return $_ENV[$name];
        }
        if (array_key_exists($name, $_SERVER)) {
            return $_SERVER[$name];
        }
        $val = getenv($name);
        if ($val !== false && $val !== null) {
            return $val;
        }
        return $default;
    }
}

// Prefer DB_* (local), fallback to Railway's MYSQL* vars
$dbHost = env('DB_HOST', env('MYSQLHOST', '127.0.0.1'));
$dbPort = env('DB_PORT', env('MYSQLPORT', '3306'));
$dbName = env('DB_NAME', env('MYSQLDATABASE', 'railway'));
$dbUser = env('DB_USER', env('MYSQLUSER', 'root'));
$dbPass = env('DB_PASS', env('MYSQLPASSWORD', ''));

// JWT secret (set JWT_SECRET in Railway variables)
$jwtSecret = env('JWT_SECRET', env('JWT_SECRET', 'change-me-in-prod'));

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
