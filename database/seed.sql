-- InvitaStudio
-- Seed inicial compatible con MySQL 8+, MariaDB 10+ y Laragon.
-- La contrasena del admin fue generada con password_hash('admin123', PASSWORD_DEFAULT).

-- USE invitastudio;

INSERT INTO usuarios_admin (
    nombre,
    correo,
    password_hash,
    rol,
    activo,
    ultimo_login,
    created_at,
    updated_at
)
SELECT
    'Administrador General',
    'admin@invitastudio.local',
    '$2y$10$8xsHUtDhjWvIdYT0YO8BDeM5.DxOc/Se3NssxyHKBQOZl89OHRLly',
    'super_admin',
    1,
    NULL,
    '2026-05-09 10:00:00',
    '2026-05-09 10:00:00'
WHERE NOT EXISTS (
    SELECT 1
    FROM usuarios_admin
    WHERE correo = 'admin@invitastudio.local'
);

INSERT INTO tarjetas_prueba (
    titular,
    numero_tarjeta,
    fecha_expiracion,
    cvv,
    saldo_disponible,
    activa,
    created_at,
    updated_at
)
SELECT
    'Tarjeta Ficticia Uno',
    '4111111111111111',
    '12/30',
    '123',
    5000.00,
    1,
    '2026-05-09 10:02:00',
    '2026-05-09 10:02:00'
WHERE NOT EXISTS (
    SELECT 1
    FROM tarjetas_prueba
    WHERE numero_tarjeta = '4111111111111111'
);

INSERT INTO tarjetas_prueba (
    titular,
    numero_tarjeta,
    fecha_expiracion,
    cvv,
    saldo_disponible,
    activa,
    created_at,
    updated_at
)
SELECT
    'Tarjeta Ficticia Dos',
    '4000000000000004',
    '12/30',
    '444',
    3200.00,
    1,
    '2026-05-09 10:02:30',
    '2026-05-09 10:02:30'
WHERE NOT EXISTS (
    SELECT 1
    FROM tarjetas_prueba
    WHERE numero_tarjeta = '4000000000000004'
);

INSERT INTO tarjetas_prueba (
    titular,
    numero_tarjeta,
    fecha_expiracion,
    cvv,
    saldo_disponible,
    activa,
    created_at,
    updated_at
)
SELECT
    'Tarjeta Ficticia Tres',
    '4000000000000005',
    '12/30',
    '555',
    4100.00,
    1,
    '2026-05-09 10:03:00',
    '2026-05-09 10:03:00'
WHERE NOT EXISTS (
    SELECT 1
    FROM tarjetas_prueba
    WHERE numero_tarjeta = '4000000000000005'
);

INSERT INTO tarjetas_prueba (
    titular,
    numero_tarjeta,
    fecha_expiracion,
    cvv,
    saldo_disponible,
    activa,
    created_at,
    updated_at
)
SELECT
    'Tarjeta Ficticia Saldo Bajo',
    '4000000000000002',
    '12/30',
    '222',
    50.00,
    1,
    '2026-05-09 10:03:30',
    '2026-05-09 10:03:30'
WHERE NOT EXISTS (
    SELECT 1
    FROM tarjetas_prueba
    WHERE numero_tarjeta = '4000000000000002'
);

INSERT INTO tarjetas_prueba (
    titular,
    numero_tarjeta,
    fecha_expiracion,
    cvv,
    saldo_disponible,
    activa,
    created_at,
    updated_at
)
SELECT
    'Tarjeta Ficticia Inactiva',
    '4000000000000003',
    '12/30',
    '333',
    1500.00,
    0,
    '2026-05-09 10:04:00',
    '2026-05-09 10:04:00'
WHERE NOT EXISTS (
    SELECT 1
    FROM tarjetas_prueba
    WHERE numero_tarjeta = '4000000000000003'
);

INSERT INTO clientes (
    nombre,
    correo,
    telefono,
    medio_contacto,
    created_at,
    updated_at
)
SELECT
    'Mariana Lopez',
    'mariana.lopez@demo.invitastudio.local',
    '5551234567',
    'whatsapp',
    '2026-05-09 10:05:00',
    '2026-05-09 10:05:00'
WHERE NOT EXISTS (
    SELECT 1
    FROM clientes
    WHERE correo = 'mariana.lopez@demo.invitastudio.local'
);

