-- InvitaStudio
-- Esquema inicial compatible con MySQL 8+, MariaDB 10+ y Laragon.

-- CREATE DATABASE IF NOT EXISTS invitastudio
-- CHARACTER SET utf8mb4
-- COLLATE utf8mb4_unicode_ci;

-- USE invitastudio;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS actividad_log;
DROP TABLE IF EXISTS mensajes_pedido;
DROP TABLE IF EXISTS entregas;
DROP TABLE IF EXISTS pagos;
DROP TABLE IF EXISTS tarjetas_prueba;
DROP TABLE IF EXISTS pedidos;
DROP TABLE IF EXISTS servicios;
DROP TABLE IF EXISTS usuarios_admin;
DROP TABLE IF EXISTS clientes;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    correo VARCHAR(150) NOT NULL,
    telefono VARCHAR(30) NOT NULL,
    medio_contacto VARCHAR(50) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_clientes_correo (correo),
    KEY idx_clientes_telefono (telefono)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE usuarios_admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    correo VARCHAR(150) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('super_admin', 'operador', 'disenador') NOT NULL DEFAULT 'operador',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    ultimo_login DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_usuarios_admin_correo (correo),
    KEY idx_usuarios_admin_activo (activo),
    KEY idx_usuarios_admin_rol (rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    numero_pedido VARCHAR(40) NOT NULL,
    tipo_evento VARCHAR(100) NOT NULL,
    nombre_evento VARCHAR(150) NOT NULL,
    fecha_evento DATETIME NOT NULL,
    hora_evento TIME NULL,
    ubicacion_evento VARCHAR(255) NULL,
    estilo_diseno VARCHAR(120) NULL,
    colores VARCHAR(255) NULL,
    tematica VARCHAR(120) NULL,
    informacion_adicional TEXT NULL,
    estado_pedido ENUM(
        'pendiente',
        'pago_confirmado',
        'en_proceso',
        'terminado',
        'entregado',
        'cancelado'
    ) NOT NULL DEFAULT 'pendiente',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_pedidos_cliente
        FOREIGN KEY (cliente_id) REFERENCES clientes (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    UNIQUE KEY uq_pedidos_numero_pedido (numero_pedido),
    KEY idx_pedidos_cliente_id (cliente_id),
    KEY idx_pedidos_estado_pedido (estado_pedido),
    KEY idx_pedidos_fecha_evento (fecha_evento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE servicios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    descripcion VARCHAR(1000) NOT NULL,
    categoria VARCHAR(100) NOT NULL,
    precio DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    formato_entrega ENUM('imagen', 'pdf', 'video') NOT NULL,
    tiempo_entrega VARCHAR(100) NOT NULL,
    imagen_referencia VARCHAR(255) NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_servicios_activo (activo),
    KEY idx_servicios_categoria (categoria),
    KEY idx_servicios_precio (precio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tarjetas_prueba (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titular VARCHAR(150) NOT NULL,
    numero_tarjeta VARCHAR(25) NOT NULL,
    fecha_expiracion VARCHAR(5) NOT NULL,
    cvv VARCHAR(4) NOT NULL,
    saldo_disponible DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    activa TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_tarjetas_prueba_numero_tarjeta (numero_tarjeta),
    KEY idx_tarjetas_prueba_activa (activa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pagos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    tarjeta_prueba_id INT NULL,
    metodo_pago VARCHAR(50) NOT NULL,
    monto_pago DECIMAL(10,2) NOT NULL,
    estado_pago ENUM(
        'pendiente',
        'confirmado',
        'rechazado',
        'reembolsado'
    ) NOT NULL DEFAULT 'pendiente',
    resultado_transaccion ENUM(
        'pendiente',
        'aprobado',
        'rechazado',
        'saldo_insuficiente',
        'tarjeta_invalida',
        'tarjeta_inactiva',
        'error'
    ) NULL,
    mensaje_transaccion VARCHAR(255) NULL,
    referencia_pago VARCHAR(120) NULL,
    fecha_pago DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_pagos_pedido
        FOREIGN KEY (pedido_id) REFERENCES pedidos (id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_pagos_tarjeta_prueba
        FOREIGN KEY (tarjeta_prueba_id) REFERENCES tarjetas_prueba (id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    KEY idx_pagos_pedido_id (pedido_id),
    KEY idx_pagos_tarjeta_prueba_id (tarjeta_prueba_id),
    KEY idx_pagos_estado_pago (estado_pago),
    KEY idx_pagos_resultado_transaccion (resultado_transaccion),
    KEY idx_pagos_fecha_pago (fecha_pago)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE entregas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    formato_entrega VARCHAR(50) NOT NULL,
    archivo_final VARCHAR(255) NOT NULL,
    fecha_entrega DATETIME NOT NULL,
    notas_entrega TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_entregas_pedido
        FOREIGN KEY (pedido_id) REFERENCES pedidos (id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    KEY idx_entregas_pedido_id (pedido_id),
    KEY idx_entregas_fecha_entrega (fecha_entrega)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mensajes_pedido (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    tipo_usuario ENUM('cliente', 'admin') NOT NULL,
    mensaje TEXT NOT NULL,
    archivo_adjunto VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mensajes_pedido_pedido
        FOREIGN KEY (pedido_id) REFERENCES pedidos (id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    KEY idx_mensajes_pedido_pedido_id (pedido_id),
    KEY idx_mensajes_pedido_tipo_usuario (tipo_usuario),
    KEY idx_mensajes_pedido_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE actividad_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_tipo ENUM('cliente', 'admin', 'sistema') NOT NULL,
    usuario_id INT NULL,
    accion VARCHAR(100) NOT NULL,
    modulo VARCHAR(100) NOT NULL,
    referencia_id INT NULL,
    descripcion VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_actividad_log_usuario (usuario_tipo, usuario_id),
    KEY idx_actividad_log_modulo (modulo),
    KEY idx_actividad_log_referencia_id (referencia_id),
    KEY idx_actividad_log_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
