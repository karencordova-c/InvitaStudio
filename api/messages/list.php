<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/common.php';
require_once dirname(__DIR__) . '/status/common.php';

requireRequestMethod(['GET']);

$connection = getDatabaseConnection();
$adminUser = getAdminUser();

try {
    if ($adminUser !== null) {
        $orderId = filter_var($_GET['order_id'] ?? $_GET['id'] ?? null, FILTER_VALIDATE_INT);

        if ($orderId !== false && (int) $orderId > 0) {
            successResponse(
                'Conversacion obtenida correctamente.',
                buildAdminConversationDetailResponse($connection, (int) $orderId)
            );
        }

        successResponse(
            'Conversaciones obtenidas correctamente.',
            [
                'conversations' => fetchAdminConversationSummaries($connection),
            ]
        );
    }

    $validatedLookup = validateStatusLookupInput($_GET);
    $order = findPublicOrderStatusRecord(
        $connection,
        $validatedLookup['numero_pedido'],
        $validatedLookup['correo']
    );

    if ($order === null) {
        sendJsonResponse(404, false, 'Pedido no encontrado.');
    }

    $orderId = (int) ($order['id'] ?? 0);

    successResponse(
        'Conversacion obtenida correctamente.',
        buildPublicConversationDetailResponse(
            $connection,
            $orderId,
            $validatedLookup['numero_pedido'],
            $validatedLookup['correo']
        )
    );
} catch (Throwable $exception) {
    errorResponse('No fue posible obtener los mensajes.', [], 500);
}

function fetchAdminConversationSummaries(PDO $connection): array
{
    $statement = $connection->query(
        'SELECT
            p.id AS pedido_id,
            p.numero_pedido,
            p.estado_pedido,
            c.nombre AS cliente_nombre,
            c.correo AS cliente_correo,
            COUNT(m.id) AS total_mensajes,
            MAX(m.created_at) AS ultimo_mensaje_fecha,
            (
                SELECT m2.mensaje
                FROM mensajes_pedido m2
                WHERE m2.pedido_id = p.id
                ORDER BY m2.created_at DESC, m2.id DESC
                LIMIT 1
            ) AS ultimo_mensaje,
            (
                SELECT m3.tipo_usuario
                FROM mensajes_pedido m3
                WHERE m3.pedido_id = p.id
                ORDER BY m3.created_at DESC, m3.id DESC
                LIMIT 1
            ) AS ultimo_mensaje_tipo
         FROM mensajes_pedido m
         INNER JOIN pedidos p ON p.id = m.pedido_id
         INNER JOIN clientes c ON c.id = p.cliente_id
         GROUP BY
            p.id,
            p.numero_pedido,
            p.estado_pedido,
            c.nombre,
            c.correo
         ORDER BY ultimo_mensaje_fecha DESC, p.id DESC'
    );
    $rows = $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];

    return array_map(
        static function (array $row): array {
            return [
                'pedido_id' => (int) ($row['pedido_id'] ?? 0),
                'numero_pedido' => (string) ($row['numero_pedido'] ?? ''),
                'estado_pedido' => (string) ($row['estado_pedido'] ?? ''),
                'cliente_nombre' => (string) ($row['cliente_nombre'] ?? ''),
                'cliente_correo' => (string) ($row['cliente_correo'] ?? ''),
                'total_mensajes' => (int) ($row['total_mensajes'] ?? 0),
                'ultimo_mensaje' => (string) ($row['ultimo_mensaje'] ?? ''),
                'ultimo_mensaje_tipo' => (string) ($row['ultimo_mensaje_tipo'] ?? ''),
                'ultimo_mensaje_fecha' => (string) ($row['ultimo_mensaje_fecha'] ?? ''),
            ];
        },
        $rows
    );
}

function buildAdminConversationDetailResponse(PDO $connection, int $orderId): array
{
    $conversation = fetchConversationOrderDataById($connection, $orderId);

    return [
        'conversation' => $conversation,
        'messages' => fetchConversationMessages($connection, $orderId, 'admin', $conversation['numero_pedido']),
    ];
}

