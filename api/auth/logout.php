<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/common.php';

requireRequestMethod(['POST']);

$adminUser = getAdminUser();
$expectsJson = str_contains(
    strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? '')),
    'application/json'
);

if ($adminUser !== null) {
    try {
        createActivityLogEntry(
            getDatabaseConnection(),
            'admin',
            (int) $adminUser['id'],
            'logout',
            'auth',
            (int) $adminUser['id'],
            'Cierre de sesion administrativo.',
            getClientIpAddress()
        );
    } catch (Throwable $exception) {
        error_log('Admin logout log error: ' . $exception->getMessage());
    }
}

destroyAdminSession();

if ($expectsJson) {
    sendJsonResponse(
        200,
        true,
        'Sesion cerrada correctamente.',
        ['redirect_url' => getAdminLoginUrl() . '?logout=1']
    );
}

redirectToLocation(getAdminLoginUrl() . '?logout=1');
