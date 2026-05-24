# Spec 0006 — Invitation Request Form

## Objetivo

Implementar el formulario principal de solicitud de invitaciones digitales de InvitaStudio.

Esta spec deberá construir:

- Formulario visual completo.
- Validaciones frontend.
- Integración inicial con API.
- Estructura de datos del pedido.
- UX amigable.
- Flujo inicial de solicitud.

IMPORTANTE:

Esta spec cubre el formulario y captura de datos.

El procesamiento completo del pedido se realizará en specs posteriores.

---

# Dependencias

Esta spec depende de:

- 0004_php_api_foundation
- 0005_public_website_layout

---

# Ruta del proyecto

```txt
C:\Mayingo\Proyectos\InvitaStudio
```

---

# Página principal

## request.html

Esta página deberá contener el formulario completo.

---

# Objetivo UX

El formulario deberá sentirse:

- Claro.
- Moderno.
- Fácil.
- Guiado.
- Profesional.
- Mobile friendly.

---

# Secciones requeridas

## 1. Información personal

Campos:

```txt
Nombre completo
Correo electrónico
Teléfono
Medio preferido de contacto
```

---

## 2. Información del evento

Campos:

```txt
Tipo de evento
Nombre del evento/festejado
Fecha del evento
Hora del evento
Ubicación
```

---

## 3. Preferencias visuales

Campos:

```txt
Temática
Colores deseados
Estilo visual
```

---

## 4. Información adicional

Campo:

```txt
Detalles adicionales
```

Textarea.

---

## 5. Tipo de entrega

Opciones:

```txt
Imagen
PDF
Video
```

---

## 6. Selección de servicio

Debe permitir seleccionar:

- Tipo de invitación.
- Categoría.
- Precio placeholder.

---

# Campos requeridos

## Obligatorios

```txt
Nombre
Correo
Teléfono
Tipo evento
Fecha evento
Hora evento
Ubicación
Estilo visual
```

---

# Validaciones frontend

## Correo

Validar formato email.

---

## Teléfono

Validar longitud mínima.

---

## Fecha

No permitir fechas inválidas.

---

## Hora

Formato válido.

---

## Strings

Validar longitudes mínimas y máximas.

---

# Validaciones visuales

## Mostrar:

- Errores inline.
- Inputs inválidos.
- Mensajes claros.
- Estados de carga.

---

# UX requerida

## Loading state

Al enviar:

- Deshabilitar botón.
- Mostrar loading.
- Evitar doble submit.

---

# JavaScript requerido

## request_form.js

Debe contener:

- Validaciones.
- Serialización.
- Submit.
- Manejo errores.
- UI feedback.

---

# API requerida

## Endpoint placeholder

```txt
POST /api/orders/create.php
```

---

# Payload esperado

```json
{
  "nombre": "",
  "correo": "",
  "telefono": "",
  "medio_contacto": "",
  "tipo_evento": "",
  "nombre_evento": "",
  "fecha_evento": "",
  "hora_evento": "",
  "ubicacion_evento": "",
  "tematica": "",
  "colores": "",
  "estilo_diseno": "",
  "informacion_adicional": "",
  "formato_entrega": "",
  "servicio_id": ""
}
```

---

# Respuesta esperada

## Éxito

```json
{
  "success": true,
  "message": "Solicitud registrada"
}
```

---

## Error

```json
{
  "success": false,
  "message": "Error de validación"
}
```

---

# Componentes visuales requeridos

## Inputs

- Modernos.
- Consistentes.
- Responsive.

---

## Selects

Estilo consistente con UI.

---

## Textareas

Responsive y claros.

---

## Buttons

Usar componentes definidos en spec 0005.

---

# Responsive requerido

## Compatibilidad

- Mobile
- Tablet
- Desktop

---

# Accesibilidad mínima

## Obligatorio

- Labels.
- Placeholders útiles.
- Inputs accesibles.
- Focus visible.

---

# Seguridad mínima

## Frontend

- Sanitización básica.
- Validación previa.
- Escape de strings.

---

# Restricciones

## NO hacer

- No pagos reales.
- No subir archivos todavía.
- No autenticación.
- No frameworks JS.
- No React/Vue.

---

# Objetivo V1

Prioridad:

- UX clara.
- Captura correcta.
- Validaciones sólidas.
- Responsive.

---

# Validaciones

La implementación será válida si:

- El formulario funciona.
- Validaciones frontend funcionan.
- Se genera payload correcto.
- request_form.js funciona.
- Mobile funciona.
- No hay doble submit.

---

# Archivos mínimos esperados

## HTML

```txt
public/request.html
```

---

## JS

```txt
public/assets/js/request_form.js
```

---

## API

```txt
api/orders/create.php
```

---

# Prompt sugerido para Codex

```txt
Implementa el spec:
docs/specs/0006_invitation_request_form.md

Respeta:
- AGENTS.md
- docs/project/api_rules.md
- docs/project/coding_rules.md
- docs/project/architecture.md

Ruta:
C:\Mayingo\Proyectos\InvitaStudio

Objetivo:
Implementar el formulario principal de solicitud de invitaciones.

Restricciones:
- NO frameworks JS
- NO React
- NO Vue
- NO Angular
- NO pagos reales

Entrega:
1. Plan breve
2. Archivos modificados
3. Implementación
4. Comandos manuales
5. Verificación DoD
```
