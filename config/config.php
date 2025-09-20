<?php

// Load environment variables
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

return [
    'database' => [
        'host' => $_ENV['DB_HOST'],
        'port' => $_ENV['DB_PORT'],
        'db' => $_ENV['DB_NAME'],
        'username' => $_ENV['DB_USER'],
        'password' => $_ENV['DB_PASS'],
    ],
    'password' => $_ENV['JWT_SECRET']
];