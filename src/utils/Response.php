<?php

declare(strict_types=1);

class Response
{
    /**
     * Send a JSON response with given data and HTTP status code.
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
