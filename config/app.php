<?php
declare(strict_types=1);

$basePath = dirname(__DIR__);
require_once __DIR__ . '/env.php';

loadEnvironmentFile($basePath . DIRECTORY_SEPARATOR . '.env');

$defaultAppUrl = 'https://cintiaparral.com/invita';
$resolvedAppUrl = getenv('APP_URL') ?: (getenv('BASE_URL') ?: $defaultAppUrl);
$normalizedAppUrl = rtrim($resolvedAppUrl, '/');
$resolvedPublicBaseUrl = getenv('PUBLIC_BASE_URL') ?: (getenv('FRONTEND_URL') ?: ($normalizedAppUrl . '/public'));
$resolvedApiBaseUrl = getenv('API_BASE_URL') ?: ($normalizedAppUrl . '/api');
$resolvedCorsOrigins = getenv('CORS_ALLOWED_ORIGINS')
    ?: 'https://*.github.io,https://cintiaparral.com,http://localhost,http://127.0.0.1';

$appConfig = [
    'APP_NAME' => getenv('APP_NAME') ?: 'InvitaStudio',
    'APP_ENV' => getenv('APP_ENV') ?: 'local',
    'APP_URL' => $normalizedAppUrl,
    'BASE_URL' => $normalizedAppUrl,
    'PUBLIC_BASE_URL' => rtrim($resolvedPublicBaseUrl, '/'),
    'API_BASE_URL' => rtrim($resolvedApiBaseUrl, '/'),
    'CORS_ALLOWED_ORIGINS' => $resolvedCorsOrigins,
    'UPLOADS_PATH' => getenv('UPLOADS_PATH') ?: $basePath . DIRECTORY_SEPARATOR . 'uploads',
    'TIMEZONE' => getenv('TIMEZONE') ?: (getenv('APP_TIMEZONE') ?: 'America/Chihuahua'),
    'STORAGE_PATH' => $basePath . DIRECTORY_SEPARATOR . 'storage',
    'PUBLIC_PATH' => $basePath . DIRECTORY_SEPARATOR . 'public',
    'BASE_PATH' => $basePath,
];

date_default_timezone_set($appConfig['TIMEZONE']);

return array_merge(
    $appConfig,
    [
        'app_url' => $appConfig['APP_URL'],
        'app_name' => $appConfig['APP_NAME'],
        'app_env' => $appConfig['APP_ENV'],
        'base_url' => $appConfig['BASE_URL'],
        'public_base_url' => $appConfig['PUBLIC_BASE_URL'],
        'api_base_url' => $appConfig['API_BASE_URL'],
        'cors_allowed_origins' => $appConfig['CORS_ALLOWED_ORIGINS'],
        'app_timezone' => $appConfig['TIMEZONE'],
        'uploads_path' => $appConfig['UPLOADS_PATH'],
        'storage_path' => $appConfig['STORAGE_PATH'],
        'public_path' => $appConfig['PUBLIC_PATH'],
        'base_path' => $appConfig['BASE_PATH'],
    ]
);
