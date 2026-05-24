<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/common.php';

requireRequestMethod(['GET']);
requireAdminAuth();

$allowedStatuses = [
    'pendiente',
    'pago_confirmado',
    'en_proceso',
    'terminado',
    'entregado',
    'cancelado',
];
$allowedSorts = ['recent', 'event_date', 'status'];
$allowedPerPage = [10, 20];

$search = sanitizeString($_GET['search'] ?? '');
$status = sanitizeString($_GET['status'] ?? '');
$sort = sanitizeString($_GET['sort'] ?? 'recent');
$page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT);
$perPage = filter_var($_GET['per_page'] ?? 10, FILTER_VALIDATE_INT);

if ($status !== '' && !in_array($status, $allowedStatuses, true)) {
    validationErrorResponse(['status' => ['El estado solicitado no es valido.']]);
}

if (!in_array($sort, $allowedSorts, true)) {
    validationErrorResponse(['sort' => ['El orden solicitado no es valido.']]);
}

if ($page === false || $page < 1) {
    validationErrorResponse(['page' => ['La pagina debe ser un entero positivo.']]);
}

if ($perPage === false || !in_array((int) $perPage, $allowedPerPage, true)) {
    validationErrorResponse(['per_page' => ['La cantidad por pagina no es valida.']]);
}

$connection = getDatabaseConnection();
$whereClauses = [];
$queryParams = [];

if ($search !== '') {
    $whereClauses[] = '(p.numero_pedido LIKE :search OR c.nombre LIKE :search OR c.correo LIKE :search)';
    $queryParams['search'] = '%' . $search . '%';
}

if ($status !== '') {
    $whereClauses[] = 'p.estado_pedido = :status';
    $queryParams['status'] = $status;
}

$whereSql = $whereClauses === [] ? '' : ('WHERE ' . implode(' AND ', $whereClauses));
$orderBySql = match ($sort) {
    'event_date' => 'p.fecha_evento ASC, p.id DESC',
    'status' => "FIELD(p.estado_pedido, 'pendiente', 'pago_confirmado', 'en_proceso', 'terminado', 'entregado', 'cancelado') ASC, p.created_at DESC, p.id DESC",
    default => 'p.created_at DESC, p.id DESC',
};

try {
    $countStatement = $connection->prepare(
        'SELECT COUNT(*) AS total_items
         FROM pedidos p
         INNER JOIN clientes c ON c.id = p.cliente_id
         ' . $whereSql
    );
    $countStatement->execute($queryParams);

    $totalItems = (int) ($countStatement->fetch(PDO::FETCH_ASSOC)['total_items'] ?? 0);
    $totalPages = max(1, (int) ceil($totalItems / (int) $perPage));
    $currentPage = min((int) $page, $totalPages);
    $offset = ($currentPage - 1) * (int) $perPage;

    $listStatement = $connection->prepare(
        'SELECT
            p.id,
            p.numero_pedido,
            p.nombre_evento,
            p.tipo_evento,
            p.fecha_evento,
            p.estado_pedido,
            p.created_at,
            c.nombre AS cliente_nombre,
            c.correo AS cliente_correo,
            COALESCE(lp.estado_pago, "pendiente") AS estado_pago
         FROM pedidos p
         INNER JOIN clientes c ON c.id = p.cliente_id
         LEFT JOIN pagos lp ON lp.id = (
            SELECT p2.id
            FROM pagos p2
            WHERE p2.pedido_id = p.id
            ORDER BY COALESCE(p2.fecha_pago, p2.created_at) DESC, p2.id DESC
            LIMIT 1
         )
         ' . $whereSql . '
         ORDER BY ' . $orderBySql . '
         LIMIT :limit OFFSET :offset'
    );

    foreach ($queryParams as $paramName => $paramValue) {
        $listStatement->bindValue(':' . $paramName, $paramValue, PDO::PARAM_STR);
    }

    $listStatement->bindValue(':limit', (int) $perPage, PDO::PARAM_INT);
    $listStatement->bindValue(':offset', $offset, PDO::PARAM_INT);
    $listStatement->execute();

    $orders = $listStatement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $fromItem = $totalItems === 0 ? 0 : ($offset + 1);
    $toItem = $totalItems === 0 ? 0 : min($offset + (int) $perPage, $totalItems);

    successResponse(
        'Pedidos obtenidos correctamente.',
        [
            'orders' => array_map(
                static function (array $order): array {
                    return [
                        'id' => (int) ($order['id'] ?? 0),
                        'numero_pedido' => (string) ($order['numero_pedido'] ?? ''),
                        'cliente_nombre' => (string) ($order['cliente_nombre'] ?? ''),
                        'cliente_correo' => (string) ($order['cliente_correo'] ?? ''),
                        'nombre_evento' => (string) ($order['nombre_evento'] ?? ''),
                        'tipo_evento' => (string) ($order['tipo_evento'] ?? ''),
                        'fecha_evento' => (string) ($order['fecha_evento'] ?? ''),
                        'estado_pedido' => (string) ($order['estado_pedido'] ?? ''),
                        'estado_pago' => (string) ($order['estado_pago'] ?? 'pendiente'),
                        'created_at' => (string) ($order['created_at'] ?? ''),
                    ];
                },
                $orders
            ),
            'pagination' => [
                'current_page' => $currentPage,
                'per_page' => (int) $perPage,
                'total_items' => $totalItems,
                'total_pages' => $totalItems === 0 ? 0 : $totalPages,
                'from_item' => $fromItem,
                'to_item' => $toItem,
                'has_prev' => $currentPage > 1,
                'has_next' => $currentPage < $totalPages,
            ],
            'filters' => [
                'search' => $search,
                'status' => $status,
                'sort' => $sort,
            ],
        ]
    );
} catch (Throwable $exception) {
    errorResponse('No fue posible obtener la lista de pedidos.', [], 500);
}
