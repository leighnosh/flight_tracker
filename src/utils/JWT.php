<?php declare(strict_types=1);

class JWT
{
    // Base64 URL encode/decode helpers
    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        $pad = 4 - (strlen($data) % 4);
        if ($pad < 4)
            $data .= str_repeat('=', $pad);
        return base64_decode(strtr($data, '-_', '+/'));
    }

    // Create token: $payload must be array. $secret string from config. $ttl seconds
    public static function encode(array $payload, string $secret, int $ttl = 3600): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $now = time();
        $payload['iat'] = $now;
        if (!isset($payload['exp']))
            $payload['exp'] = $now + $ttl;

        $segments = [];
        $segments[] = self::base64UrlEncode(json_encode($header));
        $segments[] = self::base64UrlEncode(json_encode($payload));
        $signingInput = implode('.', $segments);
        $sig = hash_hmac('sha256', $signingInput, $secret, true);
        $segments[] = self::base64UrlEncode($sig);
        return implode('.', $segments);
    }

    // Verify and decode token; returns payload array on success or throws Exception
    public static function decode(string $token, string $secret): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3)
            throw new Exception('Invalid token structure');
        [$headB64, $payloadB64, $sigB64] = $parts;
        $header = json_decode(self::base64UrlDecode($headB64), true);
        $payload = json_decode(self::base64UrlDecode($payloadB64), true);
        if (!is_array($header) || !is_array($payload))
            throw new Exception('Invalid token encoding');

        $sig = self::base64UrlDecode($sigB64);
        $validSig = hash_hmac('sha256', "{$headB64}.{$payloadB64}", $secret, true);

        if (!hash_equals($validSig, $sig))
            throw new Exception('Invalid token signature');

        // Check exp
        if (isset($payload['exp']) && time() > intval($payload['exp'])) {
            throw new Exception('Token expired');
        }

        return $payload;
    }
}
