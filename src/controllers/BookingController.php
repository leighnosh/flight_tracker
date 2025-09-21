<?php declare(strict_types=1);

require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../models/Booking.php';

class BookingController
{

    public static function create(PDO $pdo, array $authPayload, array $body): void
    {
        $userId = (int)$authPayload['user_id'];

        $flightId = isset($body['flight_id']) ? intval($body['flight_id']) : 0;
        $seats = isset($body['seats']) ? intval($body['seats']) : 0;
        $passengers = $body['passengers'] ?? [];

        if ($flightId <= 0) {
            Response::error('Invalid flight_id', 400);
        }
        if ($seats <= 0) {
            Response::error('seats must be > 0', 400);
        }
        if (!is_array($passengers) || count($passengers) !== $seats) {
            Response::error('passengers must be an array with length equal to seats', 400);
        }

        try {
            $booking = Booking::create($pdo, $userId, $flightId, $passengers, $seats);
            Response::json(['booking' => $booking], 201);
        } catch (Exception $e) {
            $msg = $e->getMessage();
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

    // GET /api/bookings/{id}
    public static function get(PDO $pdo, array $authPayload, int $bookingId): void
    {
        $userId = (int)$authPayload['user_id'];

        $booking = Booking::findById($pdo, $bookingId);
        if (!$booking) {
            Response::error('Booking not found', 404);
        }
        if ((int)$booking['user_id'] !== $userId) {
            Response::error('Forbidden', 403);
        }
        Response::json(['booking' => $booking], 200);
    }
}