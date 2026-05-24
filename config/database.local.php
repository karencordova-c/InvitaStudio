<?php
declare(strict_types=1);

return [
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'port' => getenv('DB_PORT') ?: '3306',
    'database' => getenv('DB_NAME') ?: 'invitastudio',
    'username' => getenv('DB_USER') ?: 'invitastudio_user',
    'password' => getenv('DB_PASSWORD') ?: '',
    'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
    'collation' => getenv('DB_COLLATION') ?: 'utf8mb4_unicode_ci',
];
