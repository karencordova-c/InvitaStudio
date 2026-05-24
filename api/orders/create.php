<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/common.php';

requireRequestMethod(['POST']);

$requestData = readRequestData();

$serviceCatalog = [
    'clasica_esencial' => [
        'title' => 'Invitacion digital clasica',
        'category' => 'Social',
        'price_label' => 'Desde $850 MXN',
        'base_amount' => 850.00,
    ],
    'interactiva_recomendada' => [
        'title' => 'Invitacion interactiva',
        'category' => 'Experiencia web',
        'price_label' => 'Desde $1,450 MXN',
        'base_amount' => 1450.00,
    ],
    'concepto_medida' => [
        'title' => 'Concepto a medida',
        'category' => 'Direccion creativa',
        'price_label' => 'Cotizacion segun briefing',
        'base_amount' => 1850.00,
    ],
];

$allowedContactMethods = ['whatsapp', 'correo', 'llamada'];
$allowedDeliveryFormats = ['imagen', 'pdf', 'video'];

$nombre = sanitizeString($requestData['nombre'] ?? null);
$correo = normalizeString($requestData['correo'] ?? null);
$telefono = sanitizeString($requestData['telefono'] ?? null);
$medioContacto = normalizeString($requestData['medio_contacto'] ?? 'whatsapp');
$tipoEvento = sanitizeString($requestData['tipo_evento'] ?? null);
$nombreEvento = sanitizeString($requestData['nombre_evento'] ?? null);
$fechaEvento = sanitizeString($requestData['fecha_evento'] ?? null);
$horaEvento = sanitizeString($requestData['hora_evento'] ?? null);
$ubicacionEvento = sanitizeString($requestData['ubicacion_evento'] ?? null);
$tematica = sanitizeString($requestData['tematica'] ?? null);
$colores = sanitizeString($requestData['colores'] ?? null);
$estiloDiseno = sanitizeString($requestData['estilo_diseno'] ?? null);
$informacionAdicional = sanitizeString($requestData['informacion_adicional'] ?? null);
$formatoEntrega = normalizeString($requestData['formato_entrega'] ?? 'imagen');
$servicioId = normalizeString($requestData['servicio_id'] ?? null);

$errors = mergeValidationErrors(
    validateStringLength($nombre, 'nombre', 3, 150),
    validateEmailValue($correo, 'correo'),
    validateStringLength($tipoEvento, 'tipo_evento', 3, 100),
    validateStringLength($fechaEvento, 'fecha_evento', 10, 10),
    validateStringLength($horaEvento, 'hora_evento', 5, 5),
    validateStringLength($ubicacionEvento, 'ubicacion_evento', 5, 255),
    validateStringLength($estiloDiseno, 'estilo_diseno', 3, 120),
    validateStringLength($nombreEvento, 'nombre_evento', 3, 150, false),
    validateStringLength($tematica, 'tematica', 3, 120, false),
    validateStringLength($colores, 'colores', 3, 255, false),
    validateStringLength($informacionAdicional, 'informacion_adicional', 10, 1500, false),
    validateEnumValue($medioContacto, 'medio_contacto', $allowedContactMethods),
    validateEnumValue($formatoEntrega, 'formato_entrega', $allowedDeliveryFormats),
    validateEnumValue($servicioId, 'servicio_id', array_keys($serviceCatalog))
);

$telefonoDigits = preg_replace('/\D+/', '', $telefono) ?? '';

if ($telefonoDigits === '' || strlen($telefonoDigits) < 8 || strlen($telefonoDigits) > 20) {
    addValidationMessage($errors, 'telefono', 'El telefono debe tener entre 8 y 20 digitos.');
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaEvento) || !isValidDateValue($fechaEvento)) {
    addValidationMessage($errors, 'fecha_evento', 'La fecha del evento no es valida.');
}

if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $horaEvento)) {
    addValidationMessage($errors, 'hora_evento', 'La hora del evento no es valida.');
}

if ($errors !== []) {
    validationErrorResponse($errors, 'Error de validacion.');
}

$selectedService = $serviceCatalog[$servicioId];
$resolvedEventName = $nombreEvento !== '' ? $nombreEvento : buildFallbackEventName($tipoEvento, $nombre);
$formattedEventDate = $fechaEvento . ' 00:00:00';
$formattedEventTime = $horaEvento . ':00';
$orderNotes = buildOrderNotes(
    $selectedService,
    $servicioId,
    $formatoEntrega,
    $informacionAdicional
);

