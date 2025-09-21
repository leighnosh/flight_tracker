<?php declare(strict_types=1);

require_once __DIR__ . '/../utils/Response.php';

class AuthController
{
    // POST /api/auth/register
    public static function register(PDO $pdo, array $body): void
    {
        $email = trim(strtolower($body['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('Invalid email', 400);
        }
        // Check if User already registered
        $existing = User::findByEmail($pdo, $email);
        if ($existing) {
            Response::error('Email already registered', 409);
        }
        
        $password = trim($body['password'] ?? '');
        if (!is_string($password) || strlen($password) < 6) {
            Response::error('Password must be at least 6 characters', 400);
        }

        try{
            $userId = User::create($pdo, $email, $password);
            Response::json(['message' => 'Registered', 'user_id' => $userId], 201);            
        } catch (PDOException $e) {
            Response::error('Database error: ' . $e->getMessage(), 500);
        }
    }

    // POST /api/auth/register
    public static function login(PDO $pdo, array $body, array $config): void
    {
        $email = trim(strtolower($body['email'] ?? ''));
        $password = $body['password'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('Invalid email', 400);
        }
        if (!is_string($password) || $password === '') {
            Response::error('Password required', 400);
        }

        // Fetch user
        $user = User::findByEmail($pdo, $email);
        if (!$user) {
            Response::error('Invalid credentials', 401);
        }

        // Verify password
        if (!User::verifyPassword($password, $user['password_hash'])) {
            Response::error('Invalid credentials', 401);
        }

        $userId = (int)$user['id'];

        // Get JWT secret
        $secret = self::getSecretFromConfig($config);

        try {
            $token = JWT::encode(['user_id' => $userId], $secret, 60*60*4);
            Response::json(['token' => $token, 'expires_in' => 60*60*4, 'user_id' => $userId]);
        } catch (Exception $e) {
            Response::error('Token generation failed', 500);
        }
    }

    private static function getSecretFromConfig(array $config): string {
        if (isset($config['jwt_secret']) && $config['jwt_secret']) return $config['jwt_secret'];
        $env = getenv('JWT_SECRET');
        return $env;
    }
}
