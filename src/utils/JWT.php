<?php declare(strict_types=1);

/**
 * JWT utility class for encoding and decoding JSON Web Tokens using HMAC-SHA256.
 * Provides static methods for creating signed tokens and verifying them.
 * Includes automatic handling of standard claims like 'iat' and 'exp'.
 */
class JWT
{
    // Algorithm used for signing the JWT (HMAC-SHA256)
    private const ALG = 'HS256';
    // Leeway in seconds for time-based validations to account for clock skew
    private const LEEWAY = 30; // seconds

    /**
     * Encodes data to base64url format (URL-safe base64 without padding).
     *
     * @param string $data The data to encode.
     * @return string The base64url encoded string.
     */
    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Decodes a base64url encoded string back to its original form.
     *
     * @param string $data The base64url encoded string.
     * @return string The decoded string.
     * @throws UnexpectedValueException If the input is invalid base64url.
     */
    private static function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        $decoded = base64_decode(strtr($data, '-_', '+/'), true);
        if ($decoded === false) {
            throw new UnexpectedValueException('Invalid base64url string');
        }
        return $decoded;
    }

    /**
     * Encodes a payload into a JWT string using the provided secret.
     *
     * Automatically adds 'iat' (issued at) and 'exp' (expiration) claims if not present.
     *
     * @param array $payload The payload data to include in the token.
     * @param string $secret The secret key for signing.
     * @param int $ttl Time-to-live in seconds for the token (default 3600).
     * @return string The encoded JWT token.
     * @throws JsonException If JSON encoding fails.
     */
    public static function encode(array $payload, string $secret, int $ttl = 3600): string
    {
        // Create the JWT header
        $header = ['alg' => self::ALG, 'typ' => 'JWT'];
        $now = time();

        // Add issued-at time
        $payload['iat'] = $now;
        // Add expiration if not set
        if (!isset($payload['exp'])) {
            $payload['exp'] = $now + $ttl;
        }

        // Encode header and payload to JSON
        $headJson = json_encode($header, JSON_THROW_ON_ERROR);
        $payloadJson = json_encode($payload, JSON_THROW_ON_ERROR);

        // Create base64url encoded segments
        $segments = [
            self::base64UrlEncode($headJson),
            self::base64UrlEncode($payloadJson),
        ];
        // Signing input is header.payload
        $signingInput = implode('.', $segments);
        // Generate HMAC-SHA256 signature
        $sig = hash_hmac('sha256', $signingInput, $secret, true);
        // Add signature segment
        $segments[] = self::base64UrlEncode($sig);

        // Return the complete token
        return implode('.', $segments);
    }

    /**
     * Decodes and verifies a JWT token, returning the payload if valid.
     *
     * Verifies the signature, algorithm, and time-based claims ('exp', 'nbf').
     *
     * @param string $token The JWT token to decode.
     * @param string $secret The secret key used for verification.
     * @return array The decoded payload.
     * @throws InvalidArgumentException If the token structure is invalid.
     * @throws UnexpectedValueException If decoding, signature, or claims fail.
     * @throws JsonException If JSON decoding fails.
     */
    public static function decode(string $token, string $secret): array
    {
        // Split the token into its three parts
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new InvalidArgumentException('Invalid token structure');
        }

        // Extract base64url encoded parts
        [$headB64, $payloadB64, $sigB64] = $parts;

        // Decode header and payload
        $headerJson = self::base64UrlDecode($headB64);
        $payloadJson = self::base64UrlDecode($payloadB64);
        $sig = self::base64UrlDecode($sigB64);

        // Parse JSON
        $header = json_decode($headerJson, true, 512, JSON_THROW_ON_ERROR);
        $payload = json_decode($payloadJson, true, 512, JSON_THROW_ON_ERROR);

        // Ensure decoded data is arrays
        if (!is_array($header) || !is_array($payload)) {
            throw new UnexpectedValueException('Invalid token encoding');
        }

        // Verify algorithm
        if (!isset($header['alg']) || $header['alg'] !== self::ALG) {
            throw new UnexpectedValueException('Unsupported algorithm');
        }

        // Verify signature
        $validSig = hash_hmac('sha256', "{$headB64}.{$payloadB64}", $secret, true);
        if (!hash_equals($validSig, $sig)) {
            throw new UnexpectedValueException('Invalid token signature');
        }

        // Check expiration with leeway
        if (isset($payload['exp']) && (time() - self::LEEWAY) > (int)$payload['exp']) {
            throw new UnexpectedValueException('Token expired');
        }

        // Check not-before with leeway
        if (isset($payload['nbf']) && (time() + self::LEEWAY) < (int)$payload['nbf']) {
            throw new UnexpectedValueException('Token not yet valid');
        }

        // Return the payload
        return $payload;
    }
}
