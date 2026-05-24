<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/common.php';

requireRequestMethod(['GET']);

$includeInactive = sanitizeString($_GET['include_inactive'] ?? '') === '1';

if ($includeInactive) {
    requireAdminAuth();
}

$search = sanitizeString($_GET['search'] ?? '');
$activeFilter = sanitizeString($_GET['activo'] ?? '');

if (!$includeInactive) {
    $activeFilter = '1';
}

if (!in_array($activeFilter, ['', '0', '1'], true)) {
    validationErrorResponse(['activo' => ['El filtro de estado no es valido.']]);
}

$connection = getDatabaseConnection();
$whereClauses = [];
$queryParams = [];

if ($search !== '') {
    $whereClauses[] = '(nombre LIKE :search OR categoria LIKE :search OR descripcion LIKE :search)';
    $queryParams['search'] = '%' . $search . '%';
}

if ($activeFilter !== '') {
    $whereClauses[] = 'activo = :activo';
    $queryParams['activo'] = (int) $activeFilter;
}

$whereSql = $whereClauses === [] ? '' : ('WHERE ' . implode(' AND ', $whereClauses));

try {
    $statement = $connection->prepare(
        'SELECT
            id,
            nombre,
            descripcion,
            categoria,
            precio,
            formato_entrega,
            tiempo_entrega,
            imagen_referencia,
            activo,
            created_at,
            updated_at
         FROM servicios
         ' . $whereSql . '
         ORDER BY activo DESC, categoria ASC, precio ASC, id DESC'
    );

    foreach ($queryParams as $paramName => $paramValue) {
        $parameterType = $paramName === 'activo' ? PDO::PARAM_INT : PDO::PARAM_STR;
        $statement->bindValue(':' . $paramName, $paramValue, $parameterType);
    }

    $statement->execute();
    $services = $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $serializedServices = array_map('serializeServiceRecord', $services);
    $activeCount = count(array_filter(
        $serializedServices,
        static fn (array $service): bool => $service['activo'] === true
    ));

    successResponse(
        'Servicios obtenidos correctamente.',
        [
            'services' => $serializedServices,
            'summary' => [
                'total' => count($serializedServices),
                'active' => $activeCount,
                'inactive' => count($serializedServices) - $activeCount,
                'include_inactive' => $includeInactive,
            ],
            'filters' => [
                'search' => $search,
                'activo' => $activeFilter,
            ],
        ]
    );
} catch (Throwable $exception) {
    errorResponse('No fue posible obtener los servicios.', [], 500);
}
