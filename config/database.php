<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';

loadEnvironmentFile(dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env');

if (!function_exists('getRailwayMysqlUrlConfig')) {
    function getRailwayMysqlUrlConfig(): array
    {
        $mysqlUrl = getenv('MYSQL_URL') ?: '';

        if ($mysqlUrl === '') {
            return [];
        }

        $parsedUrl = parse_url($mysqlUrl);

        if (!is_array($parsedUrl)) {
            return [];
        }

        $databasePath = trim((string) ($parsedUrl['path'] ?? ''), '/');

        return [
            'HOST' => (string) ($parsedUrl['host'] ?? ''),
            'PORT' => (string) ($parsedUrl['port'] ?? '3306'),
            'DATABASE' => $databasePath,
            'USERNAME' => (string) ($parsedUrl['user'] ?? ''),
            'PASSWORD' => (string) ($parsedUrl['pass'] ?? ''),
        ];
    }
}

if (!function_exists('normalizeDatabaseConfig')) {
    function normalizeDatabaseConfig(array $config): array
    {
        return [
            'HOST' => (string) ($config['HOST'] ?? $config['host'] ?? '127.0.0.1'),
            'PORT' => (string) ($config['PORT'] ?? $config['port'] ?? '3306'),
            'DATABASE' => (string) ($config['DATABASE'] ?? $config['database'] ?? 'invitastudio'),
            'USERNAME' => (string) ($config['USERNAME'] ?? $config['username'] ?? 'invitastudio_user'),
            'PASSWORD' => (string) ($config['PASSWORD'] ?? $config['password'] ?? ''),
            'CHARSET' => (string) ($config['CHARSET'] ?? $config['charset'] ?? 'utf8mb4'),
            'COLLATION' => (string) ($config['COLLATION'] ?? $config['collation'] ?? 'utf8mb4_unicode_ci'),
        ];
    }
}

$railwayMysqlUrlConfig = getRailwayMysqlUrlConfig();

$databaseConfig = normalizeDatabaseConfig(
    [
        'HOST' => getenv('DB_HOST') ?: (getenv('MYSQLHOST') ?: ($railwayMysqlUrlConfig['HOST'] ?? '127.0.0.1')),
        'PORT' => getenv('DB_PORT') ?: (getenv('MYSQLPORT') ?: ($railwayMysqlUrlConfig['PORT'] ?? '3306')),
        'DATABASE' => getenv('DB_NAME') ?: (getenv('MYSQLDATABASE') ?: ($railwayMysqlUrlConfig['DATABASE'] ?? 'invitastudio')),
        'USERNAME' => getenv('DB_USER') ?: (getenv('MYSQLUSER') ?: ($railwayMysqlUrlConfig['USERNAME'] ?? 'invitastudio_user')),
        'PASSWORD' => getenv('DB_PASSWORD') ?: (getenv('MYSQLPASSWORD') ?: ($railwayMysqlUrlConfig['PASSWORD'] ?? '')),
        'CHARSET' => getenv('DB_CHARSET') ?: 'utf8mb4',
        'COLLATION' => getenv('DB_COLLATION') ?: 'utf8mb4_unicode_ci',
    ]
);

$databaseLocalConfigPath = __DIR__ . '/database.local.php';

if (is_file($databaseLocalConfigPath)) {
    $databaseLocalConfig = require $databaseLocalConfigPath;

    if (is_array($databaseLocalConfig)) {
        $databaseConfig = array_merge(
            $databaseConfig,
            normalizeDatabaseConfig($databaseLocalConfig)
        );
    }
}

if (!function_exists('getDatabaseConfig')) {
    function getDatabaseConfig(): array
    {
        static $config = null;

        if ($config !== null) {
            return $config;
        }

        $config = require __DIR__ . '/database.php';

        return $config;
    }
}

if (!function_exists('createDatabaseConnection')) {
    function createDatabaseConnection(array $databaseConfig): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $databaseConfig['HOST'],
            $databaseConfig['PORT'],
            $databaseConfig['DATABASE'],
            $databaseConfig['CHARSET']
        );

        try {
            $connection = new PDO(
                $dsn,
                $databaseConfig['USERNAME'],
                $databaseConfig['PASSWORD'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );

            $connection->exec(
                sprintf(
                    "SET NAMES %s COLLATE %s",
                    $databaseConfig['CHARSET'],
                    $databaseConfig['COLLATION']
                )
            );

            return $connection;
        } catch (PDOException $exception) {
            throw new RuntimeException(
                'No fue posible conectar con la base de datos.',
                (int) $exception->getCode(),
                $exception
            );
        }
    }
}

if (!function_exists('getPdoConnection')) {
    function getPdoConnection(): PDO
    {
        static $connection = null;

        if ($connection instanceof PDO) {
            return $connection;
        }

        $connection = createDatabaseConnection(getDatabaseConfig());

        return $connection;
    }
}

return array_merge(
    $databaseConfig,
    [
        'host' => $databaseConfig['HOST'],
        'port' => $databaseConfig['PORT'],
        'database' => $databaseConfig['DATABASE'],
        'username' => $databaseConfig['USERNAME'],
        'password' => $databaseConfig['PASSWORD'],
        'charset' => $databaseConfig['CHARSET'],
        'collation' => $databaseConfig['COLLATION'],
    ]
);
