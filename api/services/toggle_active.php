<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/common.php';

requireRequestMethod(['PUT']);
$adminUser = requireAdminAuth();

$requestData = readRequestData();
$serviceId = requirePositiveInt($requestData['id'] ?? $requestData['service_id'] ?? null, 'id');
$activeValue = normalizeActiveFlag($requestData['activo'] ?? null);
$ipAddress = getClientIpAddress();
$connection = getDatabaseConnection();

try {
    $connection->beginTransaction();

    $serviceStatement = $connection->prepare(
        'SELECT id, nombre, activo
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
         SET activo = :activo, updated_at = NOW()
         WHERE id = :id'
    );
    $updateStatement->execute(
        [
            'activo' => $activeValue,
            'id' => $serviceId,
        ]
    );

    $actionName = $activeValue === 1 ? 'activar_servicio' : 'desactivar_servicio';
    $actionLabel = $activeValue === 1 ? 'Activacion' : 'Desactivacion';

    createActivityLogEntry(
        $connection,
        'admin',
        (int) $adminUser['id'],
        $actionName,
        'services',
        $serviceId,
        $actionLabel . ' del servicio ' . ((string) ($service['nombre'] ?? '')) . '.',
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
        $activeValue === 1 ? 'Servicio activado correctamente.' : 'Servicio desactivado correctamente.',
        [
            'service' => serializeServiceRecord($updatedService),
        ]
    );
} catch (Throwable $exception) {
    if ($connection->inTransaction()) {
        $connection->rollBack();
    }

    sendJsonResponse(500, false, 'No fue posible actualizar el estado del servicio.');
}
