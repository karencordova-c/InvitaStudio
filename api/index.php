<?php
declare(strict_types=1);

require_once __DIR__ . '/common.php';

requireRequestMethod(['GET']);

successResponse(
    'API base de InvitaStudio disponible.',
    [
        'modules' => ['orders', 'payments', 'deliveries', 'messages', 'status', 'auth'],
        'status_endpoints' => ['health', 'database', 'lookup'],
        'authentication_enabled' => isAuthenticationEnabled(),
    ]
);