$connection = getDatabaseConnection();
$ipAddress = getClientIpAddress();

try {
    $connection->beginTransaction();

    $clientId = upsertClient(
        $connection,
        [
            'nombre' => $nombre,
            'correo' => $correo,
            'telefono' => $telefono,
            'medio_contacto' => $medioContacto,
        ]
    );

    $orderId = createOrderRecord(
        $connection,
        [
            'cliente_id' => $clientId,
            'numero_pedido' => generateReferenceCode('ord'),
            'tipo_evento' => $tipoEvento,
            'nombre_evento' => $resolvedEventName,
            'fecha_evento' => $formattedEventDate,
            'hora_evento' => $formattedEventTime,
            'ubicacion_evento' => $ubicacionEvento,
            'estilo_diseno' => $estiloDiseno,
            'colores' => $colores !== '' ? $colores : null,
            'tematica' => $tematica !== '' ? $tematica : null,
            'informacion_adicional' => $orderNotes,
            'estado_pedido' => 'pendiente',
        ]
    );

    $orderNumber = generateOrderNumber($orderId);
    updateOrderNumber($connection, $orderId, $orderNumber);

    $paymentAmount = (float) $selectedService['base_amount'];
    $paymentId = createPendingPaymentRecord($connection, $orderId, $paymentAmount);

    createActivityLogEntry(
        $connection,
        'cliente',
        $clientId,
        'crear_pedido',
        'orders',
        $orderId,
        'Registro de nueva solicitud ' . $orderNumber . ' con pago inicial pendiente ID ' . $paymentId . '.',
        $ipAddress
    );

    $connection->commit();

    sendOrderConfirmation(
        $connection,
        [
            'pedido_id' => $orderId,
            'numero_pedido' => $orderNumber,
            'cliente_nombre' => $nombre,
            'cliente_correo' => $correo,
            'fecha_evento' => $formattedEventDate,
            'estado_pedido' => 'pendiente',
            'nombre_evento' => $resolvedEventName,
            'tipo_evento' => $tipoEvento,
            'servicio' => (string) ($selectedService['title'] ?? ''),
            'formato_entrega' => $formatoEntrega,
            'monto_pago' => $paymentAmount,
        ]
    );

    successResponse(
        'Solicitud registrada correctamente',
        [
            'pedido_id' => $orderId,
            'numero_pedido' => $orderNumber,
            'monto_pago' => $paymentAmount,
            'payment_url' => 'payment.html?numero_pedido=' . rawurlencode($orderNumber) . '&correo=' . rawurlencode($correo),
        ],
        201
    );
} catch (Throwable $exception) {
    if ($connection->inTransaction()) {
        $connection->rollBack();
    }

    logOrderRegistrationFailure(
        $exception,
        [
            'correo' => $correo,
            'tipo_evento' => $tipoEvento,
            'fecha_evento' => $fechaEvento,
        ]
    );

    errorResponse('Error al registrar pedido', [], 500);
}

function upsertClient(PDO $connection, array $clientData): int
{
    $statement = $connection->prepare(
        'INSERT INTO clientes (
            nombre,
            correo,
            telefono,
            medio_contacto,
            created_at,
            updated_at
        ) VALUES (
            :nombre,
            :correo,
            :telefono,
            :medio_contacto,
            NOW(),
            NOW()
        )
        ON DUPLICATE KEY UPDATE
            nombre = VALUES(nombre),
            telefono = VALUES(telefono),
            medio_contacto = VALUES(medio_contacto),
            updated_at = NOW(),
            id = LAST_INSERT_ID(id)'
    );
    $statement->execute(
        [
            'nombre' => $clientData['nombre'],
            'correo' => $clientData['correo'],
            'telefono' => $clientData['telefono'],
            'medio_contacto' => $clientData['medio_contacto'],
        ]
    );

    return (int) $connection->lastInsertId();
}

