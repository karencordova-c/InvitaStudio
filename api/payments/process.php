<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/common.php';
require_once dirname(__DIR__) . '/status/common.php';

requireRequestMethod(['POST']);

$requestData = readRequestData();

$lookupInput = validateStatusLookupInput($requestData);
$numeroTarjeta = preg_replace('/\D+/', '', requireStringValue($requestData['numero_tarjeta'] ?? null, 'numero_tarjeta', 25));
$titular = requireStringValue($requestData['titular'] ?? null, 'titular', 150);
$fechaExpiracion = requireStringValue($requestData['fecha_expiracion'] ?? null, 'fecha_expiracion', 5);
$cvv = requireStringValue($requestData['cvv'] ?? null, 'cvv', 4);

$validationErrors = mergeValidationErrors(
    validateStringLength($titular, 'titular', 3, 150)
);

if ($numeroTarjeta === null || strlen($numeroTarjeta) < 13 || strlen($numeroTarjeta) > 19) {
    addValidationMessage($validationErrors, 'numero_tarjeta', 'El numero de tarjeta de prueba no es valido.');
}

if (preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $fechaExpiracion) !== 1) {
    addValidationMessage($validationErrors, 'fecha_expiracion', 'La fecha debe usar el formato MM/YY.');
}

if (preg_match('/^\d{3,4}$/', $cvv) !== 1) {
    addValidationMessage($validationErrors, 'cvv', 'El CVV debe contener 3 o 4 digitos.');
}

assertValidInput($validationErrors);

$connection = getDatabaseConnection();
$ipAddress = getClientIpAddress();
$referenceCode = generateReferenceCode('sim');

