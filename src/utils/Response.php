<?php

declare(strict_types=1);

/**
 * Response utility class for handling HTTP responses.
 * Provides methods for sending JSON responses and error messages.
 */
class Response
{
    /**
     * Send a JSON response with given data and HTTP status code.
     *
     * @param mixed $data Data to encode as JSON.
     * @param int $status HTTP status code (default 200).
     */
    public static function json($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Shortcut for error responses with consistent structure.
     * Sends a JSON error response and exits.
     *
     * @param string $message Error message.
     * @param int $status HTTP status code (default 400).
     * @param array $extra Additional data to include in the response.
     */
    public static function error(string $message, int $status = 400, array $extra = []): void
    {
        $payload = array_merge([
            'error' => true,
            'message' => $message
        ], $extra);

        self::json($payload, $status);
    }
}
