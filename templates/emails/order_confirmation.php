<?php
declare(strict_types=1);

return renderEmailLayout(
    [
        'title' => 'Confirmacion de pedido',
        'preheader' => 'Recibimos tu solicitud y ya tiene numero de seguimiento.',
        'intro' => 'Tu solicitud fue registrada correctamente en InvitaStudio. Desde este momento puedes continuar con el pago o consultar el estado del pedido cuando lo necesites.',
        'sections' => [
            ['label' => 'Numero de pedido', 'value' => $emailData['numero_pedido'] ?? ''],
            ['label' => 'Fecha del evento', 'value' => $emailData['fecha_evento'] ?? ''],
            ['label' => 'Estado inicial', 'value' => $emailData['estado_inicial'] ?? 'Pendiente'],
            ['label' => 'Evento', 'value' => $emailData['nombre_evento'] ?? ''],
            ['label' => 'Tipo de evento', 'value' => $emailData['tipo_evento'] ?? ''],
            ['label' => 'Servicio', 'value' => $emailData['servicio'] ?? ''],
            ['label' => 'Formato solicitado', 'value' => $emailData['formato_entrega'] ?? ''],
            ['label' => 'Monto base', 'value' => $emailData['monto_pago'] ?? ''],
        ],
        'body_lines' => [
            'Conserva este numero de pedido para futuras consultas.',
            'Si ya estas listo, puedes avanzar al modulo de pago simulado desde el boton inferior.',
        ],
        'cta_label' => 'Ir a pago',
        'cta_url' => $emailData['payment_url'] ?? '',
        'footer' => 'Tambien puedes consultar tu seguimiento en: ' . ($emailData['status_url'] ?? ''),
    ]
);
