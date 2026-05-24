<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/common.php';

requireRequestMethod(['GET']);
requireAdminAuth();

$connection = getDatabaseConnection();

try {
    $ordersStatement = $connection->prepare(
        'SELECT
            COUNT(*) AS total_orders,
            SUM(CASE WHEN estado_pedido = :pending_status THEN 1 ELSE 0 END) AS pending_orders,
            SUM(CASE WHEN estado_pedido = :processing_status THEN 1 ELSE 0 END) AS processing_orders,
            SUM(CASE WHEN estado_pedido = :delivered_status THEN 1 ELSE 0 END) AS completed_orders,
            SUM(CASE WHEN estado_pedido = :paid_status THEN 1 ELSE 0 END) AS paid_orders,
            SUM(CASE WHEN estado_pedido = :finished_status THEN 1 ELSE 0 END) AS finished_orders
         FROM pedidos'
    );
    $ordersStatement->execute(
        [
            'pending_status' => 'pendiente',
            'processing_status' => 'en_proceso',
            'delivered_status' => 'entregado',
            'paid_status' => 'pago_confirmado',
            'finished_status' => 'terminado',
        ]
    );
    $ordersStats = $ordersStatement->fetch(PDO::FETCH_ASSOC) ?: [];

    $paymentsStatement = $connection->prepare(
        'SELECT COUNT(*) AS pending_payments
         FROM pagos
         WHERE estado_pago = :payment_status'
    );
    $paymentsStatement->execute(['payment_status' => 'pendiente']);
    $paymentsStats = $paymentsStatement->fetch(PDO::FETCH_ASSOC) ?: [];

    successResponse(
        'Estadisticas del dashboard obtenidas.',
        [
            'total_orders' => (int) ($ordersStats['total_orders'] ?? 0),
            'pending_orders' => (int) ($ordersStats['pending_orders'] ?? 0),
            'processing_orders' => (int) ($ordersStats['processing_orders'] ?? 0),
            'completed_orders' => (int) ($ordersStats['completed_orders'] ?? 0),
            'pending_payments' => (int) ($paymentsStats['pending_payments'] ?? 0),
            'delivered_orders' => (int) ($ordersStats['completed_orders'] ?? 0),
            'paid_orders' => (int) ($ordersStats['paid_orders'] ?? 0),
            'finished_orders' => (int) ($ordersStats['finished_orders'] ?? 0),
        ]
    );
} catch (Throwable $exception) {
    errorResponse('No fue posible obtener las estadisticas del dashboard.', [], 500);
}
