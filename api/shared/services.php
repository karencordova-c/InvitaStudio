<?php
declare(strict_types=1);

if (!function_exists('getAllowedServiceFormats')) {
    function getAllowedServiceFormats(): array
    {
        return ['imagen', 'pdf', 'video'];
    }
}

if (!function_exists('getServiceFormatLabel')) {
    function getServiceFormatLabel(string $format): string
    {
        return match ($format) {
            'imagen' => 'Imagen',
            'pdf' => 'PDF',
            'video' => 'Video',
            default => 'Sin formato',
        };
    }
}

if (!function_exists('normalizeActiveFlag')) {
    function normalizeActiveFlag(mixed $value, string $fieldName = 'activo'): int
    {
        if ($value === null || $value === '') {
            validationErrorResponse([$fieldName => ['El estado activo es obligatorio.']]);
        }

        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        $normalizedValue = strtolower(trim((string) $value));

        return match ($normalizedValue) {
            '1', 'true', 'activo', 'si', 'sí', 'on' => 1,
            '0', 'false', 'inactivo', 'no', 'off' => 0,
            default => validationErrorResponse([$fieldName => ['El estado activo no es valido.']]),
        };
    }
}

if (!function_exists('validateServicePayload')) {
    function validateServicePayload(array $input): array
    {
        $imageReference = $input['imagen_referencia'] ?? null;
        $imageReferenceRequired = !isEmptyValue($imageReference);

        return mergeValidationErrors(
            validateStringLength($input['nombre'] ?? null, 'nombre', 3, 150),
            validateStringLength($input['descripcion'] ?? null, 'descripcion', 10, 1000),
            validateStringLength($input['categoria'] ?? null, 'categoria', 3, 100),
            validateNumericValue($input['precio'] ?? null, 'precio', 0),
            validateEnumValue($input['formato_entrega'] ?? null, 'formato_entrega', getAllowedServiceFormats()),
            validateStringLength($input['tiempo_entrega'] ?? null, 'tiempo_entrega', 3, 100),
            validateStringLength($imageReference, 'imagen_referencia', 3, 255, $imageReferenceRequired)
        );
    }
}

if (!function_exists('sanitizeServicePayload')) {
    function sanitizeServicePayload(array $input): array
    {
        return [
            'nombre' => sanitizeString($input['nombre'] ?? null),
            'descripcion' => sanitizeString($input['descripcion'] ?? null),
            'categoria' => sanitizeString($input['categoria'] ?? null),
            'precio' => round((float) ($input['precio'] ?? 0), 2),
            'formato_entrega' => normalizeString($input['formato_entrega'] ?? null),
            'tiempo_entrega' => sanitizeString($input['tiempo_entrega'] ?? null),
            'imagen_referencia' => optionalStringValue($input['imagen_referencia'] ?? null, 255),
            'activo' => normalizeActiveFlag($input['activo'] ?? null),
        ];
    }
}

if (!function_exists('fetchServiceById')) {
    function fetchServiceById(PDO $connection, int $serviceId): ?array
    {
        $statement = $connection->prepare(
            'SELECT
                id,
                nombre,
                descripcion,
                categoria,
                precio,
                formato_entrega,
                tiempo_entrega,
                imagen_referencia,
                activo,
                created_at,
                updated_at
             FROM servicios
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $serviceId]);

        $service = $statement->fetch(PDO::FETCH_ASSOC);

        return $service === false ? null : $service;
    }
}

if (!function_exists('serializeServiceRecord')) {
    function serializeServiceRecord(array $service): array
    {
        $format = (string) ($service['formato_entrega'] ?? '');
        $isActive = (int) ($service['activo'] ?? 0) === 1;

        return [
            'id' => (int) ($service['id'] ?? 0),
            'nombre' => (string) ($service['nombre'] ?? ''),
            'descripcion' => (string) ($service['descripcion'] ?? ''),
            'categoria' => (string) ($service['categoria'] ?? ''),
            'precio' => isset($service['precio']) ? (float) $service['precio'] : 0.0,
            'formato_entrega' => $format,
            'formato_entrega_label' => getServiceFormatLabel($format),
            'tiempo_entrega' => (string) ($service['tiempo_entrega'] ?? ''),
            'imagen_referencia' => (string) ($service['imagen_referencia'] ?? ''),
            'activo' => $isActive,
            'activo_valor' => $isActive ? 1 : 0,
            'created_at' => (string) ($service['created_at'] ?? ''),
            'updated_at' => (string) ($service['updated_at'] ?? ''),
        ];
    }
}
