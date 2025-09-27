<?php declare(strict_types=1);

require_once __DIR__ . '/../utils/Response.php';

/**
 * AuthController handles user authentication operations.
 * Provides methods for user registration and login, including JWT token generation.
 */
class AuthController
{
    /**
     * Handles user registration.
     * Validates email and password, checks for existing users, and creates a new user account.
     *
     * @param PDO $pdo Database connection.
     * @param array $body Request body containing 'email' and 'password'.
     */
    public static function register(PDO $pdo, array $body): void
    {
        // Validate and normalize email
        $email = trim(strtolower($body['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('Invalid email', 400);
        }
        // Check if user already exists
        $existing = User::findByEmail($pdo, $email);
        if ($existing) {
            Response::error('Email already registered', 409);
        }

        // Validate password
        $password = trim($body['password'] ?? '');
        if (!is_string($password) || strlen($password) < 6) {
            Response::error('Password must be at least 6 characters', 400);
        }

        try {
            // Create new user
            $userId = User::create($pdo, $email, $password);
            Response::json(['message' => 'Registered', 'user_id' => $userId], 201);
        } catch (PDOException $e) {
            Response::error('Database error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Handles user login.
     * Validates credentials, verifies password, and generates a JWT token.
     *
     * @param PDO $pdo Database connection.
     * @param array $body Request body containing 'email' and 'password'.
     * @param array $config Configuration array containing JWT secret.
     */
    public static function login(PDO $pdo, array $body, array $config): void
    {
        // Validate email
        $email = trim(strtolower($body['email'] ?? ''));
        $password = $body['password'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('Invalid email', 400);
        }
        if (!is_string($password) || $password === '') {
            Response::error('Password required', 400);
        }

        // Find user by email
        $user = User::findByEmail($pdo, $email);
        if (!$user) {
            Response::error('Invalid credentials', 401);
        }

        // Verify password against stored hash
        if (!User::verifyPassword($password, $user['password_hash'])) {
            Response::error('Invalid credentials', 401);
        }

        $userId = (int)$user['id'];

        // Retrieve JWT secret from config
        $secret = self::getSecretFromConfig($config);

        try {
            // Generate JWT token with 4-hour expiration
            $token = JWT::encode(['user_id' => $userId], $secret, 60*60*4);
            Response::json(['token' => $token, 'expires_in' => 60*60*4, 'user_id' => $userId]);
        } catch (Exception $e) {
            Response::error('Token generation failed', 500);
        }
    }

    /**
     * Retrieves the JWT secret from configuration or environment.
     *
     * @param array $config Configuration array.
     * @return string The JWT secret.
     */
    private static function getSecretFromConfig(array $config): string {
        // Prefer config value, fallback to environment variable
        if (isset($config['jwt_secret']) && $config['jwt_secret']) return $config['jwt_secret'];
        $env = getenv('JWT_SECRET');
        return $env;
    }
}
