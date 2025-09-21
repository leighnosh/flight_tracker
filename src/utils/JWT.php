<?php declare(strict_types=1);

class JWT
{
    private const ALG = 'HS256';
    private const LEEWAY = 30; // seconds

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

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

    public static function encode(array $payload, string $secret, int $ttl = 3600): string
    {
        $header = ['alg' => self::ALG, 'typ' => 'JWT'];
        $now = time();

        $payload['iat'] = $now;
        if (!isset($payload['exp'])) {
            $payload['exp'] = $now + $ttl;
        }

        $headJson = json_encode($header, JSON_THROW_ON_ERROR);
        $payloadJson = json_encode($payload, JSON_THROW_ON_ERROR);

        $segments = [
            self::base64UrlEncode($headJson),
            self::base64UrlEncode($payloadJson),
        ];
        $signingInput = implode('.', $segments);
        $sig = hash_hmac('sha256', $signingInput, $secret, true);
        $segments[] = self::base64UrlEncode($sig);

        return implode('.', $segments);
    }

    public static function decode(string $token, string $secret): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new InvalidArgumentException('Invalid token structure');
        }

        [$headB64, $payloadB64, $sigB64] = $parts;

        $headerJson = self::base64UrlDecode($headB64);
        $payloadJson = self::base64UrlDecode($payloadB64);
        $sig = self::base64UrlDecode($sigB64);

        $header = json_decode($headerJson, true, 512, JSON_THROW_ON_ERROR);
        $payload = json_decode($payloadJson, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($header) || !is_array($payload)) {
            throw new UnexpectedValueException('Invalid token encoding');
        }

        if (!isset($header['alg']) || $header['alg'] !== self::ALG) {
            throw new UnexpectedValueException('Unsupported algorithm');
        }

        $validSig = hash_hmac('sha256', "{$headB64}.{$payloadB64}", $secret, true);
        if (!hash_equals($validSig, $sig)) {
            throw new UnexpectedValueException('Invalid token signature');
        }

        if (isset($payload['exp']) && (time() - self::LEEWAY) > (int)$payload['exp']) {
            throw new UnexpectedValueException('Token expired');
        }

        if (isset($payload['nbf']) && (time() + self::LEEWAY) < (int)$payload['nbf']) {
            throw new UnexpectedValueException('Token not yet valid');
        }

        return $payload;
    }
}
