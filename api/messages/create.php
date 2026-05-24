<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/common.php';
require_once dirname(__DIR__) . '/status/common.php';

requireRequestMethod(['POST']);

$requestData = sanitizeArray($_POST);
$messageText = sanitizeString($requestData['mensaje'] ?? '');
$connection = getDatabaseConnection();
$ipAddress = getClientIpAddress();
$adminUser = getAdminUser();
$validatedFile = null;
$movedFilePath = null;

assertValidInput(
    validateStringLength($messageText, 'mensaje', 5, 2000)
);

if (isset($_FILES['archivo_adjunto']) && is_array($_FILES['archivo_adjunto'])) {
    $uploadError = (int) ($_FILES['archivo_adjunto']['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($uploadError !== UPLOAD_ERR_NO_FILE) {
        $validatedFile = validateMessageAttachmentUpload($_FILES['archivo_adjunto']);
    }
}

try {
    $connection->beginTransaction();

    $actorContext = resolveMessageActorContext($connection, $requestData, $adminUser);
    $orderId = (int) ($actorContext['order']['id'] ?? 0);
    $orderNumber = (string) ($actorContext['order']['numero_pedido'] ?? '');
    $actorType = (string) ($actorContext['actor_type'] ?? 'cliente');
    $actorUserId = isset($actorContext['actor_user_id']) ? (int) $actorContext['actor_user_id'] : null;
    $storedAttachmentPath = null;
    $storedAttachmentName = null;

    if ($validatedFile !== null) {
        $storagePaths = buildMessageAttachmentStoragePaths(
            $orderNumber,
            (string) ($validatedFile['original_extension'] ?? '')
        );
        $movedFilePath = (string) ($storagePaths['absolute_path'] ?? '');

        if (!move_uploaded_file((string) ($validatedFile['temporary_path'] ?? ''), $movedFilePath)) {
            throw new RuntimeException('No fue posible guardar el archivo cargado.');
        }

        $storedAttachmentPath = (string) ($storagePaths['relative_path'] ?? '');
        $storedAttachmentName = (string) ($storagePaths['file_name'] ?? '');
    }

    $insertStatement = $connection->prepare(
        'INSERT INTO mensajes_pedido (
            pedido_id,
            tipo_usuario,
            mensaje,
            archivo_adjunto,
            created_at
        ) VALUES (
            :pedido_id,
            :tipo_usuario,
            :mensaje,
            :archivo_adjunto,
            NOW()
        )'
    );
    $insertStatement->execute(
        [
            'pedido_id' => $orderId,
            'tipo_usuario' => $actorType,
            'mensaje' => $messageText,
            'archivo_adjunto' => $storedAttachmentPath,
        ]
    );

    $messageId = (int) $connection->lastInsertId();
    $actorLabel = $actorType === 'admin' ? 'admin' : 'cliente';

    createActivityLogEntry(
        $connection,
        $actorType,
        $actorUserId,
        'mensaje_enviado_' . $actorLabel,
        'messages',
        $orderId,
        'Mensaje enviado para el pedido ' . $orderNumber . '.',
        $ipAddress
    );

    if ($storedAttachmentPath !== null && $storedAttachmentName !== null) {
        createActivityLogEntry(
            $connection,
            $actorType,
            $actorUserId,
            'adjunto_enviado_' . $actorLabel,
            'messages',
            $orderId,
            'Adjunto enviado en la conversacion del pedido ' . $orderNumber . ': ' . $storedAttachmentName . '.',
            $ipAddress
        );
    }

    $connection->commit();

    sendClarificationNotification(
        $connection,
        [
            'pedido_id' => $orderId,
            'numero_pedido' => $orderNumber,
            'tipo_usuario' => $actorType,
            'mensaje' => $messageText,
            'cliente_nombre' => (string) ($actorContext['order']['cliente_nombre'] ?? ''),
            'cliente_correo' => (string) ($actorContext['order']['cliente_correo'] ?? ''),
        ]
    );

    successResponse(
        'Mensaje enviado',
        [
            'message_id' => $messageId,
            'pedido_id' => $orderId,
            'numero_pedido' => $orderNumber,
            'tipo_usuario' => $actorType,
            'archivo_adjunto' => $storedAttachmentPath,
            'archivo_nombre' => $storedAttachmentName,
            'mime_type' => $validatedFile['detected_mime_type'] ?? null,
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

    errorResponse('Error al enviar mensaje', [], 500);
}

function resolveMessageActorContext(PDO $connection, array $requestData, ?array $adminUser): array
{
    if ($adminUser !== null) {
        $orderId = requirePositiveInt($requestData['order_id'] ?? $requestData['pedido_id'] ?? null, 'order_id');
        $order = findOrderForMessagesById($connection, $orderId, true);

        return [
            'actor_type' => 'admin',
            'actor_user_id' => (int) ($adminUser['id'] ?? 0),
            'order' => $order,
        ];
    }

    $validatedLookup = validateStatusLookupInput($requestData);
    $order = findOrderForMessagesByLookup(
        $connection,
        $validatedLookup['numero_pedido'],
        $validatedLookup['correo'],
        true
    );

    return [
        'actor_type' => 'cliente',
        'actor_user_id' => null,
        'order' => $order,
    ];
}

function findOrderForMessagesById(PDO $connection, int $orderId, bool $lock = false): array
{
    $query = 'SELECT
            p.id,
            p.numero_pedido,
            c.nombre AS cliente_nombre,
            c.correo AS cliente_correo
        FROM pedidos p
        INNER JOIN clientes c ON c.id = p.cliente_id
        WHERE p.id = :id
        LIMIT 1';

    if ($lock) {
        $query .= ' FOR UPDATE';
    }

    $statement = $connection->prepare($query);
    $statement->execute(['id' => $orderId]);
    $order = $statement->fetch(PDO::FETCH_ASSOC);

    if ($order === false) {
        sendJsonResponse(404, false, 'Pedido no encontrado.');
    }

    return [
        'id' => (int) ($order['id'] ?? 0),
        'numero_pedido' => (string) ($order['numero_pedido'] ?? ''),
        'cliente_nombre' => (string) ($order['cliente_nombre'] ?? ''),
        'cliente_correo' => (string) ($order['cliente_correo'] ?? ''),
    ];
}

function findOrderForMessagesByLookup(PDO $connection, string $orderNumber, string $email, bool $lock = false): array
{
    $query = 'SELECT
            p.id,
            p.numero_pedido,
            c.nombre AS cliente_nombre,
            c.correo AS cliente_correo
        FROM pedidos p
        INNER JOIN clientes c ON c.id = p.cliente_id
        WHERE p.numero_pedido = :numero_pedido
          AND c.correo = :correo
        LIMIT 1';

    if ($lock) {
        $query .= ' FOR UPDATE';
    }

    $statement = $connection->prepare($query);
    $statement->execute(
        [
            'numero_pedido' => $orderNumber,
            'correo' => $email,
        ]
    );
    $order = $statement->fetch(PDO::FETCH_ASSOC);

    if ($order === false) {
        sendJsonResponse(404, false, 'Pedido no encontrado.');
    }

    return [
        'id' => (int) ($order['id'] ?? 0),
        'numero_pedido' => (string) ($order['numero_pedido'] ?? ''),
        'cliente_nombre' => (string) ($order['cliente_nombre'] ?? ''),
        'cliente_correo' => (string) ($order['cliente_correo'] ?? ''),
    ];
}
