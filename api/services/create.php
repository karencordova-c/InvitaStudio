<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/common.php';

requireRequestMethod(['POST']);
$adminUser = requireAdminAuth();

$requestData = readRequestData();
$validationErrors = validateServicePayload($requestData);
assertValidInput($validationErrors);

$serviceData = sanitizeServicePayload($requestData);
$ipAddress = getClientIpAddress();
$connection = getDatabaseConnection();

try {
    $connection->beginTransaction();

    $insertStatement = $connection->prepare(
        'INSERT INTO servicios (
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
         ) VALUES (
            :nombre,
            :descripcion,
            :categoria,
            :precio,
            :formato_entrega,
            :tiempo_entrega,
            :imagen_referencia,
            :activo,
            NOW(),
            NOW()
         )'
    );
    $insertStatement->execute($serviceData);

    $serviceId = (int) $connection->lastInsertId();
    $service = fetchServiceById($connection, $serviceId);

    if ($service === null) {
        $connection->rollBack();
        sendJsonResponse(500, false, 'No fue posible recuperar el servicio creado.');
    }

    createActivityLogEntry(
        $connection,
        'admin',
        (int) $adminUser['id'],
        'crear_servicio',
        'services',
        $serviceId,
        'Registro del servicio ' . $serviceData['nombre'] . '.',
        $ipAddress
    );

    $connection->commit();

    sendJsonResponse(
        201,
        true,
        'Servicio creado correctamente.',
        [
            'service' => serializeServiceRecord($service),
        ]
    );
} catch (Throwable $exception) {
    if ($connection->inTransaction()) {
        $connection->rollBack();
    }

    sendJsonResponse(500, false, 'No fue posible crear el servicio.');
}
