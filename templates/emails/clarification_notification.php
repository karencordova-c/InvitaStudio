<?php
declare(strict_types=1);

$recipientType = ($emailData['destinatario_tipo'] ?? 'cliente') === 'admin' ? 'admin' : 'cliente';
$intro = $recipientType === 'admin'
    ? 'Se registro un nuevo mensaje del cliente y ya puedes revisarlo desde el seguimiento del pedido.'
    : 'Hay un nuevo mensaje asociado a tu pedido. Revísalo y responde si necesitas continuar la conversacion.';

$bodyLines = $recipientType === 'admin'
    ? [
        'Cliente: ' . (string) ($emailData['cliente_nombre'] ?? 'Sin nombre'),
        'Correo de contacto: ' . (string) ($emailData['cliente_correo'] ?? 'Sin correo'),
    ]
    : [
        'Puedes responder directamente desde la pantalla de estado del pedido.',
    ];

return renderEmailLayout(
    [
        'title' => 'Nueva aclaracion',
        'preheader' => 'Hay un nuevo mensaje asociado al pedido.',
        'intro' => $intro,
        'sections' => [
            ['label' => 'Numero de pedido', 'value' => $emailData['numero_pedido'] ?? ''],
            ['label' => 'Autor', 'value' => $emailData['autor'] ?? ''],
            ['label' => 'Mensaje', 'value' => $emailData['mensaje'] ?? ''],
        ],
        'body_lines' => $bodyLines,
        'cta_label' => 'Ver conversacion',
        'cta_url' => $emailData['status_url'] ?? '',
    ]
);
