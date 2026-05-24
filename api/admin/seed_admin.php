<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/common.php';

requireRequestMethod(['POST']);

$seedToken = (string) (getenv('ADMIN_SEED_TOKEN') ?: '');
$providedToken = (string) ($_SERVER['HTTP_X_ADMIN_SEED_TOKEN'] ?? '');

if ($seedToken === '' || !hash_equals($seedToken, $providedToken)) {
    errorResponse('No autorizado.', [], 401);
}

$requestData = readRequestData();
$nombre = sanitizeString($requestData['nombre'] ?? 'Administrador General');
$correo = normalizeString($requestData['correo'] ?? 'admin@invitastudio.local');
$password = is_scalar($requestData['password'] ?? null) ? (string) $requestData['password'] : '';
$rol = normalizeString($requestData['rol'] ?? 'super_admin');

$errors = mergeValidationErrors(
    validateStringLength($nombre, 'nombre', 3, 150),
    validateEmailValue($correo, 'correo'),
    validateStringLength($password, 'password', 8, 255),
    validateEnumValue($rol, 'rol', ['super_admin', 'operador', 'disenador'])
);

assertValidInput($errors);

$connection = getDatabaseConnection();
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

try {
    $statement = $connection->prepare(
        'INSERT INTO usuarios_admin (
            nombre,
            correo,
            password_hash,
            rol,
            activo,
            ultimo_login,
            created_at,
            updated_at
        ) VALUES (
            :nombre,
            :correo,
            :password_hash,
            :rol,
            1,
            NULL,
            NOW(),
            NOW()
        )
        ON DUPLICATE KEY UPDATE
            nombre = VALUES(nombre),
            password_hash = VALUES(password_hash),
            rol = VALUES(rol),
            activo = 1,
            updated_at = NOW()'
    );

    $statement->execute(
        [
            'nombre' => $nombre,
            'correo' => $correo,
            'password_hash' => $passwordHash,
            'rol' => $rol,
        ]
    );

    successResponse(
        'Administrador creado o actualizado correctamente.',
        [
            'correo' => $correo,
            'rol' => $rol,
        ]
    );
} catch (Throwable $exception) {
    error_log('Admin seed error: ' . $exception->getMessage());
    errorResponse('No fue posible crear el administrador.', [], 500);
}
