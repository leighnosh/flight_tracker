<?php

declare(strict_types=1);

class Flight
{
    public static function search(
        PDO $pdo,
        string $origin,
        string $destination,
        string $date,
        int $passengers,
        int $limit,
        int $offset,
        string $orderBy
    ): array {
        $dateStart = $date . ' 00:00:00';
        $dateEnd   = $date . ' 23:59:59';
    
        // Allowlist for ORDER BY
        $orderMap = [
            'price_asc'      => 'price ASC, departure ASC',
            'price_desc'     => 'price DESC, departure ASC',
            'departure_asc'  => 'departure ASC, price ASC',
            'departure_desc' => 'departure DESC, price ASC',
        ];
        $orderSql = $orderMap[$orderBy] ?? $orderMap['price_asc'];
    
        $sql = "SELECT id, airline, airline_code, flight_number, origin, destination,
                    DATE_FORMAT(departure, '%Y-%m-%dT%H:%i:%sZ') AS departure,
                    IFNULL(DATE_FORMAT(arrival, '%Y-%m-%dT%H:%i:%sZ'), '') AS arrival,
                    duration, price, available_seats
                FROM flights
                WHERE origin = :origin
                  AND destination = :destination
                  AND departure BETWEEN :date_start AND :date_end
                  AND available_seats >= :passengers
                ORDER BY {$orderSql}
                LIMIT :limit OFFSET :offset";
    
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':origin', $origin, PDO::PARAM_STR);
        $stmt->bindValue(':destination', $destination, PDO::PARAM_STR);
        $stmt->bindValue(':date_start', $dateStart, PDO::PARAM_STR);
        $stmt->bindValue(':date_end', $dateEnd, PDO::PARAM_STR);
        $stmt->bindValue(':passengers', $passengers, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        foreach ($rows as &$r) {
            $r['price'] = (float)$r['price'];
            $r['available_seats'] = (int)$r['available_seats'];
        }
        unset($r);
    
        return $rows;
    }
    
}
