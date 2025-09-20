<?php
// db/seed.php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/../config/config.php';

// load DB connection
require_once __DIR__ . '/db.php';

$inputPath = __DIR__ . '/Flight_Data.json';
$inputReal = realpath($inputPath);

if (!$inputReal || !file_exists($inputReal)) {
    fwrite(STDERR, "ERROR: JSON file not found: {$inputPath}\n");
    exit(1);
}

$raw = file_get_contents($inputReal);
$data = json_decode($raw, true);
if (!is_array($data)) {
    fwrite(STDERR, "ERROR: JSON invalid or top-level not an array\n");
    exit(1);
}

$insertSql = <<<SQL
INSERT INTO flights
  (airline, airline_code, flight_number, origin, destination, departure, arrival, duration, price, available_seats, operational_days, raw_meta)
VALUES
  (:airline, :airline_code, :flight_number, :origin, :destination, :departure, :arrival, :duration, :price, :available_seats, :operational_days, :raw_meta)
ON DUPLICATE KEY UPDATE
  price = VALUES(price),
  available_seats = VALUES(available_seats),
  operational_days = VALUES(operational_days),
  raw_meta = VALUES(raw_meta)
SQL;

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare($insertSql);
    $count = 0;
    foreach ($data as $idx => $item) {
        // Map fields (robust to different key names)
        $airline = $item['airline'] ?? ($item['airlineName'] ?? null);
        $airlineCode = $item['airlineCode'] ?? ($item['carrierCode'] ?? null);

        // flightNumber in dataset is integer; store as string to be safe
        $flightNumber = isset($item['flightNumber']) ? (string)$item['flightNumber'] : ($item['flight_number'] ?? null);

        $origin = isset($item['origin']) ? strtoupper(trim($item['origin'])) : null;
        $destination = isset($item['destination']) ? strtoupper(trim($item['destination'])) : null;

        // departure/arrival ISO strings (expected in dataset)
        $departureRaw = $item['departure'] ?? null;
        $arrivalRaw = $item['arrival'] ?? null;

        $departure = parseDatetimeToMysql($departureRaw);
        $arrival = parseDatetimeToMysql($arrivalRaw);

        $duration = $item['duration'] ?? null;
        $price = isset($item['price']) ? floatval($item['price']) : 0.0;
        $availableSeats = isset($item['availableSeats']) ? intval($item['availableSeats']) : 0;

        // Normalize operationalDays to array of ints 0..6 where 0=Sunday
        $operationalRaw = $item['operationalDays'] ?? $item['operational_days'] ?? null;
        $operationalDays = normalizeOperationalDays($operationalRaw);

        // raw_meta: keep everything except top-level mapped fields to reduce duplication
        $meta = $item;
        unset($meta['airline'], $meta['airlineName'], $meta['airlineCode'], $meta['carrierCode']);
        unset($meta['flightNumber'], $meta['flight_number']);
        unset($meta['origin'], $meta['destination']);
        unset($meta['departure'], $meta['arrival']);
        unset($meta['duration'], $meta['price'], $meta['availableSeats'], $meta['operationalDays'], $meta['operational_days']);

        // Validate minimal required fields
        if (!$airline || !$airlineCode || !$flightNumber || !$origin || !$destination || !$departure) {
            fwrite(STDOUT, "Skipping entry #{$idx}: missing required field(s)\n");
            continue;
        }

        $stmt->execute([
            ':airline' => $airline,
            ':airline_code' => $airlineCode,
            ':flight_number' => $flightNumber,
            ':origin' => $origin,
            ':destination' => $destination,
            ':departure' => $departure,
            ':arrival' => $arrival,
            ':duration' => $duration,
            ':price' => $price,
            ':available_seats' => $availableSeats,
            ':operational_days' => json_encode(array_values($operationalDays), JSON_UNESCAPED_UNICODE),
            ':raw_meta' => json_encode($meta, JSON_UNESCAPED_UNICODE)
        ]);

        $count++;
    }

    $pdo->commit();
    fwrite(STDOUT, "Seed complete. Inserted/Updated {$count} flights.\n");
    exit(0);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR, "ERROR during seeding: " . $e->getMessage() . "\n");
    exit(1);
}

/**
 * Helpers
 */

/**
 * Convert ISO-ish datetime to MySQL DATETIME (Y-m-d H:i:s) or return NULL.
 */
function parseDatetimeToMysql($value): ?string {
    if (!$value) return null;
    // If numeric unix timestamp
    if (is_numeric($value)) {
        $ts = intval($value);
        return gmdate('Y-m-d H:i:s', $ts);
    }
    // Try DateTime parsing (handles ISO 8601)
    try {
        $dt = new DateTime($value);
        // store in UTC-equivalent MySQL DATETIME (no timezone suffix)
        $dt->setTimezone(new DateTimeZone('UTC'));
        return $dt->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Normalize operationalDays inputs into array of ints 0..6 (0=Sunday).
 * Accepts:
 * - array of ints
 * - comma-separated string like "1,2,3"
 * - single int
 * Any numeric value is mapped with ($v % 7) so values like 7 -> 0.
 */
function normalizeOperationalDays($val): array {
    if ($val === null) return [];
    $out = [];
    if (is_array($val)) {
        foreach ($val as $v) {
            if ($v === null || $v === '') continue;
            if (!is_numeric($v)) continue;
            $n = intval($v) % 7;
            if ($n < 0) $n += 7;
            $out[$n] = $n;
        }
    } elseif (is_numeric($val)) {
        $n = intval($val) % 7;
        if ($n < 0) $n += 7;
        $out[$n] = $n;
    } elseif (is_string($val)) {
        // split by comma/pipe/space
        $parts = preg_split('/[,\|\s;]+/', trim($val));
        foreach ($parts as $p) {
            if ($p === '') continue;
            if (!is_numeric($p)) continue;
            $n = intval($p) % 7;
            if ($n < 0) $n += 7;
            $out[$n] = $n;
        }
    }
    ksort($out, SORT_NUMERIC);
    return array_values($out);
}
