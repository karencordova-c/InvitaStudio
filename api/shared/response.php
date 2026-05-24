<?php
declare(strict_types=1);

if (!function_exists('setJsonHeaders')) {
    function setJsonHeaders(): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=UTF-8');
        }
    }
}

if (!function_exists('jsonResponse')) {
    function jsonResponse(int $statusCode, array $payload): void
    {
        http_response_code($statusCode);
        setJsonHeaders();

        echo json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        exit;
    }
}

if (!function_exists('successResponse')) {
    function successResponse(string $message, array $data = [], int $statusCode = 200): void
    {
        jsonResponse(
            $statusCode,
            [
                'success' => true,
                'message' => $message,
                'data' => $data,
            ]
        );
    }
}

if (!function_exists('errorResponse')) {
    function errorResponse(string $message, array $errors = [], int $statusCode = 400): void
    {
        jsonResponse(
            $statusCode,
            [
                'success' => false,
                'message' => $message,
                'errors' => $errors,
            ]
        );
    }
}

if (!function_exists('validationErrorResponse')) {
    function validationErrorResponse(array $errors, string $message = 'Campos invalidos.', int $statusCode = 422): void
    {
        errorResponse($message, $errors, $statusCode);
    }
}
