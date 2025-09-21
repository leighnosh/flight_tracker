<?php declare(strict_types=1);

class User
{
    public static function create(PDO $pdo, string $email, string $password): int
    {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (email, password_hash) VALUES (:email, :hash)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => $email, ':hash' => $passwordHash]);
        return (int)$pdo->lastInsertId();
    }  

    public static function findByEmail(PDO $pdo, string $email): ?array
    {
        $sql = "SELECT id, email, password_hash, created_at FROM users WHERE email = :email LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;        
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}