<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/common.php';

requireRequestMethod(['PUT']);
$adminUser = requireAdminAuth();

$requestData = readRequestData();

$pagoId = requirePositiveInt($requestData['pago_id'] ?? null, 'pago_id');
$resultadoTransaccion = optionalStringValue($requestData['resultado_transaccion'] ?? null, 30) ?? 'rechazado';
$mensajeTransaccion = optionalStringValue($requestData['mensaje_transaccion'] ?? null, 255);
$ipAddress = (string) ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');

$allowedResults = [
    'rechazado',
    'saldo_insuficiente',
    'tarjeta_invalida',
    'tarjeta_inactiva',
    'error',
];

if (!in_array($resultadoTransaccion, $allowedResults, true)) {
    sendJsonResponse(400, false, 'Campo invalido: resultado_transaccion.');
}

$defaultMessages = [
    'rechazado' => 'Pago rechazado manualmente.',
    'saldo_insuficiente' => 'La tarjeta ficticia no cuenta con saldo suficiente.',
    'tarjeta_invalida' => 'Los datos de la tarjeta ficticia no son validos.',
    'tarjeta_inactiva' => 'La tarjeta ficticia esta inactiva.',
    'error' => 'Ocurrio un error al procesar el pago.',
];

$connection = getDatabaseConnection();

try {
    $connection->beginTransaction();

    $paymentStatement = $connection->prepare(
        'SELECT p.id, p.pedido_id, p.estado_pago, p.referencia_pago, pe.numero_pedido, pe.estado_pedido
         FROM pagos p
         INNER JOIN pedidos pe ON pe.id = p.pedido_id
         WHERE p.id = :id
         LIMIT 1
         FOR UPDATE'
    );
    $paymentStatement->execute(['id' => $pagoId]);
    $pago = $paymentStatement->fetch();

    if ($pago === false) {
        $connection->rollBack();
        sendJsonResponse(404, false, 'Pago no encontrado.');
    }

    if (($pago['estado_pago'] ?? '') === 'rechazado') {
        $connection->rollBack();
        sendJsonResponse(
            200,
            true,
            'El pago ya estaba rechazado.',
            [
                'pago_id' => (int) $pago['id'],
                'pedido_id' => (int) $pago['pedido_id'],
                'referencia_pago' => $pago['referencia_pago'],
            ]
        );
    }

    if (in_array((string) ($pago['estado_pago'] ?? ''), ['confirmado', 'reembolsado'], true)) {
        $connection->rollBack();
        sendJsonResponse(400, false, 'No se puede rechazar un pago ya confirmado o reembolsado.');
    }

    $updatePaymentStatement = $connection->prepare(
        'UPDATE pagos
         SET estado_pago = :estado_pago,
             resultado_transaccion = :resultado_transaccion,
             mensaje_transaccion = :mensaje_transaccion,
             fecha_pago = COALESCE(fecha_pago, NOW()),
             updated_at = NOW()
         WHERE id = :id'
    );
    $updatePaymentStatement->execute(
        [
            'estado_pago' => 'rechazado',
            'resultado_transaccion' => $resultadoTransaccion,
            'mensaje_transaccion' => $mensajeTransaccion ?? $defaultMessages[$resultadoTransaccion],
            'id' => $pago['id'],
        ]
    );

    if (($pago['estado_pedido'] ?? '') !== 'cancelado') {
        $updateOrderStatement = $connection->prepare(
            'UPDATE pedidos
             SET estado_pedido = :estado_pedido, updated_at = NOW()
             WHERE id = :id'
        );
        $updateOrderStatement->execute(
            [
                'estado_pedido' => 'pendiente',
                'id' => $pago['pedido_id'],
            ]
        );
    }

    createActivityLogEntry(
        $connection,
        'admin',
        (int) $adminUser['id'],
        'rechazar_pago',
        'payments',
        (int) $pago['id'],
        'Rechazo manual del pago ' . $pago['referencia_pago'] . ' para el pedido ' . $pago['numero_pedido'] . '.',
        $ipAddress
    );

    $connection->commit();

    sendJsonResponse(
        200,
        true,
        'Pago rechazado correctamente.',
        [
            'pago_id' => (int) $pago['id'],
            'pedido_id' => (int) $pago['pedido_id'],
            'referencia_pago' => $pago['referencia_pago'],
            'estado_pago' => 'rechazado',
            'resultado_transaccion' => $resultadoTransaccion,
        ]
    );
} catch (Throwable $exception) {
    if ($connection->inTransaction()) {
        $connection->rollBack();
    }

    sendJsonResponse(
        500,
        false,
        'No fue posible rechazar el pago.'
    );
}
