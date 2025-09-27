<?php declare(strict_types=1);

require_once __DIR__ . '/../utils/JWT.php';
require_once __DIR__ . '/../utils/Response.php';

/**
 * AuthMiddleware handles authentication for protected routes.
 * Provides methods to require valid JWT tokens and extract user payloads.
 */
class AuthMiddleware
{
    /**
     * Require a valid JWT token.
     * - Looks for "Authorization: Bearer <token>"
     * - Verifies signature & expiry
     * - Returns payload array on success
     * - Sends 401 JSON error + exit on failure
     *
     * @param array $config Configuration array containing JWT secret.
     * @return array Decoded JWT payload.
     */
    public static function requireAuth(array $config): array
    {
        // Retrieve Authorization header (check both direct and redirect versions)
        $hdr = $_SERVER['HTTP_AUTHORIZATION']
            ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');

        if (!$hdr) {
            Response::error('Missing Authorization header', 401);
        }

        // Ensure header starts with 'Bearer '
        if (stripos($hdr, 'Bearer ') !== 0) {
            Response::error('Invalid Authorization header format', 401);
        }

        // Extract token from header
        $token = trim(substr($hdr, 7));

        // Get JWT secret from config or environment
        $secret = $config['jwt_secret'] ?? getenv('JWT_SECRET');

        try {
            // Decode and verify the token
            $payload = JWT::decode($token, $secret);

            // Ensure payload contains required user_id
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
