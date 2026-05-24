# Spec 0007 — Order Registration Flow

## Objetivo

Implementar el flujo backend completo para registrar pedidos dentro de InvitaStudio.

Esta spec deberá conectar:

- Formulario frontend.
- API backend.
- Base de datos.
- Generación de pedidos.
- Estados iniciales.
- Registro de cliente.
- Registro de pago inicial.

Esta spec representa el primer flujo funcional completo del sistema.

---

# Dependencias

Esta spec depende de:

- 0002_local_database_schema
- 0003_simulated_payment_cards
- 0004_php_api_foundation
- 0006_invitation_request_form

---

# Ruta del proyecto

```txt
C:\Mayingo\Proyectos\InvitaStudio
```

---

# Objetivo funcional

El sistema deberá:

1. Recibir formulario.
2. Validar datos.
3. Registrar cliente.
4. Registrar pedido.
5. Generar número pedido.
6. Crear pago pendiente.
7. Retornar respuesta correcta.

---

# Endpoint requerido

## POST

```txt
/api/orders/create.php
```

---

# Flujo esperado

```txt
Frontend request.html
→ request_form.js
→ POST create.php
→ Validación backend
→ Registro cliente
→ Registro pedido
→ Registro pago pendiente
→ JSON response
```

---

# Reglas de cliente

## Cliente existente

Si el correo ya existe:

- Reutilizar cliente.
- NO crear duplicado.

---

## Cliente nuevo

Si no existe:

- Crear cliente.
- Registrar timestamps.

---

# Generación de número de pedido

Formato:

```txt
INV-2026-000001
```

Debe ser:

- Único.
- Incremental.
- Reutilizable.

---

# Estado inicial requerido

## Pedido

```txt
pendiente
```

---

## Pago

```txt
pendiente
```

---

# Validaciones backend obligatorias

## Validar

- Campos requeridos.
- Email válido.
- Fecha válida.
- Hora válida.
- Longitudes.
- Strings vacíos.
- Formatos inválidos.

---

# Reglas SQL

## Obligatorio

Usar:

```txt
PDO
prepared statements
transactions
```

---

# Transacciones requeridas

El flujo deberá usar:

```sql
BEGIN
COMMIT
ROLLBACK
```

---

# Operaciones transaccionales

## Deben ser atómicas

- Crear cliente.
- Crear pedido.
- Crear pago.

Si algo falla:

- rollback completo.

---

# Tabla clientes

## Registrar

```txt
nombre
correo
telefono
medio_contacto
```

---

# Tabla pedidos

## Registrar

```txt
cliente_id
numero_pedido
tipo_evento
nombre_evento
fecha_evento
hora_evento
ubicacion_evento
tematica
colores
estilo_diseno
informacion_adicional
estado_pedido
```

---

# Tabla pagos

## Registrar automáticamente

```txt
pedido_id
estado_pago = pendiente
resultado_transaccion = pendiente
```

---

# Respuesta éxito requerida

## JSON

```json
{
  "success": true,
  "message": "Solicitud registrada correctamente",
  "data": {
    "numero_pedido": "INV-2026-000001"
  }
}
```

---

# Respuesta error requerida

## JSON

```json
{
  "success": false,
  "message": "Error al registrar pedido"
}
```

---

# Manejo de errores

## Obligatorio

- try/catch
- rollback
- logs básicos
- respuestas JSON

---

# Seguridad

## Obligatorio

- Sanitización.
- Prepared statements.
- Validación backend.
- Escape correcto.

---

# Logs básicos

Registrar errores importantes.

NO exponer SQL al usuario.

---

# UX requerida

## Frontend

Al éxito:

- Mostrar confirmación.
- Mostrar número pedido.
- Limpiar formulario.

---

## Error

Mostrar mensaje amigable.

---

# Estados loading

## Requerido

Mientras se envía:

- Deshabilitar botón.
- Mostrar loading.
- Evitar doble submit.

---

# Estructura backend sugerida

```txt
api/orders/create.php
api/shared/validation.php
api/shared/helpers.php
api/shared/response.php
```

---

# Restricciones

## NO hacer

- No pagos reales.
- No autenticación cliente.
- No envío correo todavía.
- No archivos todavía.
- No frameworks.

---

# Objetivo V1

Prioridad:

- Flujo funcional estable.
- Registro correcto.
- Integridad datos.
- Validaciones sólidas.

---

# Validaciones

La implementación será válida si:

- El pedido se registra.
- El cliente se registra.
- El pago pendiente se crea.
- El número pedido es único.
- Las transacciones funcionan.
- Rollback funciona.
- JSON responses funcionan.

---

# Archivos mínimos esperados

## API

```txt
api/orders/create.php
```

---

## JS

```txt
public/assets/js/request_form.js
```

Actualizado para flujo real.

---

# Prompt sugerido para Codex

```txt
Implementa el spec:
docs/specs/0007_order_registration_flow.md

Respeta:
- AGENTS.md
- docs/project/api_rules.md
- docs/project/database_rules.md
- docs/project/coding_rules.md

Ruta:
C:\Mayingo\Proyectos\InvitaStudio

Objetivo:
Implementar el flujo backend completo de registro de pedidos.

IMPORTANTE:
- Usar PDO
- Usar transactions
- Usar prepared statements

Restricciones:
- NO frameworks
- NO pagos reales
- NO autenticación cliente

Entrega:
1. Plan breve
2. Archivos modificados
3. Implementación
4. Comandos manuales
5. Verificación DoD
```
