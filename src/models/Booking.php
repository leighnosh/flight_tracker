<?php declare(strict_types=1);

class Booking
{
    /**
     * Create a booking transactionally.
     *
     * @param PDO $pdo
     * @param int $userId
     * @param int $flightId
     * @param array $passengers  Array of passenger objects (name, age, passport etc.)
     * @param int $seatsBooked
     * @return array  Booking row (id, confirmation, flight_id, seats_booked, passengers, status, created_at)
     * @throws Exception on failure
     */
    public static function create(PDO $pdo, int $userId, int $flightId, array $passengers, int $seatsBooked): array
    {
        if ($seatsBooked <= 0) {
            throw new InvalidArgumentException('Seats booked must be > 0');
        }
        if (count($passengers) !== $seatsBooked) {
            throw new InvalidArgumentException('Passengers count must equal seats booked');
        }

        try {
            // Start transaction
            $pdo->beginTransaction();

            // Lock the flight row for update to avoid race conditions
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

            // Decrement seats
            $newSeats = $available - $seatsBooked;
            $update = $pdo->prepare("UPDATE flights SET available_seats = :newSeats WHERE id = :id");
            $update->execute([':newSeats' => $newSeats, ':id' => $flightId]);

            // Create a confirmation code (unique-ish)
            $confirmation = 'BOOK-' . bin2hex(random_bytes(6)) . '-' . time();

            // Insert booking
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

            // Return a snapshot of flight price & total
            $pricePerSeat = isset($flight['price']) ? (float)$flight['price'] : 0.0;
            $totalPrice = $pricePerSeat * $seatsBooked;

            $pdo->commit();

            // Fetch and return the booking row
            $stmt2 = $pdo->prepare("SELECT id, user_id, flight_id, passengers, seats_booked, confirmation, status, created_at FROM bookings WHERE id = :id LIMIT 1");
            $stmt2->execute([':id' => $bookingId]);
            $bookingRow = $stmt2->fetch(PDO::FETCH_ASSOC);

            // decode passengers JSON for convenience
            $bookingRow['passengers'] = json_decode($bookingRow['passengers'], true);
            $bookingRow['total_price'] = $totalPrice;
            $bookingRow['price_per_seat'] = $pricePerSeat;

            return $bookingRow;

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Get booking by id
     */
    public static function findById(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare("SELECT id, user_id, flight_id, passengers, seats_booked, confirmation, status, created_at FROM bookings WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        $row['passengers'] = json_decode($row['passengers'], true);
        return $row;
    }
}
