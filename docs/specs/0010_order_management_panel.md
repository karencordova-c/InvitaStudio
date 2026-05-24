# Spec 0010 — Order Management Panel

## Objetivo

Implementar el módulo administrativo completo de gestión de pedidos de InvitaStudio.

Esta spec deberá permitir al administrador:

- Visualizar pedidos.
- Filtrar pedidos.
- Consultar detalles.
- Actualizar estados.
- Revisar información del cliente.
- Gestionar flujo operativo.

Este módulo será el núcleo operativo principal del sistema.

---

# Dependencias

Esta spec depende de:

- 0007_order_registration_flow
- 0008_admin_authentication
- 0009_admin_dashboard

---

# Ruta del proyecto

```txt
C:\Mayingo\Proyectos\InvitaStudio
```

---

# Objetivo funcional

El administrador deberá poder:

1. Ver lista pedidos.
2. Buscar pedidos.
3. Filtrar estados.
4. Abrir detalle.
5. Actualizar estado.
6. Ver información cliente.
7. Ver información evento.

---

# Rutas requeridas

## Frontend admin

```txt
admin/orders/index.php
admin/orders/details.php
```

---

## API

```txt
api/orders/list.php
api/orders/details.php
api/orders/update_status.php
```

---

# Protección requerida

## Obligatorio

Todas las rutas admin deben usar:

```php
requireAdminAuth()
```

---

# Lista de pedidos requerida

## Tabla principal

Debe mostrar:

```txt
Número pedido
Cliente
Evento
Fecha evento
Estado pedido
Estado pago
Fecha creación
Acciones
```

---

# Funciones requeridas

## Buscar pedidos

Permitir búsqueda por:

- Número pedido.
- Nombre cliente.
- Correo.

---

## Filtrar pedidos

Filtrar por:

```txt
pendiente
pago_confirmado
en_proceso
terminado
entregado
cancelado
```

---

## Ordenamiento

Permitir ordenar por:

- Más recientes.
- Fecha evento.
- Estado.

---

# Paginación requerida

## Obligatorio

Implementar paginación simple.

---

# Cantidad sugerida

```txt
10-20 pedidos por página
```

---

# Detalle de pedido

## details.php

Debe mostrar:

### Información cliente

```txt
Nombre
Correo
Teléfono
Medio contacto
```

---

### Información evento

```txt
Tipo evento
Nombre evento
Fecha
Hora
Ubicación
Temática
Colores
Estilo
```

---

### Información pago

```txt
Estado pago
Resultado transacción
Método pago
```

---

### Información entrega

```txt
Formato entrega
Estado pedido
```

---

# Cambio de estado requerido

## update_status.php

Debe permitir:

```txt
pendiente
pago_confirmado
en_proceso
terminado
entregado
cancelado
```

---

# Validaciones backend

## Obligatorio

Validar:

- Estado válido.
- Pedido existente.
- Sesión admin.
- Datos correctos.

---

# Activity log

## Registrar

Cada cambio de estado.

Usar:

```txt
actividad_log
```

---

# UX requerida

## Lista pedidos

Debe sentirse:

- Clara.
- Rápida.
- Profesional.
- Fácil consultar.

---

# Estados visuales

## Mostrar badges visuales

### Pendiente
amarillo

### Pago confirmado
azul

### En proceso
morado

### Terminado
verde claro

### Entregado
verde

### Cancelado
rojo

---

# Responsive requerido

## Compatibilidad

- Desktop
- Tablet
- Mobile

---

# Mobile behavior

## Requerido

- Tabla responsive.
- Cards mobile si necesario.
- Sidebar responsive.

---

# API requerida

## list.php

Debe permitir:

- Filtros.
- Búsqueda.
- Paginación.

---

# Response ejemplo

```json
{
  "success": true,
  "data": {
    "orders": [],
    "pagination": {}
  }
}
```

---

# details.php

Debe retornar:

- Cliente.
- Pedido.
- Pago.
- Entrega.
- Información completa.

---

# update_status.php

Debe:

- Validar estado.
- Actualizar pedido.
- Registrar actividad.

---

# Seguridad requerida

## Obligatorio

- Prepared statements.
- Validaciones backend.
- Auth middleware.
- Sanitización.

---

# Restricciones

## NO hacer

- No tiempo real.
- No sockets.
- No frameworks admin.
- No DataTables externas.
- No complejidad innecesaria.

---

# Objetivo V1

Prioridad:

- Gestión clara.
- Estados funcionales.
- Flujo operativo estable.

---

# Loading states

## Requeridos

- Loading tabla.
- Loading detalle.
- Loading update status.

---

# Estados vacíos

Mostrar mensajes claros:

```txt
No hay pedidos registrados
```

---

# Accesibilidad mínima

## Obligatorio

- Focus visible.
- Contraste correcto.
- Botones claros.
- Navegación clara.

---

# Validaciones

La implementación será válida si:

- Lista pedidos funciona.
- Filtros funcionan.
- Detalle funciona.
- Estados actualizan.
- Activity log registra.
- Responsive funciona.

---

# Archivos mínimos esperados

## Admin

```txt
admin/orders/index.php
admin/orders/details.php
```

---

## API

```txt
api/orders/list.php
api/orders/details.php
api/orders/update_status.php
```

---

## JS

```txt
public/assets/js/admin_orders.js
```

---

# Prompt sugerido para Codex

```txt
Implementa el spec:
docs/specs/0010_order_management_panel.md

Respeta:
- AGENTS.md
- docs/project/api_rules.md
- docs/project/database_rules.md
- docs/project/coding_rules.md

Ruta:
C:\Mayingo\Proyectos\InvitaStudio

Objetivo:
Implementar el módulo administrativo completo de gestión de pedidos.

IMPORTANTE:
- Usar requireAdminAuth()
- Registrar activity_log
- Responsive obligatorio

Restricciones:
- NO frameworks admin
- NO DataTables externas
- NO sockets
- NO tiempo real

Entrega:
1. Plan breve
2. Archivos modificados
3. Implementación
4. Comandos manuales
5. Verificación DoD
```
