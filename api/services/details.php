<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/common.php';

requireRequestMethod(['GET']);
requireAdminAuth();

$serviceId = requirePositiveInt($_GET['id'] ?? $_GET['service_id'] ?? null, 'id');
$connection = getDatabaseConnection();

try {
    $service = fetchServiceById($connection, $serviceId);

    if ($service === null) {
        sendJsonResponse(404, false, 'Servicio no encontrado.');
    }

    successResponse(
        'Detalle del servicio obtenido.',
        [
            'service' => serializeServiceRecord($service),
        ]
    );
} catch (Throwable $exception) {
    errorResponse('No fue posible obtener el detalle del servicio.', [], 500);
}
