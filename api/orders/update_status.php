<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/common.php';

requireRequestMethod(['PUT']);
$adminUser = requireAdminAuth();

$requestData = readRequestData();
$allowedStatuses = [
    'pendiente',
    'pago_confirmado',
    'en_proceso',
    'terminado',
    'entregado',
    'cancelado',
];

$orderId = requirePositiveInt($requestData['order_id'] ?? null, 'order_id');
$newStatus = sanitizeString($requestData['estado_pedido'] ?? '');

assertValidInput(
    validateEnumValue($newStatus, 'estado_pedido', $allowedStatuses)
);

$connection = getDatabaseConnection();
$ipAddress = getClientIpAddress();

try {
    $connection->beginTransaction();

    $orderStatement = $connection->prepare(
        'SELECT id, numero_pedido, estado_pedido
         FROM pedidos
         WHERE id = :id
         LIMIT 1
         FOR UPDATE'
    );
    $orderStatement->execute(['id' => $orderId]);
    $order = $orderStatement->fetch(PDO::FETCH_ASSOC);

    if ($order === false) {
        $connection->rollBack();
        sendJsonResponse(404, false, 'Pedido no encontrado.');
    }

    $currentStatus = (string) ($order['estado_pedido'] ?? '');

    if ($currentStatus === $newStatus) {
        $connection->rollBack();
        sendJsonResponse(
            200,
            true,
            'El pedido ya cuenta con ese estado.',
            [
                'order_id' => (int) ($order['id'] ?? 0),
                'numero_pedido' => (string) ($order['numero_pedido'] ?? ''),
                'estado_pedido' => $currentStatus,
            ]
        );
    }

    $updateStatement = $connection->prepare(
        'UPDATE pedidos
         SET estado_pedido = :estado_pedido,
             updated_at = NOW()
         WHERE id = :id'
    );
    $updateStatement->execute(
        [
            'estado_pedido' => $newStatus,
            'id' => $orderId,
        ]
    );

    createActivityLogEntry(
        $connection,
        'admin',
        (int) ($adminUser['id'] ?? 0),
        'actualizar_estado_pedido',
        'orders',
        $orderId,
        'Cambio de estado del pedido ' . $order['numero_pedido'] . ' de ' . $currentStatus . ' a ' . $newStatus . '.',
        $ipAddress
    );

    $connection->commit();

    sendJsonResponse(
        200,
        true,
        'Estado del pedido actualizado correctamente.',
        [
            'order_id' => (int) ($order['id'] ?? 0),
            'numero_pedido' => (string) ($order['numero_pedido'] ?? ''),
            'previous_status' => $currentStatus,
            'estado_pedido' => $newStatus,
        ]
    );
} catch (Throwable $exception) {
    if ($connection->inTransaction()) {
        $connection->rollBack();
    }

    sendJsonResponse(500, false, 'No fue posible actualizar el estado del pedido.');
}
