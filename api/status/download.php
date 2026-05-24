<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/common.php';
require_once __DIR__ . '/common.php';

requireRequestMethod(['POST']);

$requestData = readRequestData();
$connection = getDatabaseConnection();
$ipAddress = getClientIpAddress();

try {
    enforceStatusRateLimit('download', $ipAddress, 6, 300);

    $validatedInput = validateStatusLookupInput($requestData);
    $order = findPublicOrderStatusRecord(
        $connection,
        $validatedInput['numero_pedido'],
        $validatedInput['correo']
    );

    if ($order === null || (string) ($order['estado_pedido'] ?? '') !== 'entregado') {
        errorResponse('Archivo no disponible para este pedido.', [], 404);
    }

    $filePath = resolvePublicDeliveryAbsolutePath($order);

    if ($filePath === null) {
        errorResponse('Archivo no disponible para este pedido.', [], 404);
    }

    $downloadName = resolvePublicDeliveryDownloadName($order, $filePath);
    $mimeType = resolvePublicDeliveryMimeType($filePath);
    $fileSize = filesize($filePath);

    if ($fileSize === false) {
        errorResponse('No fue posible preparar la descarga.', [], 500);
    }

    try {
        createActivityLogEntry(
            $connection,
            'cliente',
            null,
            'descargar_entrega_publica',
            'deliveries',
            (int) ($order['id'] ?? 0),
            'Descarga publica de la entrega final para el pedido ' . (string) ($order['numero_pedido'] ?? '') . '.',
            $ipAddress
        );
    } catch (Throwable $loggingException) {
        error_log('InvitaStudio delivery download log error: ' . $loggingException->getMessage());
    }

    if (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Description: File Transfer');
    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: attachment; filename="' . addslashes($downloadName) . '"');
    header('Content-Length: ' . (string) $fileSize);
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    readfile($filePath);
    exit;
} catch (Throwable $exception) {
    logPublicStatusFailure(
        'Fallo durante la descarga publica de una entrega.',
        $exception,
        ['ip_address' => $ipAddress]
    );
    errorResponse('No fue posible descargar el archivo solicitado.', [], 500);
}
