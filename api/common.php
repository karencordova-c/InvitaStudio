<?php
declare(strict_types=1);

$appConfig = require __DIR__ . '/../config/app.php';
$databaseConfig = require __DIR__ . '/../config/database.php';

require_once __DIR__ . '/shared/response.php';
require_once __DIR__ . '/shared/validation.php';
require_once __DIR__ . '/shared/helpers.php';
require_once __DIR__ . '/shared/services.php';
require_once __DIR__ . '/shared/deliveries.php';
require_once __DIR__ . '/shared/messages.php';
require_once __DIR__ . '/shared/mail_service.php';
require_once __DIR__ . '/shared/middleware.php';
require_once __DIR__ . '/shared/auth.php';

$GLOBALS['appConfig'] = $appConfig;
$GLOBALS['databaseConfig'] = $databaseConfig;

applyCorsHeaders($appConfig);
handleCorsPreflight($appConfig);

if (!function_exists('sendJsonResponse')) {
    function sendJsonResponse(int $statusCode, bool $success, string $message, array $payload = []): void
    {
        if ($success) {
            successResponse($message, $payload, $statusCode);
        }

        errorResponse($message, $payload, $statusCode);
    }
}

if (!function_exists('requireRequestMethod')) {
    function requireRequestMethod(array $allowedMethods): string
    {
        return requireHttpMethod($allowedMethods);
    }
}

if (!function_exists('respondPlaceholderEndpoint')) {
    function respondPlaceholderEndpoint(string $moduleName, string $actionName, string $expectedMethod): void
    {
        successResponse(
            'Endpoint placeholder listo para implementacion futura.',
            [
                'app_name' => $GLOBALS['appConfig']['APP_NAME'] ?? 'InvitaStudio',
                'module' => $moduleName,
                'action' => $actionName,
                'expected_method' => $expectedMethod,
                'implemented' => false,
            ]
        );
    }
}

if (!function_exists('getDatabaseConnection')) {
    function getDatabaseConnection(): PDO
    {
        try {
            return getPdoConnection();
        } catch (Throwable $exception) {
            errorResponse('No fue posible conectar con la base de datos.', [], 500);
        }
    }
}

if (!function_exists('readRequestData')) {
    function readRequestData(): array
    {
        if ($_POST !== []) {
            return sanitizeArray($_POST);
        }

        $rawBody = file_get_contents('php://input');

        if ($rawBody === false || trim($rawBody) === '') {
            return [];
        }

        $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));

        if (str_contains($contentType, 'application/json')) {
            $decodedBody = json_decode($rawBody, true);

            if (!is_array($decodedBody)) {
                errorResponse('JSON invalido.', [], 400);
            }

            return sanitizeArray($decodedBody);
        }

        parse_str($rawBody, $parsedBody);

        return is_array($parsedBody) ? sanitizeArray($parsedBody) : [];
    }
}

if (!function_exists('requirePositiveInt')) {
    function requirePositiveInt(mixed $value, string $fieldName): int
    {
        $filteredValue = filter_var($value, FILTER_VALIDATE_INT);

        if ($filteredValue === false || (int) $filteredValue <= 0) {
            validationErrorResponse([$fieldName => ['Debe ser un entero positivo.']]);
        }

        return (int) $filteredValue;
    }
}

if (!function_exists('requirePositiveAmount')) {
    function requirePositiveAmount(mixed $value, string $fieldName): float
    {
        $errors = validateNumericValue($value, $fieldName, 0.01);
        assertValidInput($errors);

        return round((float) $value, 2);
    }
}

if (!function_exists('requireStringValue')) {
    function requireStringValue(mixed $value, string $fieldName, int $maxLength): string
    {
        $errors = mergeValidationErrors(
            validateStringLength($value, $fieldName, 1, $maxLength)
        );
        assertValidInput($errors);

        return sanitizeString($value);
    }
}

if (!function_exists('optionalStringValue')) {
    function optionalStringValue(mixed $value, int $maxLength): ?string
    {
        if (isEmptyValue($value)) {
            return null;
        }

        $errors = validateStringLength($value, 'value', 1, $maxLength, false);
        assertValidInput($errors);

        return sanitizeString($value);
    }
}

if (!function_exists('createActivityLogEntry')) {
    function createActivityLogEntry(
        PDO $connection,
        string $usuarioTipo,
        ?int $usuarioId,
        string $accion,
        string $modulo,
        ?int $referenciaId,
        string $descripcion,
        ?string $ipAddress
    ): void {
        $statement = $connection->prepare(
            'INSERT INTO actividad_log (
                usuario_tipo,
                usuario_id,
                accion,
                modulo,
                referencia_id,
                descripcion,
                ip_address,
                created_at
            ) VALUES (
                :usuario_tipo,
                :usuario_id,
                :accion,
                :modulo,
                :referencia_id,
                :descripcion,
                :ip_address,
                NOW()
            )'
        );

        $statement->execute(
            [
                'usuario_tipo' => $usuarioTipo,
                'usuario_id' => $usuarioId,
                'accion' => $accion,
                'modulo' => $modulo,
                'referencia_id' => $referenciaId,
                'descripcion' => sanitizeString($descripcion),
                'ip_address' => optionalStringValue($ipAddress, 45),
            ]
        );
    }
}
