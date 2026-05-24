# Spec 0009 — Admin Dashboard

## Objetivo

Implementar el dashboard administrativo principal de InvitaStudio.

Esta spec deberá crear:

- Layout administrativo.
- Navegación interna.
- Dashboard visual.
- Resumen de pedidos.
- KPIs básicos.
- Acceso protegido.
- Estructura reusable para panel admin.

El dashboard será el centro operativo principal del sistema.

---

# Dependencias

Esta spec depende de:

- 0005_public_website_layout
- 0008_admin_authentication

---

# Ruta del proyecto

```txt
C:\Mayingo\Proyectos\InvitaStudio
```

---

# Objetivo funcional

El administrador deberá poder:

1. Acceder al panel.
2. Visualizar resumen general.
3. Navegar módulos.
4. Ver métricas básicas.
5. Ver actividad reciente.

---

# Ruta principal requerida

```txt
admin/index.php
```

---

# Protección requerida

## Obligatorio

Todas las páginas admin deben usar:

```php
requireAdminAuth()
```

---

# Layout requerido

## Estructura

```txt
Sidebar
Topbar
Main content
Cards
Responsive layout
```

---

# Sidebar requerido

## Debe contener

- Dashboard
- Pedidos
- Pagos
- Entregas
- Mensajes
- Configuración
- Logout

---

# Topbar requerida

## Debe mostrar

- Nombre admin.
- Rol.
- Logout.
- Breadcrumb placeholder.

---

# Dashboard principal

## Debe mostrar KPIs

### Total pedidos

---

### Pedidos pendientes

---

### Pedidos en proceso

---

### Pedidos entregados

---

### Pagos pendientes

---

# Widgets requeridos

## Actividad reciente

Mostrar:

- Últimos pedidos.
- Últimos pagos.
- Últimas entregas.

---

## Pedidos recientes

Tabla simple con:

```txt
Número pedido
Cliente
Estado
Fecha
```

---

# Diseño visual requerido

## Estilo

- Minimalista.
- Profesional.
- Moderno.
- Limpio.

---

# Responsive requerido

## Compatibilidad

- Desktop
- Tablet
- Mobile

---

# Sidebar mobile

Debe permitir:

- Toggle.
- Overlay.
- Cierre responsive.

---

# JavaScript requerido

## admin.js

Debe manejar:

- Sidebar toggle.
- Responsive menu.
- Dropdowns básicos.
- UI helpers.

---

# CSS requerido

## Archivos sugeridos

```txt
public/assets/css/admin.css
public/assets/css/admin_layout.css
```

---

# Reutilización UI

## IMPORTANTE

Mantener coherencia visual con:

```txt
0005_public_website_layout
```

---

# API requerida

## Endpoint dashboard stats

```txt
GET /api/admin/dashboard_stats.php
```

---

# Endpoint recent activity

```txt
GET /api/admin/recent_activity.php
```

---

# Datos esperados

## dashboard_stats

Debe retornar:

```json
{
  "success": true,
  "data": {
    "total_orders": 0,
    "pending_orders": 0,
    "processing_orders": 0,
    "completed_orders": 0,
    "pending_payments": 0
  }
}
```

---

# Actividad reciente

Debe retornar:

- Pedidos recientes.
- Pagos recientes.
- Entregas recientes.

---

# Base de datos requerida

Usar:

```txt
pedidos
pagos
entregas
actividad_log
```

---

# Seguridad requerida

## Obligatorio

- requireAdminAuth()
- Validación sesión
- Prepared statements
- Validación backend

---

# Restricciones

## NO hacer

- No gráficos complejos.
- No charts libraries.
- No frameworks admin.
- No Bootstrap Admin.
- No Tailwind Admin.

---

# Objetivo V1

Prioridad:

- Navegación clara.
- Dashboard funcional.
- KPIs básicos.
- Responsive.

---

# UX requerida

## Dashboard

Debe sentirse:

- Rápido.
- Organizado.
- Profesional.
- Fácil navegar.

---

# Loading states

## Requeridos

Mientras carga info:

- Skeletons simples.
- Loading placeholders.
- Estados vacíos claros.

---

# Estados vacíos

## Mostrar mensajes amigables

Ejemplo:

```txt
No existen pedidos todavía
```

---

# Accesibilidad mínima

## Obligatorio

- Navegación clara.
- Contraste adecuado.
- Botones visibles.
- Focus visible.

---

# Validaciones

La implementación será válida si:

- Dashboard carga.
- Auth funciona.
- Sidebar funciona.
- Responsive funciona.
- KPIs cargan.
- Recent activity funciona.

---

# Archivos mínimos esperados

## Admin

```txt
admin/index.php
```

---

## API

```txt
api/admin/dashboard_stats.php
api/admin/recent_activity.php
```

---

## JS

```txt
public/assets/js/admin.js
```

---

## CSS

```txt
public/assets/css/admin.css
```

---

# Prompt sugerido para Codex

```txt
Implementa el spec:
docs/specs/0009_admin_dashboard.md

Respeta:
- AGENTS.md
- docs/project/coding_rules.md
- docs/project/api_rules.md
- docs/project/architecture.md

Ruta:
C:\Mayingo\Proyectos\InvitaStudio

Objetivo:
Crear el dashboard administrativo principal de InvitaStudio.

IMPORTANTE:
- Usar requireAdminAuth()
- Mantener diseño minimalista
- Responsive obligatorio

Restricciones:
- NO Bootstrap Admin
- NO Tailwind
- NO charts libraries
- NO frameworks JS

Entrega:
1. Plan breve
2. Archivos modificados
3. Implementación
4. Comandos manuales
5. Verificación DoD
```
