<?php
declare(strict_types=1);

return renderEmailLayout(
    [
        'title' => 'Entrega final disponible',
        'preheader' => 'La invitacion final ya quedo lista para descarga.',
        'intro' => 'Tu pedido ya fue marcado como entregado. Desde el modulo de estado puedes acceder al archivo final y descargarlo con los mismos datos del pedido.',
        'sections' => [
            ['label' => 'Numero de pedido', 'value' => $emailData['numero_pedido'] ?? ''],
            ['label' => 'Formato de entrega', 'value' => $emailData['formato_entrega'] ?? ''],
            ['label' => 'Fecha de entrega', 'value' => $emailData['fecha_entrega'] ?? ''],
            ['label' => 'Notas', 'value' => $emailData['notas_entrega'] ?? 'Sin notas adicionales'],
        ],
        'body_lines' => [
            'Usa el boton inferior para abrir la pantalla de seguimiento y descargar la entrega final.',
        ],
        'cta_label' => 'Abrir seguimiento',
        'cta_url' => $emailData['status_url'] ?? '',
    ]
);
