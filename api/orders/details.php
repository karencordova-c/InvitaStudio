<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/common.php';

requireRequestMethod(['GET']);
requireAdminAuth();

$orderId = requirePositiveInt($_GET['id'] ?? $_GET['order_id'] ?? null, 'id');
$connection = getDatabaseConnection();

try {
    $orderStatement = $connection->prepare(
        'SELECT
            p.id,
            p.numero_pedido,
            p.tipo_evento,
            p.nombre_evento,
            p.fecha_evento,
            p.hora_evento,
            p.ubicacion_evento,
            p.estilo_diseno,
            p.colores,
            p.tematica,
            p.informacion_adicional,
            p.estado_pedido,
            p.created_at,
            p.updated_at,
            c.id AS cliente_id,
            c.nombre AS cliente_nombre,
            c.correo AS cliente_correo,
            c.telefono AS cliente_telefono,
            c.medio_contacto AS cliente_medio_contacto,
            lp.id AS pago_id,
            lp.metodo_pago,
            lp.monto_pago,
            lp.estado_pago,
            lp.resultado_transaccion,
            lp.mensaje_transaccion,
            lp.referencia_pago,
            lp.fecha_pago,
            lp.created_at AS pago_created_at,
            d.id AS entrega_id,
            d.formato_entrega,
            d.archivo_final,
            d.fecha_entrega,
            d.notas_entrega
         FROM pedidos p
         INNER JOIN clientes c ON c.id = p.cliente_id
         LEFT JOIN pagos lp ON lp.id = (
            SELECT p2.id
            FROM pagos p2
            WHERE p2.pedido_id = p.id
            ORDER BY COALESCE(p2.fecha_pago, p2.created_at) DESC, p2.id DESC
            LIMIT 1
         )
         LEFT JOIN entregas d ON d.id = (
            SELECT d2.id
            FROM entregas d2
            WHERE d2.pedido_id = p.id
            ORDER BY d2.fecha_entrega DESC, d2.id DESC
            LIMIT 1
         )
         WHERE p.id = :id
         LIMIT 1'
    );
    $orderStatement->execute(['id' => $orderId]);
    $order = $orderStatement->fetch(PDO::FETCH_ASSOC);

    if ($order === false) {
        sendJsonResponse(404, false, 'Pedido no encontrado.');
    }

    $activityStatement = $connection->prepare(
        'SELECT
            id,
            accion,
            modulo,
            descripcion,
            created_at
         FROM actividad_log
         WHERE (
                referencia_id = :order_reference_id
                AND modulo IN ("orders", "deliveries", "messages")
            )
            OR (
                modulo = "payments"
                AND EXISTS (
                    SELECT 1
                    FROM pagos pa
                    WHERE pa.id = actividad_log.referencia_id
                      AND pa.pedido_id = :payment_order_id
                )
            )
         ORDER BY created_at DESC, id DESC
         LIMIT 10'
    );
    $activityStatement->execute(
        [
            'order_reference_id' => $orderId,
            'payment_order_id' => $orderId,
        ]
    );
    $activity = $activityStatement->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $requestedDeliveryFormat = extractRequestedDeliveryFormat((string) ($order['informacion_adicional'] ?? ''));

    successResponse(
        'Detalle del pedido obtenido.',
        [
            'order' => [
                'id' => (int) ($order['id'] ?? 0),
                'numero_pedido' => (string) ($order['numero_pedido'] ?? ''),
                'tipo_evento' => (string) ($order['tipo_evento'] ?? ''),
                'nombre_evento' => (string) ($order['nombre_evento'] ?? ''),
                'fecha_evento' => (string) ($order['fecha_evento'] ?? ''),
                'hora_evento' => (string) ($order['hora_evento'] ?? ''),
                'ubicacion_evento' => (string) ($order['ubicacion_evento'] ?? ''),
                'estilo_diseno' => (string) ($order['estilo_diseno'] ?? ''),
                'colores' => (string) ($order['colores'] ?? ''),
                'tematica' => (string) ($order['tematica'] ?? ''),
                'informacion_adicional' => (string) ($order['informacion_adicional'] ?? ''),
                'estado_pedido' => (string) ($order['estado_pedido'] ?? ''),
                'created_at' => (string) ($order['created_at'] ?? ''),
                'updated_at' => (string) ($order['updated_at'] ?? ''),
            ],
            'customer' => [
                'id' => (int) ($order['cliente_id'] ?? 0),
                'nombre' => (string) ($order['cliente_nombre'] ?? ''),
                'correo' => (string) ($order['cliente_correo'] ?? ''),
                'telefono' => (string) ($order['cliente_telefono'] ?? ''),
                'medio_contacto' => (string) ($order['cliente_medio_contacto'] ?? ''),
            ],
            'payment' => [
                'id' => isset($order['pago_id']) ? (int) $order['pago_id'] : null,
                'metodo_pago' => (string) ($order['metodo_pago'] ?? ''),
                'monto_pago' => isset($order['monto_pago']) ? (float) $order['monto_pago'] : 0.0,
                'estado_pago' => (string) ($order['estado_pago'] ?? 'pendiente'),
                'resultado_transaccion' => (string) ($order['resultado_transaccion'] ?? ''),
                'mensaje_transaccion' => (string) ($order['mensaje_transaccion'] ?? ''),
                'referencia_pago' => (string) ($order['referencia_pago'] ?? ''),
                'fecha_pago' => (string) ($order['fecha_pago'] ?? ''),
                'created_at' => (string) ($order['pago_created_at'] ?? ''),
            ],
            'delivery' => [
                'id' => isset($order['entrega_id']) ? (int) $order['entrega_id'] : null,
                'requested_format' => $requestedDeliveryFormat,
                'formato_entrega' => (string) ($order['formato_entrega'] ?? ''),
                'archivo_final' => (string) ($order['archivo_final'] ?? ''),
                'fecha_entrega' => (string) ($order['fecha_entrega'] ?? ''),
                'notas_entrega' => (string) ($order['notas_entrega'] ?? ''),
            ],
            'activity' => array_map(
                static function (array $activityItem): array {
                    return [
                        'id' => (int) ($activityItem['id'] ?? 0),
                        'accion' => (string) ($activityItem['accion'] ?? ''),
                        'modulo' => (string) ($activityItem['modulo'] ?? ''),
                        'descripcion' => (string) ($activityItem['descripcion'] ?? ''),
                        'created_at' => (string) ($activityItem['created_at'] ?? ''),
                    ];
                },
                $activity
            ),
            'available_statuses' => [
                'pendiente',
                'pago_confirmado',
                'en_proceso',
                'terminado',
                'entregado',
                'cancelado',
            ],
        ]
    );
} catch (Throwable $exception) {
    errorResponse('No fue posible obtener el detalle del pedido.', [], 500);
}

function extractRequestedDeliveryFormat(string $notes): string
{
    if ($notes === '') {
        return '';
    }

    if (preg_match('/Formato de entrega solicitado:\s*([^\.\n]+)/i', $notes, $matches) === 1) {
        return sanitizeString($matches[1] ?? '');
    }

    return '';
}