INSERT INTO pedidos (
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
)
SELECT
    c.id,
    'INV-2026-000001',
    'Boda',
    'Boda de Mariana y Luis',
    '2026-09-12 00:00:00',
    '17:00:00',
    'Jardin Los Encinos, Ciudad de Mexico',
    'Minimalista',
    'Blanco, dorado y verde salvia',
    'Floral',
    'Pedido de ejemplo en estado pendiente.',
    'pendiente',
    '2026-05-09 10:10:00',
    '2026-05-09 10:10:00'
FROM clientes c
WHERE c.correo = 'mariana.lopez@demo.invitastudio.local'
AND NOT EXISTS (
    SELECT 1
    FROM pedidos
    WHERE numero_pedido = 'INV-2026-000001'
);

INSERT INTO pedidos (
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
)
SELECT
    c.id,
    'INV-2026-000002',
    'XV Anos',
    'XV de Sofia',
    '2026-08-01 00:00:00',
    '19:30:00',
    'Salon Primavera, Puebla',
    'Elegante',
    'Rosa palo y plata',
    'Vintage',
    'Pedido de ejemplo en estado pago_confirmado.',
    'pago_confirmado',
    '2026-05-09 10:15:00',
    '2026-05-09 10:15:00'
FROM clientes c
WHERE c.correo = 'mariana.lopez@demo.invitastudio.local'
AND NOT EXISTS (
    SELECT 1
    FROM pedidos
    WHERE numero_pedido = 'INV-2026-000002'
);

INSERT INTO pedidos (
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
)
SELECT
    c.id,
    'INV-2026-000003',
    'Cumpleaños',
    'Cumpleaños de Diego',
    '2026-07-18 00:00:00',
    '16:00:00',
    'Monterrey, Nuevo Leon',
    'Moderno',
    'Azul electrico y blanco',
    'Videojuegos',
    'Pedido de ejemplo en estado en_proceso.',
    'en_proceso',
    '2026-05-09 10:20:00',
    '2026-05-09 10:20:00'
FROM clientes c
WHERE c.correo = 'mariana.lopez@demo.invitastudio.local'
AND NOT EXISTS (
    SELECT 1
    FROM pedidos
    WHERE numero_pedido = 'INV-2026-000003'
);

INSERT INTO pedidos (
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
)
SELECT
    c.id,
    'INV-2026-000004',
    'Baby Shower',
    'Baby Shower de Elena',
    '2026-06-28 00:00:00',
    '13:00:00',
    'Queretaro, Queretaro',
    'Acuarela',
    'Beige y terracota',
    'Ositos',
    'Pedido de ejemplo en estado terminado.',
    'terminado',
    '2026-05-09 10:25:00',
    '2026-05-09 10:25:00'
FROM clientes c
WHERE c.correo = 'mariana.lopez@demo.invitastudio.local'
AND NOT EXISTS (
    SELECT 1
    FROM pedidos
    WHERE numero_pedido = 'INV-2026-000004'
);

INSERT INTO pedidos (
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
)
SELECT
    c.id,
    'INV-2026-000005',
    'Aniversario',
    'Aniversario de Ana y Marco',
    '2026-10-22 00:00:00',
    '20:00:00',
    'Guadalajara, Jalisco',
    'Clasico',
    'Negro y dorado',
    'Gala',
    'Pedido de ejemplo en estado entregado.',
    'entregado',
    '2026-05-09 10:30:00',
    '2026-05-09 10:30:00'
FROM clientes c
WHERE c.correo = 'mariana.lopez@demo.invitastudio.local'
AND NOT EXISTS (
    SELECT 1
    FROM pedidos
    WHERE numero_pedido = 'INV-2026-000005'
);

INSERT INTO pedidos (
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
)
SELECT
    c.id,
    'INV-2026-000006',
    'Bautizo',
    'Bautizo de Mateo',
    '2026-11-15 00:00:00',
    '12:00:00',
    'Toluca, Estado de Mexico',
    'Tradicional',
    'Celeste y blanco',
    'Angelical',
    'Pedido de ejemplo en estado cancelado.',
    'cancelado',
    '2026-05-09 10:35:00',
    '2026-05-09 10:35:00'
FROM clientes c
WHERE c.correo = 'mariana.lopez@demo.invitastudio.local'
AND NOT EXISTS (
    SELECT 1
    FROM pedidos
    WHERE numero_pedido = 'INV-2026-000006'
);

INSERT INTO pedidos (
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
)
SELECT
    c.id,
    'INV-2026-000007',
    'Demo',
    'Pedido de prueba local A',
    '2026-12-05 00:00:00',
    '18:30:00',
    'Ciudad de Mexico',
    'Contemporaneo',
    'Azul y plata',
    'Pruebas API',
    'Pedido libre para pruebas repetidas de pago simulado.',
    'pendiente',
    '2026-05-09 10:36:00',
    '2026-05-09 10:36:00'