try {
    $connection->beginTransaction();

    $orderStatement = $connection->prepare(
        'SELECT
            p.id,
            numero_pedido,
            estado_pedido,
            c.nombre AS cliente_nombre,
            c.correo AS cliente_correo
         FROM pedidos p
         INNER JOIN clientes c ON c.id = p.cliente_id
         WHERE p.numero_pedido = :numero_pedido
           AND c.correo = :correo
         LIMIT 1
         FOR UPDATE'
    );
    $orderStatement->execute(
        [
            'numero_pedido' => $lookupInput['numero_pedido'],
            'correo' => $lookupInput['correo'],
        ]
    );
    $pedido = $orderStatement->fetch(PDO::FETCH_ASSOC);

    if ($pedido === false) {
        $connection->rollBack();
        errorResponse('Pedido no encontrado', [], 404);
    }

    $pedidoId = (int) ($pedido['id'] ?? 0);

    if ((string) ($pedido['estado_pedido'] ?? '') !== 'pendiente') {
        $connection->rollBack();
        errorResponse(
            'El pedido no admite un nuevo pago simulado en su estado actual.',
            ['estado_pedido' => [(string) ($pedido['estado_pedido'] ?? '')]],
            400
        );
    }

    $paymentStatement = $connection->prepare(
        'SELECT
            id,
            pedido_id,
            monto_pago,
            estado_pago
         FROM pagos
         WHERE pedido_id = :pedido_id
         ORDER BY id DESC
         LIMIT 1
         FOR UPDATE'
    );
    $paymentStatement->execute(['pedido_id' => $pedidoId]);
    $pago = $paymentStatement->fetch(PDO::FETCH_ASSOC);

    if ($pago === false) {
        $connection->rollBack();
        errorResponse('El pedido no tiene un pago configurado.', [], 409);
    }

    $montoPago = round((float) ($pago['monto_pago'] ?? 0), 2);

    if ($montoPago <= 0) {
        $connection->rollBack();
        errorResponse('El pedido no tiene un monto de pago valido.', [], 409);
    }

    if (in_array((string) ($pago['estado_pago'] ?? ''), ['confirmado', 'reembolsado'], true)) {
        $connection->rollBack();
        errorResponse('El pago ya no puede procesarse nuevamente.', [], 400);
    }

    $cardStatement = $connection->prepare(
        'SELECT
            id,
            titular,
            numero_tarjeta,
            fecha_expiracion,
            cvv,
            saldo_disponible,
            activa
         FROM tarjetas_prueba
         WHERE numero_tarjeta = :numero_tarjeta
         LIMIT 1
         FOR UPDATE'
    );
    $cardStatement->execute(['numero_tarjeta' => $numeroTarjeta]);
    $tarjeta = $cardStatement->fetch(PDO::FETCH_ASSOC);

    $tarjetaPruebaId = $tarjeta !== false ? (int) $tarjeta['id'] : null;
    $estadoPago = 'rechazado';
    $resultadoTransaccion = 'tarjeta_invalida';
    $mensajeTransaccion = 'La tarjeta de prueba no es valida para esta simulacion.';
    $saldoRestante = null;

    if ($tarjeta !== false) {
        if (
            (string) ($tarjeta['fecha_expiracion'] ?? '') !== $fechaExpiracion
            || (string) ($tarjeta['cvv'] ?? '') !== $cvv
        ) {
            $resultadoTransaccion = 'tarjeta_invalida';
            $mensajeTransaccion = 'Los datos de la tarjeta de prueba no coinciden.';
        } elseif ((int) ($tarjeta['activa'] ?? 0) !== 1) {
            $resultadoTransaccion = 'tarjeta_inactiva';
            $mensajeTransaccion = 'La tarjeta de prueba esta inactiva.';
        } elseif ((float) ($tarjeta['saldo_disponible'] ?? 0) < $montoPago) {
            $resultadoTransaccion = 'saldo_insuficiente';
            $mensajeTransaccion = 'La tarjeta de prueba no cuenta con saldo suficiente.';
            $saldoRestante = round((float) ($tarjeta['saldo_disponible'] ?? 0), 2);
        } else {
            $estadoPago = 'confirmado';
            $resultadoTransaccion = 'aprobado';
            $mensajeTransaccion = 'Pago simulado aprobado correctamente.';
            $saldoRestante = round(((float) $tarjeta['saldo_disponible']) - $montoPago, 2);

            $updateCardStatement = $connection->prepare(
                'UPDATE tarjetas_prueba
                 SET saldo_disponible = :saldo_disponible,
                     updated_at = NOW()
                 WHERE id = :id'
            );
            $updateCardStatement->execute(
                [
                    'saldo_disponible' => $saldoRestante,
                    'id' => $tarjeta['id'],
                ]
            );

            $updateOrderStatement = $connection->prepare(
                'UPDATE pedidos
                 SET estado_pedido = :estado_pedido,
                     updated_at = NOW()
                 WHERE id = :id'
            );
            $updateOrderStatement->execute(
                [
                    'estado_pedido' => 'pago_confirmado',
                    'id' => $pedidoId,
                ]
            );
        }
    }

    $updatePaymentStatement = $connection->prepare(
        'UPDATE pagos
         SET tarjeta_prueba_id = :tarjeta_prueba_id,
             metodo_pago = :metodo_pago,
             estado_pago = :estado_pago,
             resultado_transaccion = :resultado_transaccion,
             mensaje_transaccion = :mensaje_transaccion,
             referencia_pago = :referencia_pago,
             fecha_pago = NOW(),
             updated_at = NOW()
         WHERE id = :id'
    );
    $updatePaymentStatement->execute(
        [
            'tarjeta_prueba_id' => $tarjetaPruebaId,
            'metodo_pago' => 'tarjeta_prueba',
            'estado_pago' => $estadoPago,
            'resultado_transaccion' => $resultadoTransaccion,
            'mensaje_transaccion' => $mensajeTransaccion,
            'referencia_pago' => $referenceCode,
            'id' => $pago['id'],
        ]
    );

    createActivityLogEntry(
        $connection,
        'sistema',
        null,
        $estadoPago === 'confirmado' ? 'pago_simulado_aprobado' : 'pago_simulado_rechazado',
        'payments',
        (int) $pago['id'],
        'Procesamiento de pago simulado para el pedido ' . $pedido['numero_pedido'] . ': ' . $resultadoTransaccion . '. ' . $mensajeTransaccion,
        $ipAddress
    );

    $connection->commit();

    if ($estadoPago === 'confirmado') {
        sendPaymentConfirmation(
            $connection,
            [
                'pedido_id' => $pedidoId,
                'numero_pedido' => (string) $pedido['numero_pedido'],
                'cliente_nombre' => (string) ($pedido['cliente_nombre'] ?? ''),
                'cliente_correo' => (string) ($pedido['cliente_correo'] ?? ''),
                'monto_pago' => $montoPago,
                'estado_pago' => 'confirmado',
                'referencia_pago' => $referenceCode,
                'fecha_pago' => date('Y-m-d H:i:s'),
            ]
        );
    }

    $responsePayload = [
        'numero_pedido' => (string) $pedido['numero_pedido'],
        'monto_pago' => $montoPago,
        'estado_pago' => $estadoPago,
        'resultado_transaccion' => $resultadoTransaccion,
        'mensaje_transaccion' => $mensajeTransaccion,
        'referencia_pago' => $referenceCode,
        'saldo_restante' => $saldoRestante,
        'estado_pedido' => $estadoPago === 'confirmado' ? 'pago_confirmado' : 'pendiente',
        'simulacion_local' => true,
    ];

    if ($estadoPago === 'confirmado') {
        successResponse('Pago procesado correctamente.', $responsePayload);
    }

    jsonResponse(
        200,
        [
            'success' => false,
            'message' => $mensajeTransaccion,
            'data' => $responsePayload,
            'errors' => [],
        ]
    );
} catch (Throwable $exception) {
    if ($connection->inTransaction()) {
        $connection->rollBack();
    }

    errorResponse('No fue posible procesar el pago simulado.', [], 500);
}
