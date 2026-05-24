<?php
declare(strict_types=1);

if (!function_exists('getRequestHeadersMap')) {
    function getRequestHeadersMap(): array
    {
        if (function_exists('getallheaders')) {
            $headers = getallheaders();

            if (is_array($headers)) {
                $normalizedHeaders = [];

                foreach ($headers as $headerName => $headerValue) {
                    $normalizedHeaders[strtolower((string) $headerName)] = (string) $headerValue;
                }

                return $normalizedHeaders;
            }
        }

        $headers = [];

        foreach ($_SERVER as $serverKey => $value) {
            if (!str_starts_with($serverKey, 'HTTP_')) {
                continue;
            }

            $headerName = str_replace('_', '-', strtolower(substr($serverKey, 5)));
            $headers[$headerName] = (string) $value;
        }

        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = (string) $_SERVER['CONTENT_TYPE'];
        }

        return $headers;
    }
}

if (!function_exists('parseCorsAllowedOrigins')) {
    function parseCorsAllowedOrigins(array $appConfig): array
    {
        $rawOrigins = (string) ($appConfig['CORS_ALLOWED_ORIGINS'] ?? $appConfig['cors_allowed_origins'] ?? '');

        if (trim($rawOrigins) === '') {
            return [];
        }

        return array_values(
            array_filter(
                array_map(
                    static fn (string $origin): string => trim($origin),
                    explode(',', $rawOrigins)
                ),
                static fn (string $origin): bool => $origin !== ''
            )
        );
    }
}

if (!function_exists('isCorsOriginAllowed')) {
    function isCorsOriginAllowed(string $requestOrigin, array $allowedOrigins): bool
    {
        if ($requestOrigin === '') {
            return false;
        }

        foreach ($allowedOrigins as $allowedOrigin) {
            if ($allowedOrigin === '*') {
                return true;
            }

            if (strcasecmp($allowedOrigin, $requestOrigin) === 0) {
                return true;
            }

            if (!str_contains($allowedOrigin, '*')) {
                continue;
            }

            $pattern = '/^' . str_replace('\*', '[^.]+', preg_quote($allowedOrigin, '/')) . '$/i';

            if (preg_match($pattern, $requestOrigin) === 1) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('applyCorsHeaders')) {
    function applyCorsHeaders(array $appConfig): void
    {
        if (headers_sent()) {
            return;
        }

        $requestOrigin = trim((string) ($_SERVER['HTTP_ORIGIN'] ?? ''));

        if ($requestOrigin === '') {
            return;
        }

        $allowedOrigins = parseCorsAllowedOrigins($appConfig);

        if (!isCorsOriginAllowed($requestOrigin, $allowedOrigins)) {
            return;
        }

        header('Vary: Origin', false);
        header('Access-Control-Allow-Origin: ' . $requestOrigin);
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Accept, Authorization, X-Requested-With');
        header('Access-Control-Max-Age: 86400');
    }
}

if (!function_exists('handleCorsPreflight')) {
    function handleCorsPreflight(array $appConfig): void
    {
        $requestMethod = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

        if ($requestMethod !== 'OPTIONS') {
            return;
        }

        applyCorsHeaders($appConfig);
        http_response_code(204);
        exit;
    }
}

if (!function_exists('requireHttpMethod')) {
    function requireHttpMethod(string|array $allowedMethods): string
    {
        $normalizedAllowedMethods = array_map(
            static fn (string $method): string => strtoupper($method),
            (array) $allowedMethods
        );

        $requestMethod = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

        if (!in_array($requestMethod, $normalizedAllowedMethods, true)) {
            errorResponse(
                'Metodo no permitido.',
                ['allowed_methods' => $normalizedAllowedMethods],
                405
            );
        }

        return $requestMethod;
    }
}

if (!function_exists('requireJsonRequest')) {
    function requireJsonRequest(bool $allowEmptyBody = false): void
    {
        $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
        $rawBody = file_get_contents('php://input');
        $hasBody = $rawBody !== false && trim($rawBody) !== '';

        if (!$allowEmptyBody && !$hasBody) {
            errorResponse('El cuerpo JSON es obligatorio.', [], 400);
        }

        if ($hasBody && !str_contains($contentType, 'application/json')) {
            errorResponse('El endpoint requiere Content-Type application/json.', [], 400);
        }
    }
}

if (!function_exists('requireHeader')) {
    function requireHeader(string $headerName, ?callable $validator = null, ?string $errorMessage = null): string
    {
        $headers = getRequestHeadersMap();
        $normalizedHeaderName = strtolower($headerName);
        $headerValue = $headers[$normalizedHeaderName] ?? '';

        if ($headerValue === '') {
            errorResponse(
                $errorMessage ?? ('Header requerido: ' . $headerName . '.'),
                ['header' => $headerName],
                400
            );
        }

        if ($validator !== null && $validator($headerValue) !== true) {
            errorResponse(
                $errorMessage ?? ('Header invalido: ' . $headerName . '.'),
                ['header' => $headerName],
                400
            );
        }

        return $headerValue;
    }
}
