<?php
declare(strict_types=1);

return renderEmailLayout(
    [
        'title' => 'Pago confirmado',
        'preheader' => 'Tu pago ya fue validado y el pedido puede avanzar.',
        'intro' => 'El pago asociado a tu pedido fue confirmado correctamente. El equipo de InvitaStudio ya puede continuar con la siguiente etapa del flujo.',
        'sections' => [
            ['label' => 'Numero de pedido', 'value' => $emailData['numero_pedido'] ?? ''],
            ['label' => 'Monto', 'value' => $emailData['monto_pago'] ?? ''],
            ['label' => 'Estado', 'value' => $emailData['estado_pago'] ?? 'Confirmado'],
            ['label' => 'Referencia de pago', 'value' => $emailData['referencia_pago'] ?? ''],
            ['label' => 'Fecha de confirmacion', 'value' => $emailData['fecha_pago'] ?? ''],
        ],
        'body_lines' => [
            'Puedes consultar el avance del pedido en cualquier momento usando el enlace de seguimiento.',
        ],
        'cta_label' => 'Consultar seguimiento',
        'cta_url' => $emailData['status_url'] ?? '',
    ]
);
