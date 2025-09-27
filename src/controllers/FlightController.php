<?php

declare(strict_types=1);

require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../models/Flight.php';

/**
 * FlightController handles flight search operations.
 * Provides methods for searching available flights based on criteria.
 */
class FlightController
{
    /**
     * Searches for flights based on query parameters.
     * Validates input parameters and retrieves matching flights from the database.
     *
     * Query parameters: origin, destination, date, passengers, limit, offset, sort.
     *
     * @param PDO $pdo Database connection.
     */
    public static function search(PDO $pdo): void
    {
        // Extract and sanitize query parameters
        $origin = isset($_GET['origin']) ? strtoupper(trim($_GET['origin'])) : null;
        $destination = isset($_GET['destination']) ? strtoupper(trim($_GET['destination'])) : null;
        $dateRaw = $_GET['date'] ?? null;
        $passengers = isset($_GET['passengers']) ? intval($_GET['passengers']) : 1;
        $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 50;
        $offset = isset($_GET['offset']) ? max(0, intval($_GET['offset'])) : 0;
        $sort = $_GET['sort'] ?? 'price';

        // Validate origin (3-letter IATA code)
        if (!$origin || strlen($origin) !== 3) {
            Response::error('Invalid or missing origin (3-letter IATA).', 400);
        }
        // Validate destination (3-letter IATA code)
        if (!$destination || strlen($destination) !== 3) {
            Response::error('Invalid or missing destination (3-letter IATA).', 400);
        }
        // Validate date presence
        if (!$dateRaw) {
            Response::error('Missing date (YYYY-MM-DD).', 400);
        }
        // Validate date format
        $dt = DateTime::createFromFormat('Y-m-d', $dateRaw);
        if (!$dt) {
            Response::error('Invalid date format. Use YYYY-MM-DD.', 400);
        }
        // Validate passengers count
        if ($passengers <= 0) {
            Response::error('Passengers must be a positive integer.', 400);
        }

        // Ensure date is in UTC
        $dt->setTimezone(new DateTimeZone('UTC'));

        // Define allowed sort options
        $allowedSort = ['price' => 'price ASC', 'departure' => 'departure ASC'];
        $orderBy = $allowedSort[$sort] ?? $allowedSort['price'];

        try {
            // Perform flight search
            $flights = Flight::search($pdo, $origin, $destination, $dateRaw, $passengers, $limit, $offset, $orderBy);
            Response::json(['data' => $flights, 'meta' => ['count' => count($flights)]]);
        } catch (PDOException $e) {
            Response::error('Database error: ' . $e->getMessage(), 500);
        }
    }
}
