<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/common.php';
require_once __DIR__ . '/common.php';

requireRequestMethod(['POST']);

$requestData = readRequestData();
$connection = getDatabaseConnection();
$ipAddress = getClientIpAddress();

try {
    $validatedInput = validateStatusLookupInput($requestData);
    $order = findPublicOrderStatusRecord(
        $connection,
        $validatedInput['numero_pedido'],
        $validatedInput['correo']
    );

    if ($order === null) {
        errorResponse('Pedido no encontrado', [], 404);
    }

    successResponse('Consulta realizada correctamente.', buildPublicStatusResponseData($order));
} catch (Throwable $exception) {
    logPublicStatusFailure(
        'Fallo durante la consulta publica del estado del pedido.',
        $exception,
        ['ip_address' => $ipAddress]
    );
    errorResponse('No fue posible consultar el pedido.', [], 500);
}
