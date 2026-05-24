<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/common.php';

requireRequestMethod(['GET']);
requireAdminAuth();

$connection = getDatabaseConnection();

try {
    $recentOrdersStatement = $connection->prepare(
        'SELECT
            p.id,
            p.numero_pedido,
            p.estado_pedido,
            p.nombre_evento,
            p.created_at,
            c.nombre AS cliente_nombre
         FROM pedidos p
         INNER JOIN clientes c ON c.id = p.cliente_id
         ORDER BY p.created_at DESC, p.id DESC
         LIMIT 5'
    );
    $recentOrdersStatement->execute();
    $recentOrders = $recentOrdersStatement->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $recentPaymentsStatement = $connection->prepare(
        'SELECT
            pa.id,
            pe.numero_pedido,
            pa.estado_pago,
            pa.monto_pago,
            pa.referencia_pago,
            COALESCE(pa.fecha_pago, pa.created_at) AS fecha_referencia,
            c.nombre AS cliente_nombre
         FROM pagos pa
         INNER JOIN pedidos pe ON pe.id = pa.pedido_id
         INNER JOIN clientes c ON c.id = pe.cliente_id
         ORDER BY COALESCE(pa.fecha_pago, pa.created_at) DESC, pa.id DESC
         LIMIT 5'
    );
    $recentPaymentsStatement->execute();
    $recentPayments = $recentPaymentsStatement->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $recentDeliveriesStatement = $connection->prepare(
        'SELECT
            e.id,
            p.numero_pedido,
            e.formato_entrega,
            e.archivo_final,
            e.fecha_entrega,
            c.nombre AS cliente_nombre
         FROM entregas e
         INNER JOIN pedidos p ON p.id = e.pedido_id
         INNER JOIN clientes c ON c.id = p.cliente_id
         ORDER BY e.fecha_entrega DESC, e.id DESC
         LIMIT 5'
    );
    $recentDeliveriesStatement->execute();
    $recentDeliveries = $recentDeliveriesStatement->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $activityFeedStatement = $connection->prepare(
        'SELECT
            id,
            accion,
            modulo,
            descripcion,
            created_at
         FROM actividad_log
         ORDER BY created_at DESC, id DESC
         LIMIT 8'
    );
    $activityFeedStatement->execute();
    $activityFeed = $activityFeedStatement->fetchAll(PDO::FETCH_ASSOC) ?: [];

    successResponse(
        'Actividad reciente obtenida.',
        [
            'recent_orders' => array_map(
                static function (array $order): array {
                    return [
                        'id' => (int) ($order['id'] ?? 0),
                        'numero_pedido' => (string) ($order['numero_pedido'] ?? ''),
                        'cliente_nombre' => (string) ($order['cliente_nombre'] ?? ''),
                        'estado_pedido' => (string) ($order['estado_pedido'] ?? ''),
                        'nombre_evento' => (string) ($order['nombre_evento'] ?? ''),
                        'created_at' => (string) ($order['created_at'] ?? ''),
                    ];
                },
                $recentOrders
            ),
            'recent_payments' => array_map(
                static function (array $payment): array {
                    return [
                        'id' => (int) ($payment['id'] ?? 0),
                        'numero_pedido' => (string) ($payment['numero_pedido'] ?? ''),
                        'cliente_nombre' => (string) ($payment['cliente_nombre'] ?? ''),
                        'estado_pago' => (string) ($payment['estado_pago'] ?? ''),
                        'monto_pago' => (float) ($payment['monto_pago'] ?? 0),
                        'referencia_pago' => (string) ($payment['referencia_pago'] ?? ''),
                        'fecha_referencia' => (string) ($payment['fecha_referencia'] ?? ''),
                    ];
                },
                $recentPayments
            ),
            'recent_deliveries' => array_map(
                static function (array $delivery): array {
                    return [
                        'id' => (int) ($delivery['id'] ?? 0),
                        'numero_pedido' => (string) ($delivery['numero_pedido'] ?? ''),
                        'cliente_nombre' => (string) ($delivery['cliente_nombre'] ?? ''),
                        'formato_entrega' => (string) ($delivery['formato_entrega'] ?? ''),
                        'archivo_final' => (string) ($delivery['archivo_final'] ?? ''),
                        'fecha_entrega' => (string) ($delivery['fecha_entrega'] ?? ''),
                    ];
                },
                $recentDeliveries
            ),
            'activity_feed' => array_map(
                static function (array $activity): array {
                    return [
                        'id' => (int) ($activity['id'] ?? 0),
                        'accion' => (string) ($activity['accion'] ?? ''),
                        'modulo' => (string) ($activity['modulo'] ?? ''),
                        'descripcion' => (string) ($activity['descripcion'] ?? ''),
                        'created_at' => (string) ($activity['created_at'] ?? ''),
                    ];
                },
                $activityFeed
            ),
        ]
    );
} catch (Throwable $exception) {
    errorResponse('No fue posible obtener la actividad reciente.', [], 500);
}
