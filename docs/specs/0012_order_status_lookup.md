# Spec 0012 — Order Status Lookup

## Objetivo

Implementar el sistema público de consulta de estado de pedidos de InvitaStudio.

Esta spec permitirá que los clientes puedan:

- Consultar su pedido.
- Revisar estado actual.
- Ver progreso del servicio.
- Confirmar pagos.
- Consultar entrega.

Sin necesidad de autenticación.

---

# Dependencias

Esta spec depende de:

- 0007_order_registration_flow
- 0010_order_management_panel
- 0011_payment_validation_flow

---

# Ruta del proyecto

```txt
C:\Mayingo\Proyectos\InvitaStudio
```

---

# Objetivo funcional

El cliente deberá poder:

1. Ingresar número pedido.
2. Validar correo.
3. Consultar estado.
4. Ver información básica.
5. Revisar progreso.

---

# Página requerida

## status.html

Debe contener:

- Formulario búsqueda.
- Estado visual.
- Timeline progreso.
- Información pedido.

---

# API requerida

## POST

```txt
/api/status/lookup.php
```

---

# Payload esperado

```json
{
  "numero_pedido": "",
  "correo": ""
}
```

---

# Validaciones requeridas

## Validar

- Número pedido requerido.
- Correo válido.
- Pedido existente.
- Relación correo/pedido.

---

# Seguridad requerida

## IMPORTANTE

NO permitir:

- Consultar pedidos ajenos.
- Enumeración pedidos.
- Exposición datos sensibles.

---

# Flujo esperado

```txt
Cliente
→ status.html
→ lookup.php
→ Validación pedido
→ Validación correo
→ Consulta DB
→ Resultado visual
```

---

# Información visible permitida

## Cliente

Mostrar:

```txt
Número pedido
Estado pedido
Estado pago
Fecha evento
Formato entrega
```

---

# Información NO permitida

## NO mostrar

- Datos admin.
- Logs internos.
- IDs internos.
- Información sensible.

---

# Estados visuales requeridos

## Estados pedido

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

# Timeline requerido

## Debe mostrar progreso

```txt
Solicitud recibida
Pago confirmado
En proceso
Terminado
Entregado
```

---

# Estado actual

Debe resaltarse visualmente.

---

# Información adicional

## Mostrar opcionalmente

- Mensaje estado.
- Tiempo estimado.
- Última actualización.

---

# Entrega final

## Si pedido entregado

Mostrar:

- Archivo disponible.
- Link descarga.
- Confirmación entrega.

---

# Restricciones entrega

## IMPORTANTE

El archivo solo debe mostrarse si:

```txt
estado_pedido = entregado
```

---

# API response requerida

## Éxito

```json
{
  "success": true,
  "data": {
    "numero_pedido": "",
    "estado_pedido": "",
    "estado_pago": "",
    "fecha_evento": "",
    "formato_entrega": ""
  }
}
```

---

# Error requerido

## Pedido no encontrado

```json
{
  "success": false,
  "message": "Pedido no encontrado"
}
```

---

# UX requerida

## Consulta

Debe sentirse:

- Clara.
- Segura.
- Profesional.
- Fácil entender.

---

# Loading states

## Requeridos

Mientras consulta:

- Spinner.
- Placeholder.
- Botón disabled.

---

# Estados vacíos

## Mostrar mensajes claros

Ejemplo:

```txt
No se encontró el pedido
```

---

# JavaScript requerido

## status_lookup.js

Debe manejar:

- Validaciones frontend.
- Submit.
- Loading states.
- Render resultado.
- Render timeline.

---

# Responsive requerido

## Compatibilidad

- Desktop
- Tablet
- Mobile

---

# Diseño requerido

## Mantener coherencia con:

```txt
0005_public_website_layout
```

---

# Seguridad backend

## Obligatorio

- Prepared statements.
- Validación correo.
- Validación pedido.
- Sanitización.
- Rate limit simple opcional.

---

# Restricciones

## NO hacer

- No login cliente.
- No datos sensibles.
- No realtime.
- No sockets.
- No polling complejo.

---

# Objetivo V1

Prioridad:

- Consulta simple.
- Información clara.
- Seguridad básica.
- UX limpia.

---

# Validaciones

La implementación será válida si:

- Consulta funciona.
- Validación correo funciona.
- Timeline funciona.
- Entrega solo aparece cuando corresponde.
- Responsive funciona.
- Seguridad básica funciona.

---

# Archivos mínimos esperados

## Frontend

```txt
public/status.html
```

---

## JS

```txt
public/assets/js/status_lookup.js
```

---

## API

```txt
api/status/lookup.php
```

---

# Prompt sugerido para Codex

```txt
Implementa el spec:
docs/specs/0012_order_status_lookup.md

Respeta:
- AGENTS.md
- docs/project/api_rules.md
- docs/project/database_rules.md
- docs/project/coding_rules.md

Ruta:
C:\Mayingo\Proyectos\InvitaStudio

Objetivo:
Implementar el sistema público de consulta de estado de pedidos.

IMPORTANTE:
- NO login cliente
- Validar correo + pedido
- Seguridad básica obligatoria

Restricciones:
- NO realtime
- NO sockets
- NO frameworks
- NO polling complejo

Entrega:
1. Plan breve
2. Archivos modificados
3. Implementación
4. Comandos manuales
5. Verificación DoD
```
