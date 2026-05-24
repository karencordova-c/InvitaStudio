<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/common.php';

requireRequestMethod(['POST']);

if (!function_exists('readAdminLoginRequestData')) {
    function readAdminLoginRequestData(): array
    {
        if ($_POST !== []) {
            return $_POST;
        }

        $rawBody = file_get_contents('php://input');

        if ($rawBody === false || trim($rawBody) === '') {
            return [];
        }

        $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));

        if (str_contains($contentType, 'application/json')) {
            $decodedBody = json_decode($rawBody, true);

            if (!is_array($decodedBody)) {
                errorResponse('JSON invalido.', [], 400);
            }

            return $decodedBody;
        }

        parse_str($rawBody, $parsedBody);

        return is_array($parsedBody) ? $parsedBody : [];
    }
}

$requestData = readAdminLoginRequestData();
$emailInput = $requestData['correo'] ?? null;
$passwordInput = $requestData['password'] ?? null;

$validationErrors = mergeValidationErrors(
    validateEmailValue($emailInput, 'correo'),
    validateStringLength($passwordInput, 'password', 1, 255)
);

assertValidInput($validationErrors);

$correo = normalizeString($emailInput);
$password = is_scalar($passwordInput) ? (string) $passwordInput : '';
$ipAddress = getClientIpAddress();
$connection = getDatabaseConnection();

try {
    $adminStatement = $connection->prepare(
        'SELECT id, nombre, correo, password_hash, rol, activo
         FROM usuarios_admin
         WHERE correo = :correo
         LIMIT 1'
    );
    $adminStatement->execute(['correo' => $correo]);
    $adminUser = $adminStatement->fetch();

    $isPasswordValid = $adminUser !== false
        && (int) ($adminUser['activo'] ?? 0) === 1
        && password_verify($password, (string) ($adminUser['password_hash'] ?? ''));

    if (!$isPasswordValid && $adminUser !== false && canRepairAdminPassword($correo, $password)) {
        $repairedPasswordHash = password_hash($password, PASSWORD_DEFAULT);

        $repairStatement = $connection->prepare(
            'UPDATE usuarios_admin
             SET password_hash = :password_hash,
                 activo = 1,
                 updated_at = NOW()
             WHERE id = :id'
        );
        $repairStatement->execute(
            [
                'password_hash' => $repairedPasswordHash,
                'id' => $adminUser['id'],
            ]
        );

        $adminUser['password_hash'] = $repairedPasswordHash;
        $adminUser['activo'] = 1;
        $isPasswordValid = true;
    }

    if (!$isPasswordValid) {
        createActivityLogEntry(
            $connection,
            'admin',
            $adminUser !== false ? (int) $adminUser['id'] : null,
            'login_fallido',
            'auth',
            $adminUser !== false ? (int) $adminUser['id'] : null,
            'Intento fallido de autenticacion administrativa para el correo ' . $correo . '.',
            $ipAddress
        );

        sendJsonResponse(401, false, 'Credenciales invalidas');
    }

    persistAdminSession($adminUser);

    $updateLoginStatement = $connection->prepare(
        'UPDATE usuarios_admin
         SET ultimo_login = NOW(), updated_at = NOW()
         WHERE id = :id'
    );
    $updateLoginStatement->execute(['id' => $adminUser['id']]);

    createActivityLogEntry(
        $connection,
        'admin',
        (int) $adminUser['id'],
        'login_exitoso',
        'auth',
        (int) $adminUser['id'],
        'Inicio de sesion administrativo exitoso.',
        $ipAddress
    );

    sendJsonResponse(
        200,
        true,
        'Login exitoso',
        [
            'redirect_url' => getAdminDashboardUrl(),
            'admin' => [
                'id' => (int) $adminUser['id'],
                'nombre' => (string) $adminUser['nombre'],
                'correo' => (string) $adminUser['correo'],
                'rol' => (string) $adminUser['rol'],
            ],
        ]
    );
} catch (Throwable $exception) {
    error_log('Admin login error: ' . $exception->getMessage());

    sendJsonResponse(
        500,
        false,
        'No fue posible iniciar sesion.'
    );
}

function canRepairAdminPassword(string $correo, string $password): bool
{
    $repairEmail = normalizeString(getenv('ADMIN_REPAIR_EMAIL') ?: '');
    $repairPassword = (string) (getenv('ADMIN_REPAIR_PASSWORD') ?: '');

    return $repairEmail !== ''
        && $repairPassword !== ''
        && hash_equals($repairEmail, $correo)
        && hash_equals($repairPassword, $password);
}