FROM clientes c
WHERE c.correo = 'mariana.lopez@demo.invitastudio.local'
AND NOT EXISTS (
    SELECT 1
    FROM pedidos
    WHERE numero_pedido = 'INV-2026-000007'
);

INSERT INTO pedidos (
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
)
SELECT
    c.id,
    'INV-2026-000008',
    'Demo',
    'Pedido de prueba local B',
    '2026-12-12 00:00:00',
    '17:00:00',
    'Puebla',
    'Minimalista',
    'Verde y blanco',
    'Pruebas API',
    'Pedido libre para validaciones de rechazo o aprobacion.',
    'pendiente',
    '2026-05-09 10:37:00',
    '2026-05-09 10:37:00'
FROM clientes c
WHERE c.correo = 'mariana.lopez@demo.invitastudio.local'
AND NOT EXISTS (
    SELECT 1
    FROM pedidos
    WHERE numero_pedido = 'INV-2026-000008'
);

INSERT INTO servicios (
    nombre,
    descripcion,
    categoria,
    precio,
    formato_entrega,
    tiempo_entrega,
    imagen_referencia,
    activo,
    created_at,
    updated_at
)
SELECT
    'Invitacion digital basica',
    'Diseno digital esencial para eventos que necesitan una pieza clara, elegante y facil de compartir.',
    'Evento general',
    850.00,
    'imagen',
    '2 a 3 dias habiles',
    'assets/img/Invitaciones/invitacion_basica.png',
    1,
    '2026-05-09 10:38:00',
    '2026-05-09 10:38:00'
WHERE NOT EXISTS (
    SELECT 1
    FROM servicios
    WHERE nombre = 'Invitacion digital basica'
);

INSERT INTO servicios (
    nombre,
    descripcion,
    categoria,
    precio,
    formato_entrega,
    tiempo_entrega,
    imagen_referencia,
    activo,
    created_at,
    updated_at
)
SELECT
    'Invitacion digital premium',
    'Paquete con composicion mas personalizada, secciones adicionales y mayor direccion visual.',
    'Cumpleaños',
    1450.00,
    'pdf',
    '3 a 4 dias habiles',
    'assets/img/Invitaciones/invitacion_premiun.png',
    1,
    '2026-05-09 10:38:30',
    '2026-05-09 10:38:30'
WHERE NOT EXISTS (
    SELECT 1
    FROM servicios
    WHERE nombre = 'Invitacion digital premium'
);

INSERT INTO servicios (
    nombre,
    descripcion,
    categoria,
    precio,
    formato_entrega,
    tiempo_entrega,
    imagen_referencia,
    activo,
    created_at,
    updated_at
)
SELECT
    'Invitacion animada en video',
    'Propuesta con movimiento para presentaciones dinamicas, stories o enlaces compartibles.',
    'Evento general',
    1850.00,
    'video',
    '4 a 5 dias habiles',
    'assets/img/Invitaciones/invitacion_animada_en_video.png',
    1,
    '2026-05-09 10:39:00',
    '2026-05-09 10:39:00'
WHERE NOT EXISTS (
    SELECT 1
    FROM servicios
    WHERE nombre = 'Invitacion animada en video'
);

INSERT INTO servicios (
    nombre,
    descripcion,
    categoria,
    precio,
    formato_entrega,
    tiempo_entrega,
    imagen_referencia,
    activo,
    created_at,
    updated_at
)
SELECT
    'Invitacion para XV años',
    'Servicio enfocado en celebraciones de XV años con tono elegante y espacio para detalles del evento.',
    'XV años',
    1600.00,
    'imagen',
    '3 a 4 dias habiles',
    'assets/img/Invitaciones/invitacion_xv.png',
    1,
    '2026-05-09 10:39:30',
    '2026-05-09 10:39:30'
WHERE NOT EXISTS (
    SELECT 1
    FROM servicios
    WHERE nombre = 'Invitacion para XV años'
);

UPDATE pedidos
SET
    tipo_evento = 'Cumpleaños',
    nombre_evento = 'Cumpleaños de Diego'
WHERE numero_pedido = 'INV-2026-000003'
  AND (tipo_evento <> 'Cumpleaños' OR nombre_evento <> 'Cumpleaños de Diego');

