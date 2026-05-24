<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/common.php';

requireRequestMethod(['GET']);
requireAdminAuth();

$orderId = filter_var($_GET['order_id'] ?? $_GET['pedido_id'] ?? null, FILTER_VALIDATE_INT);
$deliveryId = filter_var($_GET['id'] ?? $_GET['delivery_id'] ?? null, FILTER_VALIDATE_INT);

if (($orderId === false || (int) $orderId <= 0) && ($deliveryId === false || (int) $deliveryId <= 0)) {
    validationErrorResponse(
        ['order_id' => ['Debes indicar un pedido o entrega valida.']],
        'Parametros invalidos.'
    );
}

$connection = getDatabaseConnection();

try {
    if ($orderId !== false && (int) $orderId > 0) {
        $statement = $connection->prepare(
            'SELECT
                e.id,
                e.pedido_id,
                e.formato_entrega,
                e.archivo_final,
                e.fecha_entrega,
                e.notas_entrega,
                e.created_at,
                e.updated_at,
                p.numero_pedido,
                p.estado_pedido
             FROM entregas e
             INNER JOIN pedidos p ON p.id = e.pedido_id
             WHERE e.pedido_id = :pedido_id
             ORDER BY COALESCE(e.fecha_entrega, e.updated_at, e.created_at) DESC, e.id DESC
             LIMIT 1'
        );
        $statement->execute(['pedido_id' => (int) $orderId]);
    } else {
        $statement = $connection->prepare(
            'SELECT
                e.id,
                e.pedido_id,
                e.formato_entrega,
                e.archivo_final,
                e.fecha_entrega,
                e.notas_entrega,
                e.created_at,
                e.updated_at,
                p.numero_pedido,
                p.estado_pedido
             FROM entregas e
             INNER JOIN pedidos p ON p.id = e.pedido_id
             WHERE e.id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => (int) $deliveryId]);
    }

    $delivery = $statement->fetch(PDO::FETCH_ASSOC);

    if ($delivery === false) {
        sendJsonResponse(404, false, 'Entrega no encontrada.');
    }

    $storedPath = (string) ($delivery['archivo_final'] ?? '');
    $filePath = resolveStoredDeliveryAbsolutePath($storedPath);

    successResponse(
        'Detalle de entrega obtenido.',
        [
            'delivery' => [
                'id' => (int) ($delivery['id'] ?? 0),
                'pedido_id' => (int) ($delivery['pedido_id'] ?? 0),
                'numero_pedido' => (string) ($delivery['numero_pedido'] ?? ''),
                'estado_pedido' => (string) ($delivery['estado_pedido'] ?? ''),
                'formato_entrega' => (string) ($delivery['formato_entrega'] ?? ''),
                'archivo_final' => $storedPath,
                'archivo_nombre' => getDeliveryFileName($storedPath),
                'archivo_disponible' => $filePath !== null,
                'fecha_entrega' => (string) ($delivery['fecha_entrega'] ?? ''),
                'notas_entrega' => (string) ($delivery['notas_entrega'] ?? ''),
                'created_at' => (string) ($delivery['created_at'] ?? ''),
                'updated_at' => (string) ($delivery['updated_at'] ?? ''),
            ],
        ]
    );
} catch (Throwable $exception) {
    errorResponse('No fue posible obtener el detalle de la entrega.', [], 500);
}
