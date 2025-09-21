<?php
// public/debug-pdo.php  — temporary, remove after debugging
header('Content-Type: application/json');

$config = require_once __DIR__ . '/../config/config.php';

echo json_encode([
  'timestamp'     => date('c'),
  'php_sapi'      => PHP_SAPI,
  'php_version'   => phpversion(),
  'pdo_drivers'   => PDO::getAvailableDrivers(),
  'php_modules'   => extension_loaded('pdo_mysql') ? 'pdo_mysql:loaded' : 'pdo_mysql:missing',
  'env'           => $config['env'] ?? 'unknown',
  'db_host'       => $config['database']['host'] ?? null,
  'db_port'       => $config['database']['port'] ?? null,
  'db_name'       => $config['database']['db'] ?? null,
  'db_user'       => $config['database']['username'] ?? null
], JSON_PRETTY_PRINT);
