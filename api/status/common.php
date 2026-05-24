<?php
declare(strict_types=1);

if (!function_exists('normalizeOrderNumber')) {
    function normalizeOrderNumber(mixed $value): string
    {
        $normalizedValue = sanitizeString($value);

        if ($normalizedValue === '') {
            return '';
        }

        $normalizedValue = str_replace(' ', '', $normalizedValue);

        if (function_exists('mb_strtoupper')) {
            return mb_strtoupper($normalizedValue, 'UTF-8');
        }

        return strtoupper($normalizedValue);
    }
}

if (!function_exists('validateStatusLookupInput')) {
    function validateStatusLookupInput(array $requestData): array
    {
        $numeroPedido = normalizeOrderNumber($requestData['numero_pedido'] ?? null);
        $correo = normalizeString($requestData['correo'] ?? null);

        $errors = mergeValidationErrors(
            validateStringLength($numeroPedido, 'numero_pedido', 5, 40),
            validateEmailValue($correo, 'correo')
        );

        if ($numeroPedido !== '' && preg_match('/^[A-Z0-9-]+$/', $numeroPedido) !== 1) {
            addValidationMessage($errors, 'numero_pedido', 'El numero de pedido contiene caracteres no permitidos.');
        }

        assertValidInput($errors);

        return [
            'numero_pedido' => $numeroPedido,
            'correo' => $correo,
        ];
    }
}

