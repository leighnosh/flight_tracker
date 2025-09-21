<?php declare(strict_types=1);

require_once __DIR__ . '/../utils/JWT.php';
require_once __DIR__ . '/../utils/Response.php';

class AuthMiddleware
{
    /**
     * Require a valid JWT token.
     * - Looks for "Authorization: Bearer <token>"
     * - Verifies signature & expiry
     * - Returns payload array on success
     * - Sends 401 JSON error + exit on failure
     */
    public static function requireAuth(array $config): array
    {
        // Get header
        $hdr = $_SERVER['HTTP_AUTHORIZATION']
            ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');

        if (!$hdr) {
            Response::error('Missing Authorization header', 401);
        }

        // Must start with Bearer
        if (stripos($hdr, 'Bearer ') !== 0) {
            Response::error('Invalid Authorization header format', 401);
        }

        $token = trim(substr($hdr, 7));

        $secret = $config['jwt_secret'] ?? getenv('JWT_SECRET');

        try {
            $payload = JWT::decode($token, $secret);

            if (!isset($payload['user_id'])) {
                Response::error('Invalid token payload', 401);
                return [];
            }

            return $payload;

        } catch (Exception $e) {
            Response::error('Invalid token: ' . $e->getMessage(), 401);
            return [];
        }
    }
}
