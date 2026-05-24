# Spec 0002 — Local Database Schema

## Objetivo

Diseñar e implementar el esquema completo inicial de base de datos para InvitaStudio utilizando MySQL/MariaDB compatible con Laragon.

La base de datos deberá cubrir:

- Clientes
- Pedidos
- Pagos
- Entregas
- Mensajes
- Usuarios administrativos
- Logs básicos

La implementación debe respetar:

- AGENTS.md
- docs/project/database_rules.md
- docs/project/architecture.md

---

# Ruta del proyecto

```txt
C:\Mayingo\Proyectos\InvitaStudio
```

---

# Archivo principal esperado

```txt
database/schema.sql
```

---

# Objetivos de la base de datos

La base de datos deberá:

- Ser simple.
- Ser mantenible.
- Ser escalable.
- Mantener integridad relacional.
- Permitir futuras expansiones.
- Ser clara para futuras migraciones.

---

# Requisitos generales

## Motor

Compatible con:

- MySQL 8+
- MariaDB 10+

---

## Charset

Toda la base deberá usar:

```sql
utf8mb4
utf8mb4_unicode_ci
```

---

# Tablas requeridas

## 1. clientes

Información del cliente.

### Campos mínimos

```txt
id
nombre
correo
telefono
medio_contacto
created_at
updated_at
```

---

## 2. pedidos

Información principal del pedido.

### Campos mínimos

```txt
id
cliente_id
numero_pedido
tipo_evento
nombre_evento
fecha_evento
hora_evento
ubicacion_evento
estilo_diseno
colores
tematica
informacion_adicional
estado_pedido
created_at
updated_at
```

---

## 3. pagos

Información de pagos.

### Campos mínimos

```txt
id
pedido_id
metodo_pago
monto_pago
estado_pago
referencia_pago
fecha_pago
created_at
updated_at
```

---

## 4. entregas

Archivos finales entregados.

### Campos mínimos

```txt
id
pedido_id
formato_entrega
archivo_final
fecha_entrega
notas_entrega
created_at
updated_at
```

---

## 5. mensajes_pedido

Mensajes y aclaraciones.

### Campos mínimos

```txt
id
pedido_id
tipo_usuario
mensaje
archivo_adjunto
created_at
```

---

## 6. usuarios_admin

Usuarios administrativos.

### Campos mínimos

```txt
id
nombre
correo
password_hash
rol
activo
ultimo_login
created_at
updated_at
```

---

## 7. actividad_log

Bitácora básica.

### Campos mínimos

```txt
id
usuario_tipo
usuario_id
accion
modulo
referencia_id
descripcion
ip_address
created_at
```

---

# Relaciones requeridas

## pedidos → clientes

```txt
pedidos.cliente_id
→ clientes.id
```

---

## pagos → pedidos

```txt
pagos.pedido_id
→ pedidos.id
```

---

## entregas → pedidos

```txt
entregas.pedido_id
→ pedidos.id
```

---

## mensajes_pedido → pedidos

```txt
mensajes_pedido.pedido_id
→ pedidos.id
```

---

# Estados oficiales

## Estado pedido

Usar ENUM o validación equivalente.

Valores permitidos:

```txt
pendiente
pago_confirmado
en_proceso
terminado
entregado
cancelado
```

---

## Estado pago

Valores permitidos:

```txt
pendiente
confirmado
rechazado
reembolsado
```

---

# Reglas SQL

## IDs

Todas las tablas deben usar:

```sql
INT AUTO_INCREMENT PRIMARY KEY
```

---

## Fechas

Usar:

```sql
DATETIME
```

---

## Soft conventions

Agregar:

```sql
created_at
updated_at
```

en todas las tablas relevantes.

---

# Índices requeridos

## pedidos

Crear índices para:

```txt
numero_pedido
estado_pedido
fecha_evento
```

---

## clientes

Crear índices para:

```txt
correo
telefono
```

---

## pagos

Crear índices para:

```txt
estado_pago
fecha_pago
```

---

# Constraints

## numero_pedido

Debe ser UNIQUE.

---

## correo clientes

Debe ser UNIQUE.

---

## correo admin

Debe ser UNIQUE.

---

# Seed inicial

Crear:

```txt
database/seed.sql
```

Debe incluir:

- 1 usuario administrador.
- Estados de ejemplo.
- Datos mínimos para pruebas.

---

# Usuario administrador inicial

## Requisitos

Crear usuario:

```txt
admin@invitastudio.local
```

Password temporal:

```txt
admin123
```

IMPORTANTE:

La contraseña debe guardarse con hash PHP.

NO guardar texto plano.

---

# Convenciones

## Naming

### Tablas
snake_case

### Columnas
snake_case

### Foreign keys

Ejemplo:

```txt
cliente_id
pedido_id
```

---

# Seguridad

## Obligatorio

- Usar foreign keys.
- NO guardar passwords en texto plano.
- Validar tamaños razonables.
- Usar tipos SQL correctos.

---

# Restricciones

## NO hacer

- No usar ORM.
- No usar migraciones automáticas.
- No usar UUIDs.
- No agregar tablas innecesarias.
- No agregar complejidad prematura.

---

# Validaciones

La implementación será válida si:

- schema.sql ejecuta correctamente.
- seed.sql ejecuta correctamente.
- Todas las relaciones funcionan.
- Las foreign keys funcionan.
- Los ENUM oficiales existen.
- Los índices existen.

---

# Prompt sugerido para Codex

```txt
Implementa el spec:
docs/specs/0002_local_database_schema.md

Respeta:
- AGENTS.md
- docs/project/database_rules.md
- docs/project/architecture.md

Ruta:
C:\Mayingo\Proyectos\InvitaStudio

Objetivo:
Crear schema.sql y seed.sql completos para InvitaStudio usando MySQL/MariaDB compatible con Laragon.

Restricciones:
- NO ORM
- NO frameworks
- NO migraciones automáticas
- NO UUIDs

Entrega:
1. Plan breve
2. Archivos modificados
3. Implementación
4. Comandos manuales
5. Verificación DoD
```
