# Database Rules — InvitaStudio

## Motor de base de datos

- MariaDB
- MySQL

Compatible con Laragon.

---

## Convenciones

### Tablas
- snake_case
- nombres en plural

Ejemplo:
```sql
clientes
pedidos
pagos
mensajes_pedido
```

### Columnas
- snake_case
- descriptivas
- sin abreviaciones innecesarias

---

## Reglas de IDs

Todas las tablas deben tener:

```sql
id INT AUTO_INCREMENT PRIMARY KEY
```

---

## Fechas

### Campos recomendados
```sql
created_at DATETIME
updated_at DATETIME
```

---

## Relaciones

Usar claves foráneas cuando aplique.

Ejemplo:

```sql
pedido_id
cliente_id
```

---

## Tablas principales V1

### clientes
Información de clientes.

### pedidos
Información principal del pedido.

### pagos
Estados y registros de pago.

### mensajes_pedido
Aclaraciones y comunicación.

### entregas
Archivos finales entregados.

### usuarios_admin
Acceso administrativo.

### actividad_log
Registro básico de acciones.

---

## Estados oficiales

### Estado pedido
- pendiente
- pago_confirmado
- en_proceso
- terminado
- entregado
- cancelado

### Estado pago
- pendiente
- confirmado
- rechazado
- reembolsado

---

## Reglas importantes

- NO guardar contraseñas sin hash.
- NO guardar datos bancarios sensibles.
- Validar longitudes de texto.
- Usar UTF8MB4.
- Evitar NULL innecesarios.
- Mantener integridad relacional.

---

## Archivos SQL

Todos los scripts SQL deben ir dentro de:

```txt
database/
```

Ejemplo:

```txt
database/schema.sql
database/seed.sql
```

---

## Estrategia V1

Primera versión:
- Simple.
- Clara.
- Fácil de modificar.

No optimizar prematuramente.
