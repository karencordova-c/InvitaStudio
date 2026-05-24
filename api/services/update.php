<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/common.php';

requireRequestMethod(['PUT']);
$adminUser = requireAdminAuth();

$requestData = readRequestData();
$serviceId = requirePositiveInt($requestData['id'] ?? $requestData['service_id'] ?? null, 'id');
$validationErrors = validateServicePayload($requestData);
assertValidInput($validationErrors);

$serviceData = sanitizeServicePayload($requestData);
$ipAddress = getClientIpAddress();
$connection = getDatabaseConnection();

try {
    $connection->beginTransaction();

    $serviceStatement = $connection->prepare(
        'SELECT id, nombre
         FROM servicios
         WHERE id = :id
         LIMIT 1
         FOR UPDATE'
    );
    $serviceStatement->execute(['id' => $serviceId]);
    $service = $serviceStatement->fetch(PDO::FETCH_ASSOC);

    if ($service === false) {
        $connection->rollBack();
        sendJsonResponse(404, false, 'Servicio no encontrado.');
    }

    $updateStatement = $connection->prepare(
        'UPDATE servicios
         SET nombre = :nombre,
             descripcion = :descripcion,
             categoria = :categoria,
             precio = :precio,
             formato_entrega = :formato_entrega,
             tiempo_entrega = :tiempo_entrega,
             imagen_referencia = :imagen_referencia,
             activo = :activo,
             updated_at = NOW()
         WHERE id = :id'
    );
    $updateStatement->execute(
        array_merge(
            $serviceData,
            ['id' => $serviceId]
        )
    );

    createActivityLogEntry(
        $connection,
        'admin',
        (int) $adminUser['id'],
        'editar_servicio',
        'services',
        $serviceId,
        'Actualizacion del servicio ' . $serviceData['nombre'] . '.',
        $ipAddress
    );

    $updatedService = fetchServiceById($connection, $serviceId);

    if ($updatedService === null) {
        $connection->rollBack();
        sendJsonResponse(500, false, 'No fue posible recuperar el servicio actualizado.');
    }

    $connection->commit();

    sendJsonResponse(
        200,
        true,
        'Servicio actualizado correctamente.',
        [
            'service' => serializeServiceRecord($updatedService),
        ]
    );
} catch (Throwable $exception) {
    if ($connection->inTransaction()) {
        $connection->rollBack();
    }

    sendJsonResponse(500, false, 'No fue posible actualizar el servicio.');
}
