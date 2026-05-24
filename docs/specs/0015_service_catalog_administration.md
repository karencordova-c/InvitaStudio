# Spec 0015 — Service Catalog Administration

## Objetivo

Implementar el módulo de administración del catálogo de servicios de InvitaStudio.

Esta spec permitirá cumplir con el requisito del documento SRS relacionado con la visualización de servicios, precios y ejemplos de diseños.

El sistema deberá permitir que el administrador gestione los servicios que aparecen en el sitio público.

---

# Dependencias

Esta spec depende de:

- 0002_local_database_schema
- 0005_public_website_layout
- 0008_admin_authentication
- 0009_admin_dashboard

---

# Ruta del proyecto

```txt
C:\Mayingo\Proyectos\InvitaStudio
```

---

# Justificación escolar

El documento SRS indica que el sistema debe mostrar:

- Tipos de invitaciones disponibles.
- Precios.
- Ejemplos de diseños.
- Formatos disponibles.
- Tiempos estimados de entrega.

Por lo tanto, esta spec implementa un catálogo editable desde el panel administrativo.

---

# Objetivo funcional

El administrador deberá poder:

1. Crear servicios.
2. Editar servicios.
3. Activar/desactivar servicios.
4. Registrar precio.
5. Registrar descripción.
6. Registrar formato disponible.
7. Registrar tiempo estimado de entrega.
8. Asociar imagen de ejemplo.
9. Mostrar servicios activos en la página pública.

---

# Cambios de base de datos

## Nueva tabla requerida

```txt
servicios
```

---

# Campos requeridos

```txt
id
nombre
descripcion
categoria
precio
formato_entrega
tiempo_entrega
imagen_referencia
activo
created_at
updated_at
```

---

# Definición de campos

## nombre

Nombre del servicio.

Ejemplo:

```txt
Invitación digital básica
```

---

## descripcion

Descripción breve del servicio.

---

## categoria

Tipo o categoría del servicio.

Ejemplos:

```txt
Boda
XV años
Cumpleaños
Bautizo
Graduación
Evento general
```

---

## precio

Precio del servicio.

Tipo sugerido:

```sql
DECIMAL(10,2)
```

---

## formato_entrega

Formato disponible.

Valores sugeridos:

```txt
Imagen
PDF
Video
```

---

## tiempo_entrega

Texto descriptivo.

Ejemplo:

```txt
2 a 3 días hábiles
```

---

## imagen_referencia

Ruta del archivo o imagen placeholder.

---

## activo

Permite mostrar u ocultar servicios.

---

# Archivos SQL

Actualizar:

```txt
database/schema.sql
database/seed.sql
```

---

# Seed requerido

Agregar servicios de prueba.

Mínimo:

1. Invitación digital básica.
2. Invitación digital premium.
3. Invitación animada/video.
4. Invitación para XV años.
5. Invitación para boda.

---

# Rutas admin requeridas

```txt
admin/services/index.php
admin/services/create.php
admin/services/edit.php
```

---

# API requerida

```txt
api/services/list.php
api/services/create.php
api/services/details.php
api/services/update.php
api/services/toggle_active.php
```

---

# Página pública afectada

## services.html

Debe mostrar servicios activos desde la base de datos.

---

# JavaScript requerido

```txt
public/assets/js/services.js
public/assets/js/admin_services.js
```

---

# Funcionalidad pública

El usuario deberá poder ver:

- Nombre del servicio.
- Descripción.
- Categoría.
- Precio.
- Formato de entrega.
- Tiempo estimado.
- Imagen o placeholder.

---

# Funcionalidad admin

## Lista de servicios

Debe mostrar:

```txt
Nombre
Categoría
Precio
Formato
Activo
Acciones
```

---

## Crear servicio

Formulario con:

```txt
Nombre
Descripción
Categoría
Precio
Formato de entrega
Tiempo de entrega
Imagen referencia opcional
Activo
```

---

## Editar servicio

Debe permitir modificar los mismos campos.

---

## Activar/desactivar

El admin podrá ocultar servicios sin eliminarlos.

---

# Validaciones backend

Validar:

- Nombre requerido.
- Descripción requerida.
- Categoría requerida.
- Precio numérico.
- Precio mayor o igual a 0.
- Formato válido.
- Tiempo de entrega requerido.
- Estado activo válido.

---

# Validaciones frontend

Mostrar mensajes claros si:

- Faltan campos.
- Precio no es válido.
- Formato no es válido.

---

# Upload de imagen referencia

## Para V1 escolar

La imagen puede ser opcional.

Se permite usar:

- Placeholder visual.
- Ruta manual.
- Upload simple opcional.

Si se implementa upload, validar:

```txt
jpg
jpeg
png
webp
```

---

# Seguridad básica

Obligatorio:

- Prepared statements.
- Validaciones backend.
- Sanitización.
- requireAdminAuth() en rutas admin.
- No eliminar servicios físicamente; usar activo.

---

# Activity log

Registrar:

- Servicio creado.
- Servicio editado.
- Servicio activado/desactivado.

---

# UX requerida

## Admin

Debe sentirse:

- Claro.
- Simple.
- Fácil de llenar.
- Escolarmente demostrable.

---

## Público

Debe verse:

- Moderno.
- Ordenado.
- Atractivo.
- Responsive.

---

# Responsive requerido

Compatibilidad:

- Desktop
- Tablet
- Mobile

---

# Restricciones

## NO hacer

- No carrito de compras.
- No inventario.
- No promociones avanzadas.
- No cupones.
- No categorías administrables separadas todavía.
- No frameworks.

---

# Objetivo escolar

La prioridad es demostrar que el sistema permite gestionar y mostrar los servicios ofrecidos.

No se busca un catálogo comercial complejo.

---

# Validaciones de aceptación

La implementación será válida si:

- El admin puede crear servicios.
- El admin puede editar servicios.
- El admin puede activar/desactivar servicios.
- services.html muestra servicios activos.
- Los precios se muestran correctamente.
- El sistema usa base de datos.
- El diseño es responsive.
- Se registra activity_log.

---

# Archivos mínimos esperados

## Admin

```txt
admin/services/index.php
admin/services/create.php
admin/services/edit.php
```

---

## API

```txt
api/services/list.php
api/services/create.php
api/services/details.php
api/services/update.php
api/services/toggle_active.php
```

---

## Public

```txt
public/services.html
```

---

## JS

```txt
public/assets/js/services.js
public/assets/js/admin_services.js
```

---

## Database

```txt
database/schema.sql
database/seed.sql
```

---

# Prompt sugerido para Codex

```txt
Implementa el spec:
docs/specs/0015_service_catalog_administration.md

Respeta:
- AGENTS.md
- docs/project/api_rules.md
- docs/project/database_rules.md
- docs/project/coding_rules.md
- docs/project/architecture.md

Ruta:
C:\Mayingo\Proyectos\InvitaStudio

Objetivo:
Implementar el módulo de administración y visualización pública del catálogo de servicios.

IMPORTANTE:
- Proyecto escolar/local
- Usar base de datos local
- Usar requireAdminAuth() en admin
- Mantener diseño responsive
- No eliminar servicios físicamente, usar campo activo

Restricciones:
- NO frameworks
- NO carrito de compras
- NO cupones
- NO promociones avanzadas

Entrega:
1. Plan breve
2. Archivos modificados
3. Implementación
4. Comandos manuales
5. Verificación DoD
```
