<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/common.php';

requireRequestMethod(['POST']);
$adminUser = requireAdminAuth();

$orderId = requirePositiveInt($_POST['order_id'] ?? $_POST['pedido_id'] ?? null, 'order_id');
$deliveryFormat = normalizeString($_POST['formato_entrega'] ?? '');
$deliveryNotes = optionalStringValue($_POST['notas_entrega'] ?? null, 2000);

assertValidInput(
    validateEnumValue($deliveryFormat, 'formato_entrega', getAllowedDeliveryFormats())
);

if (!isset($_FILES['archivo_final']) || !is_array($_FILES['archivo_final'])) {
    validationErrorResponse(['archivo_final' => ['Debes seleccionar un archivo valido.']]);
}

$validatedFile = validateDeliveryUploadFile($_FILES['archivo_final'], $deliveryFormat);
$connection = getDatabaseConnection();
$ipAddress = getClientIpAddress();
$movedFilePath = null;

try {
    $connection->beginTransaction();

    $orderStatement = $connection->prepare(
        'SELECT
            p.id,
            p.numero_pedido,
            p.estado_pedido,
            c.nombre AS cliente_nombre,
            c.correo AS cliente_correo
         FROM pedidos p
         INNER JOIN clientes c ON c.id = p.cliente_id
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

    $orderStatus = (string) ($order['estado_pedido'] ?? '');

    if ($orderStatus === 'cancelado') {
        $connection->rollBack();
        validationErrorResponse(['order_id' => ['No es posible registrar entregas para pedidos cancelados.']]);
    }

    $storagePaths = buildDeliveryStoragePaths(
        (string) ($order['numero_pedido'] ?? ''),
        (string) ($validatedFile['original_extension'] ?? '')
    );
    $movedFilePath = (string) ($storagePaths['absolute_path'] ?? '');

    if (!move_uploaded_file((string) ($validatedFile['temporary_path'] ?? ''), $movedFilePath)) {
        throw new RuntimeException('No fue posible guardar el archivo cargado.');
    }

    $insertStatement = $connection->prepare(
        'INSERT INTO entregas (
            pedido_id,
            formato_entrega,
            archivo_final,
            fecha_entrega,
            notas_entrega,
            created_at,
            updated_at
        ) VALUES (
            :pedido_id,
            :formato_entrega,
            :archivo_final,
            NOW(),
            :notas_entrega,
            NOW(),
            NOW()
        )'
    );
    $insertStatement->execute(
        [
            'pedido_id' => $orderId,
            'formato_entrega' => $deliveryFormat,
            'archivo_final' => (string) ($storagePaths['relative_path'] ?? ''),
            'notas_entrega' => $deliveryNotes,
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
            'estado_pedido' => 'entregado',
            'id' => $orderId,
        ]
    );

    $storedFileName = (string) ($storagePaths['file_name'] ?? '');

    createActivityLogEntry(
        $connection,
        'admin',
        (int) ($adminUser['id'] ?? 0),
        'subir_archivo_entrega',
        'deliveries',
        $orderId,
        'Upload del archivo final ' . $storedFileName . ' para el pedido ' . $order['numero_pedido'] . '.',
        $ipAddress
    );

    createActivityLogEntry(
        $connection,
        'admin',
        (int) ($adminUser['id'] ?? 0),
        'registrar_entrega',
        'deliveries',
        $orderId,
        'Entrega registrada para el pedido ' . $order['numero_pedido'] . ' con formato ' . $deliveryFormat . '.',
        $ipAddress
    );

    $connection->commit();

    sendDeliveryNotification(
        $connection,
        [
            'pedido_id' => $orderId,
            'numero_pedido' => (string) ($order['numero_pedido'] ?? ''),
            'cliente_nombre' => (string) ($order['cliente_nombre'] ?? ''),
            'cliente_correo' => (string) ($order['cliente_correo'] ?? ''),
            'formato_entrega' => $deliveryFormat,
            'fecha_entrega' => date('Y-m-d H:i:s'),
            'notas_entrega' => $deliveryNotes ?? '',
        ]
    );

    successResponse(
        'Entrega registrada correctamente',
        [
            'order_id' => $orderId,
            'numero_pedido' => (string) ($order['numero_pedido'] ?? ''),
            'estado_pedido' => 'entregado',
            'formato_entrega' => $deliveryFormat,
            'archivo_final' => (string) ($storagePaths['relative_path'] ?? ''),
            'archivo_nombre' => $storedFileName,
            'mime_type' => (string) ($validatedFile['detected_mime_type'] ?? ''),
        ],
        201
    );
} catch (Throwable $exception) {
    if ($connection->inTransaction()) {
        $connection->rollBack();
    }

    if (is_string($movedFilePath) && $movedFilePath !== '' && is_file($movedFilePath)) {
        @unlink($movedFilePath);
    }

    errorResponse('No fue posible registrar la entrega.', [], 500);
}
