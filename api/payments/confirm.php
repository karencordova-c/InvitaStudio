<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/common.php';

requireRequestMethod(['PUT']);
$adminUser = requireAdminAuth();

$requestData = readRequestData();

$pagoId = requirePositiveInt($requestData['pago_id'] ?? null, 'pago_id');
$mensajeTransaccion = optionalStringValue($requestData['mensaje_transaccion'] ?? null, 255);
$ipAddress = (string) ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');

$connection = getDatabaseConnection();

try {
    $connection->beginTransaction();

    $paymentStatement = $connection->prepare(
        'SELECT
            p.id,
            p.pedido_id,
            p.tarjeta_prueba_id,
            p.estado_pago,
            p.resultado_transaccion,
            p.referencia_pago,
            p.monto_pago,
            p.fecha_pago,
            pe.numero_pedido,
            c.nombre AS cliente_nombre,
            c.correo AS cliente_correo
         FROM pagos p
         INNER JOIN pedidos pe ON pe.id = p.pedido_id
         INNER JOIN clientes c ON c.id = pe.cliente_id
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

    if (($pago['estado_pago'] ?? '') === 'confirmado') {
        $connection->rollBack();
        sendJsonResponse(
            200,
            true,
            'El pago ya estaba confirmado.',
            [
                'pago_id' => (int) $pago['id'],
                'pedido_id' => (int) $pago['pedido_id'],
                'referencia_pago' => $pago['referencia_pago'],
            ]
        );
    }

    if (($pago['estado_pago'] ?? '') !== 'pendiente') {
        $connection->rollBack();
        sendJsonResponse(400, false, 'Solo se pueden confirmar pagos pendientes.');
    }

    if (
        $pago['tarjeta_prueba_id'] !== null
        && $pago['resultado_transaccion'] !== null
        && $pago['resultado_transaccion'] !== 'aprobado'
    ) {
        $connection->rollBack();
        sendJsonResponse(400, false, 'No se puede confirmar manualmente un pago simulado rechazado.');
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
            'estado_pago' => 'confirmado',
            'resultado_transaccion' => 'aprobado',
            'mensaje_transaccion' => $mensajeTransaccion ?? 'Pago confirmado manualmente.',
            'id' => $pago['id'],
        ]
    );

    $updateOrderStatement = $connection->prepare(
        'UPDATE pedidos
         SET estado_pedido = :estado_pedido, updated_at = NOW()
         WHERE id = :id'
    );
    $updateOrderStatement->execute(
        [
            'estado_pedido' => 'pago_confirmado',
            'id' => $pago['pedido_id'],
        ]
    );

    createActivityLogEntry(
        $connection,
        'admin',
        (int) $adminUser['id'],
        'confirmar_pago',
        'payments',
        (int) $pago['id'],
        'Confirmacion manual del pago ' . $pago['referencia_pago'] . ' para el pedido ' . $pago['numero_pedido'] . '.',
        $ipAddress
    );

    $connection->commit();

    sendPaymentConfirmation(
        $connection,
        [
            'pedido_id' => (int) $pago['pedido_id'],
            'numero_pedido' => (string) $pago['numero_pedido'],
            'cliente_nombre' => (string) ($pago['cliente_nombre'] ?? ''),
            'cliente_correo' => (string) ($pago['cliente_correo'] ?? ''),
            'monto_pago' => (float) ($pago['monto_pago'] ?? 0),
            'estado_pago' => 'confirmado',
            'referencia_pago' => (string) $pago['referencia_pago'],
            'fecha_pago' => (string) ($pago['fecha_pago'] ?? date('Y-m-d H:i:s')),
        ]
    );

    sendJsonResponse(
        200,
        true,
        'Pago confirmado correctamente.',
        [
            'pago_id' => (int) $pago['id'],
            'pedido_id' => (int) $pago['pedido_id'],
            'referencia_pago' => $pago['referencia_pago'],
            'estado_pago' => 'confirmado',
            'resultado_transaccion' => 'aprobado',
        ]
    );
} catch (Throwable $exception) {
    if ($connection->inTransaction()) {
        $connection->rollBack();
    }

    sendJsonResponse(
        500,
        false,
        'No fue posible confirmar el pago.'
    );
}