function createOrderRecord(PDO $connection, array $orderData): int
{
    $statement = $connection->prepare(
        'INSERT INTO pedidos (
            cliente_id,
            numero_pedido,
            tipo_evento,
            nombre_evento,
            fecha_evento,
            hora_evento,
            ubicacion_evento,
            estilo_diseno,
            colores,
            tematica,
            informacion_adicional,
            estado_pedido,
            created_at,
            updated_at
        ) VALUES (
            :cliente_id,
            :numero_pedido,
            :tipo_evento,
            :nombre_evento,
            :fecha_evento,
            :hora_evento,
            :ubicacion_evento,
            :estilo_diseno,
            :colores,
            :tematica,
            :informacion_adicional,
            :estado_pedido,
            NOW(),
            NOW()
        )'
    );
    $statement->execute(
        [
            'cliente_id' => $orderData['cliente_id'],
            'numero_pedido' => $orderData['numero_pedido'],
            'tipo_evento' => $orderData['tipo_evento'],
            'nombre_evento' => $orderData['nombre_evento'],
            'fecha_evento' => $orderData['fecha_evento'],
            'hora_evento' => $orderData['hora_evento'],
            'ubicacion_evento' => $orderData['ubicacion_evento'],
            'estilo_diseno' => $orderData['estilo_diseno'],
            'colores' => $orderData['colores'],
            'tematica' => $orderData['tematica'],
            'informacion_adicional' => $orderData['informacion_adicional'],
            'estado_pedido' => $orderData['estado_pedido'],
        ]
    );

    return (int) $connection->lastInsertId();
}

function updateOrderNumber(PDO $connection, int $orderId, string $orderNumber): void
{
    $statement = $connection->prepare(
        'UPDATE pedidos
         SET numero_pedido = :numero_pedido,
             updated_at = NOW()
         WHERE id = :id'
    );
    $statement->execute(
        [
            'numero_pedido' => $orderNumber,
            'id' => $orderId,
        ]
    );
}

function createPendingPaymentRecord(PDO $connection, int $orderId, float $paymentAmount): int
{
    $statement = $connection->prepare(
        'INSERT INTO pagos (
            pedido_id,
            tarjeta_prueba_id,
            metodo_pago,
            monto_pago,
            estado_pago,
            resultado_transaccion,
            mensaje_transaccion,
            referencia_pago,
            fecha_pago,
            created_at,
            updated_at
        ) VALUES (
            :pedido_id,
            :tarjeta_prueba_id,
            :metodo_pago,
            :monto_pago,
            :estado_pago,
            :resultado_transaccion,
            :mensaje_transaccion,
            :referencia_pago,
            :fecha_pago,
            NOW(),
            NOW()
        )'
    );
    $statement->execute(
        [
            'pedido_id' => $orderId,
            'tarjeta_prueba_id' => null,
            'metodo_pago' => 'pendiente',
            'monto_pago' => round($paymentAmount, 2),
            'estado_pago' => 'pendiente',
            'resultado_transaccion' => null,
            'mensaje_transaccion' => 'Pago simulado pendiente de procesamiento.',
            'referencia_pago' => generateReferenceCode('pay'),
            'fecha_pago' => null,
        ]
    );

    return (int) $connection->lastInsertId();
}

function isValidDateValue(string $value): bool
{
    $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);
    $dateErrors = DateTimeImmutable::getLastErrors();

    if ($date === false) {
        return false;
    }

    if ($dateErrors === false) {
        return true;
    }

    return ($dateErrors['warning_count'] ?? 0) === 0
        && ($dateErrors['error_count'] ?? 0) === 0;
}

function buildFallbackEventName(string $eventType, string $clientName): string
{
    $firstName = trim(explode(' ', $clientName)[0] ?? '');

    if ($firstName === '') {
        return 'Solicitud de ' . $eventType;
    }

    return $eventType . ' de ' . $firstName;
}

function buildOrderNotes(
    array $selectedService,
    string $serviceId,
    string $deliveryFormat,
    string $additionalInformation
): string {
    $notes = [
        'Servicio seleccionado [' . $serviceId . ']: ' . $selectedService['title'] . '.',
        'Categoria de servicio: ' . $selectedService['category'] . '.',
        'Precio placeholder: ' . $selectedService['price_label'] . '.',
        'Monto base simulado: $' . number_format((float) $selectedService['base_amount'], 2, '.', '') . ' MXN.',
        'Formato de entrega solicitado: ' . ucfirst($deliveryFormat) . '.',
    ];

    if ($additionalInformation !== '') {
        $notes[] = 'Notas del cliente: ' . $additionalInformation;
    }

    return implode("\n", $notes);
}

function logOrderRegistrationFailure(Throwable $exception, array $context = []): void
{
    $payload = [
        'message' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'context' => $context,
    ];

    error_log('InvitaStudio order registration error: ' . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}
