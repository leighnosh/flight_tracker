<?php declare(strict_types=1);

/**
 * Booking model handles flight booking operations.
 * Provides methods for creating bookings with seat reservations and retrieving booking details.
 */
class Booking
{
    /**
     * Create a booking transactionally.
     * Locks the flight row to prevent race conditions, validates availability,
     * decrements seats, and inserts the booking record.
     *
     * @param PDO $pdo Database connection.
     * @param int $userId User ID making the booking.
     * @param int $flightId Flight ID to book.
     * @param array $passengers Array of passenger objects (name, age, passport etc.).
     * @param int $seatsBooked Number of seats to book.
     * @return array Booking row with additional calculated fields.
     * @throws Exception on failure (flight not found, insufficient seats, etc.).
     */
    public static function create(PDO $pdo, int $userId, int $flightId, array $passengers, int $seatsBooked): array
    {
        // Validate input parameters
        if ($seatsBooked <= 0) {
            throw new InvalidArgumentException('Seats booked must be > 0');
        }
        if (count($passengers) !== $seatsBooked) {
            throw new InvalidArgumentException('Passengers count must equal seats booked');
        }

        try {
            // Start database transaction
            $pdo->beginTransaction();

            // Lock the flight row for update to prevent concurrent bookings
            $stmt = $pdo->prepare("SELECT id, available_seats, price FROM flights WHERE id = :id FOR UPDATE");
            $stmt->execute([':id' => $flightId]);
            $flight = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$flight) {
                throw new Exception('Flight not found');
            }

            $available = (int)$flight['available_seats'];
            if ($available < $seatsBooked) {
                throw new Exception('Not enough seats available');
            }

            // Decrement available seats
            $newSeats = $available - $seatsBooked;
            $update = $pdo->prepare("UPDATE flights SET available_seats = :newSeats WHERE id = :id");
            $update->execute([':newSeats' => $newSeats, ':id' => $flightId]);

            // Generate unique confirmation code
            $confirmation = 'BOOK-' . bin2hex(random_bytes(6)) . '-' . time();

            // Insert booking record
            $insert = $pdo->prepare("
                INSERT INTO bookings (user_id, flight_id, passengers, seats_booked, confirmation, status)
                VALUES (:user_id, :flight_id, :passengers, :seats_booked, :confirmation, 'CONFIRMED')
            ");
            $insert->execute([
                ':user_id' => $userId,
                ':flight_id' => $flightId,
                ':passengers' => json_encode(array_values($passengers), JSON_UNESCAPED_UNICODE),
                ':seats_booked' => $seatsBooked,
                ':confirmation' => $confirmation
            ]);

            $bookingId = (int)$pdo->lastInsertId();

            // Calculate total price
            $pricePerSeat = isset($flight['price']) ? (float)$flight['price'] : 0.0;
            $totalPrice = $pricePerSeat * $seatsBooked;

            // Commit transaction
            $pdo->commit();

            // Fetch and return the complete booking row
            $stmt2 = $pdo->prepare("SELECT id, user_id, flight_id, passengers, seats_booked, confirmation, status, created_at FROM bookings WHERE id = :id LIMIT 1");
            $stmt2->execute([':id' => $bookingId]);
            $bookingRow = $stmt2->fetch(PDO::FETCH_ASSOC);

            // Decode passengers JSON for response
            $bookingRow['passengers'] = json_decode($bookingRow['passengers'], true);
            $bookingRow['total_price'] = $totalPrice;
            $bookingRow['price_per_seat'] = $pricePerSeat;

            return $bookingRow;

        } catch (Exception $e) {
            // Rollback transaction on error
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Get booking by ID.
     * Retrieves a booking record and decodes the passengers JSON.
     *
     * @param PDO $pdo Database connection.
     * @param int $id Booking ID.
     * @return array|null Booking data or null if not found.
     */
    public static function findById(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare("SELECT id, user_id, flight_id, passengers, seats_booked, confirmation, status, created_at FROM bookings WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        // Decode passengers JSON
        $row['passengers'] = json_decode($row['passengers'], true);
        return $row;
    }
}
