<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/common.php';

requireRequestMethod(['GET']);

try {
    $connection = getDatabaseConnection();
    $statement = $connection->query('SELECT 1 AS connection_ok');
    $result = $statement->fetch();

    successResponse(
        'Conexion a base de datos funcionando.',
        [
            'database' => $GLOBALS['databaseConfig']['DATABASE'] ?? $GLOBALS['databaseConfig']['database'] ?? 'invitastudio',
            'connection_ok' => ((int) ($result['connection_ok'] ?? 0)) === 1,
        ]
    );
} catch (Throwable $exception) {
    errorResponse('No fue posible conectar con la base de datos.', [], 500);
}
