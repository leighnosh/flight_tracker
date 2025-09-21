<?php
declare(strict_types=1);

$config = require __DIR__ . '/config/config.php';

// Connect using PDO
$dsn = "mysql:host={$config['database']['host']};port={$config['database']['port']};dbname={$config['database']['db']};charset=utf8mb4";
try {
    $pdo = new PDO($dsn, $config['database']['username'], $config['database']['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    fwrite(STDERR, "DB connection failed: " . $e->getMessage() . PHP_EOL);
    exit(1);
}

// Load and run schema.sql
$schemaPath = __DIR__ . '/db/schema.sql';
if (!file_exists($schemaPath)) {
    fwrite(STDERR, "Schema file not found at $schemaPath\n");
    exit(1);
}

$sql = file_get_contents($schemaPath);
try {
    $pdo->exec($sql);
    echo "Schema applied successfully.\n";
} catch (PDOException $e) {
    fwrite(STDERR, "Schema exec failed: " . $e->getMessage() . PHP_EOL);
    exit(1);
}

// Optionally run a seed file if present
$seedPath = __DIR__ . '/db/seed.php';
if (file_exists($seedPath)) {
    require $seedPath;
    echo "Seed data applied.\n";
}