UPDATE servicios
SET categoria = 'Cumpleaños'
WHERE categoria = 'Cumpleanos';

UPDATE servicios
SET
    nombre = 'Invitacion para XV años',
    descripcion = 'Servicio enfocado en celebraciones de XV años con tono elegante y espacio para detalles del evento.',
    categoria = 'XV años'
WHERE nombre = 'Invitacion para XV anos'
   OR categoria = 'XV anos';

INSERT INTO servicios (
    nombre,
    descripcion,
    categoria,
    precio,
    formato_entrega,
    tiempo_entrega,
    imagen_referencia,
    activo,
    created_at,
    updated_at
)
SELECT
    'Invitacion para boda',
    'Servicio orientado a bodas con estructura romantica, datos ceremoniales y estilo mas refinado.',
    'Boda',
    1750.00,
    'pdf',
    '4 a 6 dias habiles',
    NULL,
    1,
    '2026-05-09 10:39:45',
    '2026-05-09 10:39:45'
WHERE NOT EXISTS (
    SELECT 1
    FROM servicios
    WHERE nombre = 'Invitacion para boda'
);

INSERT INTO pagos (
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
)
SELECT
    p.id,
    NULL,
    'transferencia',
    1500.00,
    'pendiente',
    'pendiente',
    'Pago pendiente de confirmacion manual.',
    'PAGO-DEMO-0001',
    NULL,
    '2026-05-09 10:40:00',
    '2026-05-09 10:40:00'
FROM pedidos p
WHERE p.numero_pedido = 'INV-2026-000001'
AND NOT EXISTS (
    SELECT 1
    FROM pagos
    WHERE referencia_pago = 'PAGO-DEMO-0001'
);

INSERT INTO pagos (
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
)
SELECT
    p.id,
    t.id,
    'tarjeta_prueba',
    2200.00,
    'confirmado',
    'aprobado',
    'Pago aprobado con tarjeta ficticia de prueba.',
    'PAGO-DEMO-0002',
    '2026-05-09 11:00:00',
    '2026-05-09 11:00:00',
    '2026-05-09 11:00:00'
FROM pedidos p
JOIN tarjetas_prueba t ON t.numero_tarjeta = '4000000000000004'
WHERE p.numero_pedido = 'INV-2026-000002'
AND NOT EXISTS (
    SELECT 1
    FROM pagos
    WHERE referencia_pago = 'PAGO-DEMO-0002'
);

INSERT INTO pagos (
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
)
SELECT
    p.id,
    t.id,
    'tarjeta_prueba',
    1800.00,
    'rechazado',
    'tarjeta_inactiva',
    'La tarjeta ficticia esta inactiva.',
    'PAGO-DEMO-0003',
    '2026-05-09 11:20:00',
    '2026-05-09 11:20:00',
    '2026-05-09 11:20:00'
FROM pedidos p
JOIN tarjetas_prueba t ON t.numero_tarjeta = '4000000000000003'
WHERE p.numero_pedido = 'INV-2026-000006'
AND NOT EXISTS (
    SELECT 1
    FROM pagos
    WHERE referencia_pago = 'PAGO-DEMO-0003'
);

INSERT INTO pagos (
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
)
SELECT
    p.id,
    t.id,
    'tarjeta_prueba',
    2500.00,
    'reembolsado',
    'aprobado',
    'Pago reembolsado en entorno local de pruebas.',
    'PAGO-DEMO-0004',
    '2026-05-09 11:40:00',
    '2026-05-09 11:40:00',
    '2026-05-09 11:40:00'
FROM pedidos p
JOIN tarjetas_prueba t ON t.numero_tarjeta = '4000000000000005'
WHERE p.numero_pedido = 'INV-2026-000005'
AND NOT EXISTS (
    SELECT 1
    FROM pagos
    WHERE referencia_pago = 'PAGO-DEMO-0004'
);

INSERT INTO pagos (
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
)
SELECT
    p.id,
    t.id,
    'tarjeta_prueba',
    300.00,
    'rechazado',
    'saldo_insuficiente',
    'La tarjeta ficticia no cuenta con saldo suficiente.',
    'PAGO-DEMO-0005',
    '2026-05-09 11:50:00',
    '2026-05-09 11:50:00',
    '2026-05-09 11:50:00'
FROM pedidos p
JOIN tarjetas_prueba t ON t.numero_tarjeta = '4000000000000002'
WHERE p.numero_pedido = 'INV-2026-000001'
AND NOT EXISTS (
    SELECT 1
    FROM pagos
    WHERE referencia_pago = 'PAGO-DEMO-0005'
);