function buildPublicConversationDetailResponse(
    PDO $connection,
    int $orderId,
    string $numeroPedido,
    string $correo
): array {
    $conversation = fetchConversationOrderDataById($connection, $orderId);

    return [
        'conversation' => [
            'pedido_id' => $conversation['pedido_id'],
            'numero_pedido' => $conversation['numero_pedido'],
            'estado_pedido' => $conversation['estado_pedido'],
            'total_mensajes' => $conversation['total_mensajes'],
            'ultimo_mensaje_fecha' => $conversation['ultimo_mensaje_fecha'],
        ],
        'messages' => fetchConversationMessages($connection, $orderId, 'cliente', $numeroPedido, $correo),
    ];
}

function fetchConversationOrderDataById(PDO $connection, int $orderId): array
{
    $statement = $connection->prepare(
        'SELECT
            p.id AS pedido_id,
            p.numero_pedido,
            p.estado_pedido,
            c.nombre AS cliente_nombre,
            c.correo AS cliente_correo,
            COUNT(m.id) AS total_mensajes,
            MAX(m.created_at) AS ultimo_mensaje_fecha
         FROM pedidos p
         INNER JOIN clientes c ON c.id = p.cliente_id
         LEFT JOIN mensajes_pedido m ON m.pedido_id = p.id
         WHERE p.id = :id
         GROUP BY p.id, p.numero_pedido, p.estado_pedido, c.nombre, c.correo
         LIMIT 1'
    );
    $statement->execute(['id' => $orderId]);
    $row = $statement->fetch(PDO::FETCH_ASSOC);

    if ($row === false) {
        sendJsonResponse(404, false, 'Pedido no encontrado.');
    }

    return [
        'pedido_id' => (int) ($row['pedido_id'] ?? 0),
        'numero_pedido' => (string) ($row['numero_pedido'] ?? ''),
        'estado_pedido' => (string) ($row['estado_pedido'] ?? ''),
        'cliente_nombre' => (string) ($row['cliente_nombre'] ?? ''),
        'cliente_correo' => (string) ($row['cliente_correo'] ?? ''),
        'total_mensajes' => (int) ($row['total_mensajes'] ?? 0),
        'ultimo_mensaje_fecha' => (string) ($row['ultimo_mensaje_fecha'] ?? ''),
    ];
}

function fetchConversationMessages(
    PDO $connection,
    int $orderId,
    string $accessMode,
    string $numeroPedido,
    ?string $correo = null
): array {
    $statement = $connection->prepare(
        'SELECT
            id,
            pedido_id,
            tipo_usuario,
            mensaje,
            archivo_adjunto,
            created_at
         FROM mensajes_pedido
         WHERE pedido_id = :pedido_id
         ORDER BY created_at ASC, id ASC'
    );
    $statement->execute(['pedido_id' => $orderId]);
    $rows = $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];

    return array_map(
        static function (array $row) use ($accessMode, $numeroPedido, $correo): array {
            $attachmentPath = (string) ($row['archivo_adjunto'] ?? '');
            $attachment = null;

            if ($attachmentPath !== '') {
                $downloadUrl = buildMessageDownloadUrl(
                    (int) ($row['id'] ?? 0),
                    $accessMode,
                    $numeroPedido,
                    $correo
                );

                $attachment = [
                    'available' => resolveStoredMessageAbsolutePath($attachmentPath) !== null,
                    'file_name' => getMessageAttachmentFileName($attachmentPath),
                    'download_url' => $downloadUrl,
                ];
            }

            return [
                'id' => (int) ($row['id'] ?? 0),
                'pedido_id' => (int) ($row['pedido_id'] ?? 0),
                'tipo_usuario' => (string) ($row['tipo_usuario'] ?? ''),
                'autor' => getMessageAuthorLabel((string) ($row['tipo_usuario'] ?? '')),
                'mensaje' => (string) ($row['mensaje'] ?? ''),
                'created_at' => (string) ($row['created_at'] ?? ''),
                'attachment' => $attachment,
            ];
        },
        $rows
    );
}

function buildMessageDownloadUrl(int $messageId, string $accessMode, string $numeroPedido, ?string $correo = null): string
{
    $queryParams = ['id' => (string) $messageId];

    if ($accessMode === 'cliente') {
        $queryParams['numero_pedido'] = $numeroPedido;
        $queryParams['correo'] = (string) $correo;
    }

    return buildAbsoluteApiUrl('messages/download.php', $queryParams);
}
