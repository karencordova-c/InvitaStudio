# Spec 0014 — Client Clarification Messages

## Objetivo

Implementar el sistema de mensajes y aclaraciones entre administrador y cliente dentro de InvitaStudio.

Esta spec permitirá:

- Solicitar aclaraciones.
- Enviar mensajes.
- Asociar mensajes a pedidos.
- Mantener historial.
- Mostrar comunicación organizada.

Este módulo ayudará a resolver información incompleta o cambios necesarios en el pedido.

---

# Dependencias

Esta spec depende de:

- 0010_order_management_panel
- 0012_order_status_lookup
- 0013_delivery_upload_system

---

# Ruta del proyecto

```txt
C:\Mayingo\Proyectos\InvitaStudio
```

---

# Objetivo funcional

El sistema deberá permitir:

1. Administrador envía mensaje.
2. Cliente responde.
3. Mensajes se almacenan.
4. Historial permanece asociado pedido.
5. Pedido puede continuar flujo.

---

# Tabla requerida

## mensajes_pedido

Usar tabla creada en:

```txt
0002_local_database_schema
```

---

# Rutas requeridas

## Admin

```txt
admin/messages/index.php
admin/messages/details.php
```

---

## API

```txt
api/messages/create.php
api/messages/list.php
```

---

# Frontend cliente

## status.html

Debe permitir:

- Ver mensajes.
- Responder mensajes.
- Historial conversación.

---

# Flujo esperado

```txt
Admin
→ Crea mensaje
→ Asociado pedido
→ Cliente consulta estado
→ Cliente responde
→ Historial actualizado
```

---

# Tipos usuario requeridos

## tipo_usuario

Valores:

```txt
admin
cliente
```

---

# Información requerida

## Mensajes

Registrar:

```txt
pedido_id
tipo_usuario
mensaje
archivo_adjunto opcional
created_at
```

---

# Reglas mensajes

## Obligatorio

- Asociar SIEMPRE pedido.
- Mantener historial.
- Orden cronológico.
- No eliminar mensajes.

---

# UI requerida admin

## Debe mostrar

- Lista conversaciones.
- Estado pedido.
- Último mensaje.
- Fecha.
- Acceso detalle.

---

# UI requerida cliente

## status.html

Debe mostrar:

- Timeline mensajes.
- Diferenciar admin/cliente.
- Formulario respuesta.

---

# Diseño visual requerido

## Mensajes admin

Alineación derecha.

---

## Mensajes cliente

Alineación izquierda.

---

# Estados pedidos

## IMPORTANTE

El admin podrá mantener pedido:

```txt
pendiente
```

hasta recibir aclaración.

---

# Validaciones backend

## Validar

- Pedido existente.
- Mensaje requerido.
- Usuario válido.
- Longitud mensaje.

---

# Longitud sugerida

## mensaje

```txt
5-2000 caracteres
```

---

# Adjuntos opcionales

## Permitidos

```txt
jpg
jpeg
png
pdf
```

---

# Uploads adjuntos

## Ruta sugerida

```txt
uploads/messages/
```

---

# Seguridad uploads

## Obligatorio

- Validar MIME.
- Validar extensión.
- Renombrar archivo.
- Evitar ejecución.

---

# API response requerida

## Éxito

```json
{
  "success": true,
  "message": "Mensaje enviado"
}
```

---

## Error

```json
{
  "success": false,
  "message": "Error al enviar mensaje"
}
```

---

# UX requerida

## Conversación

Debe sentirse:

- Clara.
- Ordenada.
- Moderna.
- Similar chat simple.

---

# Loading states

## Requeridos

Mientras envía:

- Disable botón.
- Spinner.
- Evitar doble submit.

---

# Auto refresh opcional

## Permitido

Polling simple cada cierto tiempo.

---

# PROHIBIDO

- WebSockets.
- Tiempo real complejo.
- Socket.IO.

---

# Historial requerido

## Debe conservar

Toda conversación asociada pedido.

---

# Seguridad requerida

## Obligatorio

- Prepared statements.
- Validaciones backend.
- Sanitización.
- Escape HTML.

---

# Activity log

## Registrar

- Mensaje enviado admin.
- Mensaje enviado cliente.
- Adjuntos enviados.

---

# Responsive requerido

## Compatibilidad

- Desktop
- Tablet
- Mobile

---

# Mobile behavior

## Requerido

- Mensajes responsive.
- Inputs adaptables.
- Scroll correcto.

---

# Restricciones

## NO hacer

- No sockets.
- No tiempo real complejo.
- No sistema chat enterprise.
- No emojis complejos.
- No notificaciones push.

---

# Objetivo V1

Prioridad:

- Comunicación simple.
- Historial estable.
- UX clara.
- Integración pedidos.

---

# Validaciones

La implementación será válida si:

- Mensajes funcionan.
- Historial funciona.
- Cliente responde.
- Admin responde.
- Adjuntos funcionan.
- Responsive funciona.

---

# Archivos mínimos esperados

## Admin

```txt
admin/messages/index.php
admin/messages/details.php
```

---

## API

```txt
api/messages/create.php
api/messages/list.php
```

---

## JS

```txt
public/assets/js/messages.js
```

---

# Prompt sugerido para Codex

```txt
Implementa el spec:
docs/specs/0014_client_clarification_messages.md

Respeta:
- AGENTS.md
- docs/project/api_rules.md
- docs/project/database_rules.md
- docs/project/coding_rules.md

Ruta:
C:\Mayingo\Proyectos\InvitaStudio

Objetivo:
Implementar el sistema de mensajes y aclaraciones entre cliente y administrador.

IMPORTANTE:
- Mantener historial
- Asociar mensajes a pedidos
- Upload seguro obligatorio

Restricciones:
- NO sockets
- NO tiempo real complejo
- NO frameworks
- NO chat enterprise

Entrega:
1. Plan breve
2. Archivos modificados
3. Implementación
4. Comandos manuales
5. Verificación DoD
```
