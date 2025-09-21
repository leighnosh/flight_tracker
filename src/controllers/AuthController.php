<?php declare(strict_types=1);

require_once __DIR__ . '/../utils/Response.php';

class AuthController
{
    // POST /api/auth/register
    public static function register(PDO $pdo, array $body, array $config): void
    {
        $email = trim(strtolower($body['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('Invalid email, 400');
        }
        // Check if User already registered
        $existing = User::findByEmail($pdo, $email);
        if ($existing) {
            Response::error('Email already registered', 409);
        }
        
        $password = trim(strtolower($body['password'] ?? ''));
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
}