INSERT INTO entregas (
    pedido_id,
    formato_entrega,
    archivo_final,
    fecha_entrega,
    notas_entrega,
    created_at,
    updated_at
)
SELECT
    p.id,
    'pdf',
    'uploads/entregas/invitacion-aniversario-demo.pdf',
    '2026-05-09 12:00:00',
    'Entrega de ejemplo para pruebas locales.',
    '2026-05-09 12:00:00',
    '2026-05-09 12:00:00'
FROM pedidos p
WHERE p.numero_pedido = 'INV-2026-000005'
AND NOT EXISTS (
    SELECT 1
    FROM entregas e
    WHERE e.pedido_id = p.id
    AND e.archivo_final = 'uploads/entregas/invitacion-aniversario-demo.pdf'
);

INSERT INTO mensajes_pedido (
    pedido_id,
    tipo_usuario,
    mensaje,
    archivo_adjunto,
    created_at
)
SELECT
    p.id,
    'cliente',
    'Hola, me gustaria confirmar si pueden incluir mapa de ubicacion.',
    NULL,
    '2026-05-09 12:10:00'
FROM pedidos p
WHERE p.numero_pedido = 'INV-2026-000001'
AND NOT EXISTS (
    SELECT 1
    FROM mensajes_pedido
    WHERE pedido_id = p.id
    AND tipo_usuario = 'cliente'
    AND created_at = '2026-05-09 12:10:00'
);

INSERT INTO mensajes_pedido (
    pedido_id,
    tipo_usuario,
    mensaje,
    archivo_adjunto,
    created_at
)
SELECT
    p.id,
    'admin',
    'Si, podemos agregar el mapa. Comparte la direccion final para integrarlo.',
    NULL,
    '2026-05-09 12:15:00'
FROM pedidos p
WHERE p.numero_pedido = 'INV-2026-000001'
AND NOT EXISTS (
    SELECT 1
    FROM mensajes_pedido
    WHERE pedido_id = p.id
    AND tipo_usuario = 'admin'
    AND created_at = '2026-05-09 12:15:00'
);

INSERT INTO actividad_log (
    usuario_tipo,
    usuario_id,
    accion,
    modulo,
    referencia_id,
    descripcion,
    ip_address,
    created_at
)
SELECT
    'sistema',
    NULL,
    'seed_inicial',
    'database',
    NULL,
    'Carga inicial de datos de prueba para InvitaStudio.',
    '127.0.0.1',
    '2026-05-09 12:20:00'
WHERE NOT EXISTS (
    SELECT 1
    FROM actividad_log
    WHERE usuario_tipo = 'sistema'
    AND accion = 'seed_inicial'
    AND created_at = '2026-05-09 12:20:00'
);

INSERT INTO actividad_log (
    usuario_tipo,
    usuario_id,
    accion,
    modulo,
    referencia_id,
    descripcion,
    ip_address,
    created_at
)
SELECT
    'admin',
    ua.id,
    'crear_entrega_demo',
    'entregas',
    p.id,
    'Registro de entrega de ejemplo para el pedido INV-2026-000005.',
    '127.0.0.1',
    '2026-05-09 12:25:00'
FROM usuarios_admin ua
JOIN pedidos p ON p.numero_pedido = 'INV-2026-000005'
WHERE ua.correo = 'admin@invitastudio.local'
AND NOT EXISTS (
    SELECT 1
    FROM actividad_log
    WHERE usuario_tipo = 'admin'
    AND usuario_id = ua.id
    AND accion = 'crear_entrega_demo'
    AND referencia_id = p.id
);

UPDATE servicios
SET imagen_referencia = 'assets/img/Invitaciones/invitacion_basica.png'
WHERE nombre = 'Invitacion digital basica';

UPDATE servicios
SET imagen_referencia = 'assets/img/Invitaciones/invitacion_premiun.png'
WHERE nombre = 'Invitacion digital premium';

UPDATE servicios
SET imagen_referencia = 'assets/img/Invitaciones/invitacion_animada_en_video.png'
WHERE nombre = 'Invitacion animada en video';

UPDATE servicios
SET imagen_referencia = 'assets/img/Invitaciones/invitacion_xv.png'
WHERE nombre IN ('Invitacion para XV anos', 'Invitacion para XV aÃ±os', 'Invitacion para XV años');

UPDATE servicios
SET imagen_referencia = 'assets/img/Invitaciones/invitacion_boda.png'
WHERE nombre = 'Invitacion para boda';
