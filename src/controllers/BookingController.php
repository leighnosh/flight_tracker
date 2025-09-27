<?php declare(strict_types=1);

require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../models/Booking.php';

/**
 * BookingController handles flight booking operations.
 * Provides methods for creating bookings and retrieving user-specific bookings.
 */
class BookingController
{

    /**
     * Creates a new booking for a flight.
     * Validates input data, checks availability, and creates the booking with passengers.
     *
     * @param PDO $pdo Database connection.
     * @param array $authPayload Authenticated user payload containing 'user_id'.
     * @param array $body Request body containing 'flight_id', 'seats', and 'passengers'.
     */
    public static function create(PDO $pdo, array $authPayload, array $body): void
    {
        // Extract user ID from auth payload
        $userId = (int)$authPayload['user_id'];

        // Extract and validate booking parameters
        $flightId = isset($body['flight_id']) ? intval($body['flight_id']) : 0;
        $seats = isset($body['seats']) ? intval($body['seats']) : 0;
        $passengers = $body['passengers'] ?? [];

        // Validate flight ID
        if ($flightId <= 0) {
            Response::error('Invalid flight_id', 400);
        }
        // Validate seats count
        if ($seats <= 0) {
            Response::error('seats must be > 0', 400);
        }
        // Validate passengers array matches seats
        if (!is_array($passengers) || count($passengers) !== $seats) {
            Response::error('passengers must be an array with length equal to seats', 400);
        }

        try {
            // Create the booking
            $booking = Booking::create($pdo, $userId, $flightId, $passengers, $seats);
            Response::json(['booking' => $booking], 201);
        } catch (Exception $e) {
            $msg = $e->getMessage();
            // Handle specific error types with appropriate HTTP codes
            if ($msg === 'Flight not found') {
                Response::error($msg, 404);
            } elseif ($msg === 'Not enough seats available') {
                Response::error($msg, 409);
            } elseif ($e instanceof InvalidArgumentException) {
                Response::error($msg, 400);
            } else {
                Response::error('Booking failed: ' . $msg, 500);
            }
        }
    }

    /**
     * Retrieves a specific booking by ID for the authenticated user.
     * Ensures the booking belongs to the requesting user.
     *
     * @param PDO $pdo Database connection.
     * @param array $authPayload Authenticated user payload containing 'user_id'.
     * @param int $bookingId The booking ID to retrieve.
     */
    public static function get(PDO $pdo, array $authPayload, int $bookingId): void
    {
        // Extract user ID from auth payload
        $userId = (int)$authPayload['user_id'];

        // Find the booking
        $booking = Booking::findById($pdo, $bookingId);
        if (!$booking) {
            Response::error('Booking not found', 404);
        }
        // Check ownership
        if ((int)$booking['user_id'] !== $userId) {
            Response::error('Forbidden', 403);
        }
        Response::json(['booking' => $booking], 200);
    }
}