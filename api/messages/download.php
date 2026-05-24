<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/common.php';
require_once dirname(__DIR__) . '/status/common.php';

requireRequestMethod(['GET']);

$messageId = requirePositiveInt($_GET['id'] ?? $_GET['message_id'] ?? null, 'id');
$connection = getDatabaseConnection();
$adminUser = getAdminUser();

try {
    $statement = $connection->prepare(
        'SELECT
            m.id,
            m.pedido_id,
            m.archivo_adjunto,
            p.numero_pedido,
            c.correo AS cliente_correo
         FROM mensajes_pedido m
         INNER JOIN pedidos p ON p.id = m.pedido_id
         INNER JOIN clientes c ON c.id = p.cliente_id
         WHERE m.id = :id
         LIMIT 1'
    );
    $statement->execute(['id' => $messageId]);
    $message = $statement->fetch(PDO::FETCH_ASSOC);

    if ($message === false || trim((string) ($message['archivo_adjunto'] ?? '')) === '') {
        sendJsonResponse(404, false, 'Adjunto no encontrado.');
    }

    if ($adminUser === null) {
        $validatedLookup = validateStatusLookupInput($_GET);
        $storedOrderNumber = (string) ($message['numero_pedido'] ?? '');
        $storedEmail = normalizeString($message['cliente_correo'] ?? '');

        if (
            $validatedLookup['numero_pedido'] !== $storedOrderNumber
            || $validatedLookup['correo'] !== $storedEmail
        ) {
            errorResponse('No autorizado.', [], 401);
        }
    }

    $storedPath = (string) ($message['archivo_adjunto'] ?? '');
    $filePath = resolveStoredMessageAbsolutePath($storedPath);

    if ($filePath === null) {
        sendJsonResponse(404, false, 'Adjunto no disponible.');
    }

    $downloadName = resolveStoredMessageDownloadName(
        (string) ($message['numero_pedido'] ?? 'pedido'),
        $filePath
    );
    $mimeType = resolveStoredMessageMimeType($filePath);
    $fileSize = filesize($filePath);

    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . (string) ($fileSize !== false ? $fileSize : 0));
    header('Content-Disposition: attachment; filename="' . rawurlencode($downloadName) . '"; filename*=UTF-8\'\'' . rawurlencode($downloadName));
    header('X-Content-Type-Options: nosniff');
    readfile($filePath);
    exit;
} catch (Throwable $exception) {
    errorResponse('No fue posible descargar el adjunto.', [], 500);
}