if (!function_exists('enforceStatusRateLimit')) {
    function enforceStatusRateLimit(string $action, string $clientIp, int $maxAttempts, int $windowSeconds): void
    {
        $storagePath = $GLOBALS['appConfig']['STORAGE_PATH']
            ?? $GLOBALS['appConfig']['storage_path']
            ?? dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage';

        $rateLimitDirectory = rtrim($storagePath, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . 'rate_limits';

        if (!is_dir($rateLimitDirectory) && @mkdir($rateLimitDirectory, 0775, true) === false && !is_dir($rateLimitDirectory)) {
            return;
        }

        $rateLimitFile = $rateLimitDirectory . DIRECTORY_SEPARATOR . 'status_lookup.json';
        $fileHandle = @fopen($rateLimitFile, 'c+');

        if ($fileHandle === false) {
            return;
        }

        try {
            if (!flock($fileHandle, LOCK_EX)) {
                fclose($fileHandle);
                return;
            }

            $fileSize = filesize($rateLimitFile);
            $rawContents = $fileSize > 0 ? fread($fileHandle, $fileSize) : '';
            $rateLimitEntries = is_string($rawContents) && trim($rawContents) !== ''
                ? json_decode($rawContents, true)
                : [];

            if (!is_array($rateLimitEntries)) {
                $rateLimitEntries = [];
            }

            $now = time();
            $threshold = $now - $windowSeconds;
            $entryKey = $action . '|' . $clientIp;

            foreach ($rateLimitEntries as $key => $timestamps) {
                if (!is_array($timestamps)) {
                    unset($rateLimitEntries[$key]);
                    continue;
                }

                $filteredTimestamps = array_values(
                    array_filter(
                        $timestamps,
                        static fn (mixed $timestamp): bool => is_int($timestamp) && $timestamp >= $threshold
                    )
                );

                if ($filteredTimestamps === []) {
                    unset($rateLimitEntries[$key]);
                    continue;
                }

                $rateLimitEntries[$key] = $filteredTimestamps;
            }

            $entryTimestamps = $rateLimitEntries[$entryKey] ?? [];

            if (count($entryTimestamps) >= $maxAttempts) {
                flock($fileHandle, LOCK_UN);
                fclose($fileHandle);
                errorResponse(
                    'Has realizado demasiadas consultas. Espera unos minutos e intenta nuevamente.',
                    [],
                    429
                );
            }

            $entryTimestamps[] = $now;
            $rateLimitEntries[$entryKey] = $entryTimestamps;

            rewind($fileHandle);
            ftruncate($fileHandle, 0);
            fwrite(
                $fileHandle,
                (string) json_encode($rateLimitEntries, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );

            fflush($fileHandle);
            flock($fileHandle, LOCK_UN);
            fclose($fileHandle);
        } catch (Throwable $exception) {
            fclose($fileHandle);
        }
    }
}

if (!function_exists('findPublicOrderStatusRecord')) {
    function findPublicOrderStatusRecord(PDO $connection, string $numeroPedido, string $correo): ?array
    {
        $statement = $connection->prepare(
            'SELECT
                p.id,
                p.numero_pedido,
                p.tipo_evento,
                p.nombre_evento,
                p.fecha_evento,
                p.hora_evento,
                p.ubicacion_evento,
                p.estilo_diseno,
                p.colores,
                p.tematica,
                p.estado_pedido,
                p.informacion_adicional,
                p.created_at AS pedido_created_at,
                p.updated_at AS pedido_updated_at,
                c.correo AS cliente_correo,
                lp.monto_pago,
                lp.estado_pago,
                lp.mensaje_transaccion,
                lp.fecha_pago,
                lp.created_at AS pago_created_at,
                lp.updated_at AS pago_updated_at,
                d.formato_entrega,
                d.archivo_final,
                d.fecha_entrega,
                d.notas_entrega,
                d.created_at AS entrega_created_at,
                d.updated_at AS entrega_updated_at
             FROM pedidos p
             INNER JOIN clientes c ON c.id = p.cliente_id
             LEFT JOIN pagos lp ON lp.id = (
                SELECT p2.id
                FROM pagos p2
                WHERE p2.pedido_id = p.id
                ORDER BY COALESCE(p2.fecha_pago, p2.updated_at, p2.created_at) DESC, p2.id DESC
                LIMIT 1
             )
             LEFT JOIN entregas d ON d.id = (
                SELECT d2.id
                FROM entregas d2
                WHERE d2.pedido_id = p.id
                ORDER BY COALESCE(d2.fecha_entrega, d2.updated_at, d2.created_at) DESC, d2.id DESC
                LIMIT 1
             )
             WHERE p.numero_pedido = :numero_pedido
               AND c.correo = :correo
             LIMIT 1'
        );
        $statement->execute(
            [
                'numero_pedido' => $numeroPedido,
                'correo' => $correo,
            ]
        );

        $order = $statement->fetch(PDO::FETCH_ASSOC);

        return $order === false ? null : $order;
    }
}

if (!function_exists('buildPublicStatusResponseData')) {
    function buildPublicStatusResponseData(array $order): array
    {
        $orderStatus = (string) ($order['estado_pedido'] ?? 'pendiente');
        $paymentStatus = (string) ($order['estado_pago'] ?? 'pendiente');
        $paymentAmount = isset($order['monto_pago']) ? round((float) $order['monto_pago'], 2) : 0.0;
        $deliveryFormat = resolvePublicDeliveryFormat($order);
        $deliveryFilePath = $orderStatus === 'entregado' ? resolvePublicDeliveryAbsolutePath($order) : null;
        $statusMeta = getPublicStatusMeta($orderStatus);
        $canProcessPayment = $orderStatus === 'pendiente'
            && !in_array($paymentStatus, ['confirmado', 'reembolsado'], true)
            && $paymentAmount > 0;

        return [
            'numero_pedido' => (string) ($order['numero_pedido'] ?? ''),
            'tipo_evento' => (string) ($order['tipo_evento'] ?? ''),
            'nombre_evento' => (string) ($order['nombre_evento'] ?? ''),
            'estado_pedido' => $orderStatus,
            'estado_pago' => $paymentStatus,
            'fecha_evento' => (string) ($order['fecha_evento'] ?? ''),
            'hora_evento' => (string) ($order['hora_evento'] ?? ''),
            'ubicacion_evento' => (string) ($order['ubicacion_evento'] ?? ''),
            'estilo_diseno' => (string) ($order['estilo_diseno'] ?? ''),
            'colores' => (string) ($order['colores'] ?? ''),
            'tematica' => (string) ($order['tematica'] ?? ''),
            'informacion_adicional' => sanitizeString($order['informacion_adicional'] ?? ''),
            'formato_entrega' => $deliveryFormat,
            'monto_pago' => $paymentAmount,
            'can_process_payment' => $canProcessPayment,
            'payment_url' => $canProcessPayment
                ? 'payment.html?numero_pedido=' . rawurlencode((string) ($order['numero_pedido'] ?? ''))
                    . '&correo=' . rawurlencode((string) ($order['cliente_correo'] ?? ''))
                : '',
            'mensaje_estado' => $statusMeta['message'],
            'tiempo_estimado' => $statusMeta['estimated_time'],
            'ultima_actualizacion' => resolvePublicLastUpdatedAt($order),
            'timeline' => buildPublicStatusTimeline($orderStatus),
            'entrega' => [
                'disponible' => $orderStatus === 'entregado' && $deliveryFilePath !== null,
                'confirmacion' => $orderStatus === 'entregado'
                    ? 'La entrega final esta lista para descarga.'
                    : 'La entrega final se habilitara cuando el pedido quede marcado como entregado.',
                'fecha_entrega' => (string) ($order['fecha_entrega'] ?? ''),
                'formato_entrega' => $deliveryFormat,
                'ultima_nota' => sanitizeString($order['notas_entrega'] ?? ''),
            ],
        ];
    }
}

if (!function_exists('buildPublicStatusTimeline')) {
    function buildPublicStatusTimeline(string $orderStatus): array
    {
        $stepOrder = [
            'pendiente' => 0,
            'pago_confirmado' => 1,
            'en_proceso' => 2,
            'terminado' => 3,
            'entregado' => 4,
        ];

        $currentStepIndex = $stepOrder[$orderStatus] ?? -1;

        $steps = [
            ['key' => 'solicitud_recibida', 'label' => 'Solicitud recibida'],
            ['key' => 'pago_confirmado', 'label' => 'Pago confirmado'],
            ['key' => 'en_proceso', 'label' => 'En proceso'],
            ['key' => 'terminado', 'label' => 'Terminado'],
            ['key' => 'entregado', 'label' => 'Entregado'],
        ];

        return array_map(
            static function (array $step, int $index) use ($currentStepIndex, $orderStatus): array {
                $state = 'upcoming';

                if ($orderStatus === 'cancelado') {
                    $state = $index === 0 ? 'complete' : 'upcoming';
                } elseif ($index < $currentStepIndex) {
                    $state = 'complete';
                } elseif ($index === $currentStepIndex) {
                    $state = 'current';
                }

                return [
                    'key' => $step['key'],
                    'label' => $step['label'],
                    'state' => $state,
                    'detail' => getPublicTimelineDetail($step['key'], $state, $orderStatus),
                ];
            },
            $steps,
            array_keys($steps)
        );
    }
}

if (!function_exists('getPublicTimelineDetail')) {
    function getPublicTimelineDetail(string $stepKey, string $state, string $orderStatus): string
    {
        $details = [
            'solicitud_recibida' => [
                'complete' => 'Tu solicitud ya fue registrada.',
                'current' => 'Estamos esperando la siguiente actualizacion del pedido.',
                'upcoming' => 'La solicitud aparecera aqui cuando el pedido se registre.',
            ],
            'pago_confirmado' => [
                'complete' => 'El pago fue validado correctamente.',
                'current' => 'El pago ya fue validado y el pedido sigue avanzando.',
                'upcoming' => 'Este paso se activara cuando el pago quede confirmado.',
            ],
            'en_proceso' => [
                'complete' => 'El equipo ya trabajo en el desarrollo de la invitacion.',
                'current' => 'Tu invitacion esta en produccion.',
                'upcoming' => 'Aqui veras cuando el pedido entre en produccion.',
            ],
            'terminado' => [
                'complete' => 'La propuesta final ya fue concluida.',
                'current' => 'La invitacion final esta lista para entrega.',
                'upcoming' => 'Este paso indicara cuando el material quede listo.',
            ],
            'entregado' => [
                'complete' => 'La entrega final ya fue realizada.',
                'current' => 'La entrega final esta disponible.',
                'upcoming' => 'La descarga se habilitara al finalizar el pedido.',
            ],
        ];

        if ($orderStatus === 'cancelado' && $stepKey !== 'solicitud_recibida') {
            return 'El pedido fue cancelado antes de completar esta etapa.';
        }

        return $details[$stepKey][$state] ?? '';
    }
}

if (!function_exists('getPublicStatusMeta')) {
    function getPublicStatusMeta(string $orderStatus): array
    {
        $meta = [
            'pendiente' => [
                'message' => 'Recibimos tu solicitud y esta pendiente de avanzar al siguiente paso.',
                'estimated_time' => 'En espera de confirmacion de pago.',
            ],
            'pago_confirmado' => [
                'message' => 'El pago fue validado y el pedido quedo listo para produccion.',
                'estimated_time' => 'Inicio de produccion proximo.',
            ],
            'en_proceso' => [
                'message' => 'El equipo ya esta trabajando en tu invitacion.',
                'estimated_time' => 'Tiempo estimado sujeto al alcance del pedido.',
            ],
            'terminado' => [
                'message' => 'La invitacion ya esta terminada y preparando entrega.',
                'estimated_time' => 'Entrega final proxima.',
            ],
            'entregado' => [
                'message' => 'El pedido ya fue entregado y la descarga esta habilitada.',
                'estimated_time' => 'Proceso completado.',
            ],
            'cancelado' => [
                'message' => 'El pedido fue cancelado y ya no admite nuevas actualizaciones.',
                'estimated_time' => 'Proceso cerrado.',
            ],
        ];

        return $meta[$orderStatus] ?? $meta['pendiente'];
    }
}

if (!function_exists('resolvePublicDeliveryFormat')) {
    function resolvePublicDeliveryFormat(array $order): string
    {
        $deliveryFormat = normalizeString($order['formato_entrega'] ?? null);

        if ($deliveryFormat !== '') {
            return $deliveryFormat;
        }

        $notes = (string) ($order['informacion_adicional'] ?? '');

        if (preg_match('/Formato de entrega solicitado:\s*([^\.\n]+)/i', $notes, $matches) === 1) {
            return normalizeString($matches[1] ?? '');
        }

        return '';
    }
}

if (!function_exists('resolvePublicLastUpdatedAt')) {
    function resolvePublicLastUpdatedAt(array $order): string
    {
        $dates = array_filter(
            [
                formatDateValue((string) ($order['entrega_updated_at'] ?? '')),
                formatDateValue((string) ($order['fecha_entrega'] ?? '')),
                formatDateValue((string) ($order['pago_updated_at'] ?? '')),
                formatDateValue((string) ($order['fecha_pago'] ?? '')),
                formatDateValue((string) ($order['pedido_updated_at'] ?? '')),
                formatDateValue((string) ($order['pedido_created_at'] ?? '')),
            ]
        );

        rsort($dates);

        return $dates[0] ?? '';
    }
}

if (!function_exists('resolvePublicDeliveryAbsolutePath')) {
    function resolvePublicDeliveryAbsolutePath(array $order): ?string
    {
        return resolveStoredDeliveryAbsolutePath((string) ($order['archivo_final'] ?? ''));
    }
}

if (!function_exists('resolvePublicDeliveryDownloadName')) {
    function resolvePublicDeliveryDownloadName(array $order, string $filePath): string
    {
        return resolveStoredDeliveryDownloadName((string) ($order['numero_pedido'] ?? 'pedido'), $filePath);
    }
}

if (!function_exists('resolvePublicDeliveryMimeType')) {
    function resolvePublicDeliveryMimeType(string $filePath): string
    {
        return resolveStoredDeliveryMimeType($filePath);
    }
}

if (!function_exists('logPublicStatusFailure')) {
    function logPublicStatusFailure(string $message, Throwable $exception, array $context = []): void
    {
        $payload = [
            'message' => $message,
            'exception' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'context' => $context,
        ];

        error_log('InvitaStudio status lookup error: ' . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
