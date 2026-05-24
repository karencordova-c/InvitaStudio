<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/common.php';

requireRequestMethod(['GET']);

sendJsonResponse(
    200,
    true,
    'Modulo auth disponible.',
    [
        'module' => 'auth',
        'authentication_enabled' => isAuthenticationEnabled(),
        'implemented' => true,
        'endpoints' => [
            'login' => 'POST /api/auth/login.php',
            'logout' => 'POST /api/auth/logout.php',
        ],
    ]
);
