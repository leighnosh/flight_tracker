<?php declare(strict_types=1);

/**
 * User model handles user account operations.
 * Provides methods for creating users, finding by email, and password verification.
 */
class User
{
    /**
     * Create a new user account.
     * Hashes the password and inserts the user into the database.
     *
     * @param PDO $pdo Database connection.
     * @param string $email User's email address.
     * @param string $password Plain text password (will be hashed).
     * @return int The new user's ID.
     */
    public static function create(PDO $pdo, string $email, string $password): int
    {
        // Hash the password securely
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (email, password_hash) VALUES (:email, :hash)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => $email, ':hash' => $passwordHash]);
        return (int)$pdo->lastInsertId();
    }

    /**
     * Find a user by email address.
     *
     * @param PDO $pdo Database connection.
     * @param string $email Email to search for.
     * @return array|null User data or null if not found.
     */
    public static function findByEmail(PDO $pdo, string $email): ?array
    {
        $sql = "SELECT id, email, password_hash, created_at FROM users WHERE email = :email LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Verify a password against its hash.
     *
     * @param string $password Plain text password.
     * @param string $hash Hashed password from database.
     * @return bool True if password matches.
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}