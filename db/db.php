<?php

$config = require __DIR__ . '/../config/config.php';

try {
    // Create new PDO instance
    $pdo = new PDO('mysql:host=' . $config['database']['host'] . ';port=' . $config['database']['port'] . ';dbname=' . $config['database']['db'], $config['database']['username'], $config['database']['password']);
    // echo "Connected to database {$config['database']['db']} with user {$config['database']['username']}";
} catch (PDOException $e) {
    echo "Error connecting to database: {$e->getMessage()}";
}