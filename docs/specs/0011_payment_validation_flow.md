# Spec 0011 — Payment Validation Flow

## Objetivo

Implementar el flujo completo de validación y procesamiento de pagos simulados dentro de InvitaStudio.

Esta spec deberá permitir:

- Procesar pagos simulados.
- Validar tarjetas de prueba.
- Aprobar/rechazar pagos.
- Actualizar estados.
- Registrar transacciones.
- Integrar frontend y backend.

Este módulo conectará finalmente:

- Pedidos
- Pagos
- Tarjetas de prueba
- Estados del sistema

---

# Dependencias

Esta spec depende de:

- 0003_simulated_payment_cards
- 0007_order_registration_flow
- 0010_order_management_panel

---

# Ruta del proyecto

```txt
C:\Mayingo\Proyectos\InvitaStudio
```

---

# Objetivo funcional

El sistema deberá:

1. Recibir datos tarjeta.
2. Validar tarjeta prueba.
3. Validar saldo.
4. Aprobar o rechazar.
5. Actualizar pago.
6. Actualizar pedido.
7. Registrar actividad.

---

# Flujo esperado

```txt
Cliente
→ Payment form
→ API payments/process.php
→ Validación tarjeta
→ Validación saldo
→ Actualización DB
→ Respuesta frontend
```

---

# Endpoint requerido

## POST

```txt
/api/payments/process.php
```

---

# Payload esperado

```json
{
  "pedido_id": 1,
  "numero_tarjeta": "",
  "titular": "",
  "fecha_expiracion": "",
  "cvv": ""
}
```

---

# Tabla requerida

## tarjetas_prueba

Usar tabla creada en:

```txt
0003_simulated_payment_cards
```

---

# Validaciones obligatorias

## Tarjeta

Validar:

- Existe.
- Activa.
- CVV coincide.
- Fecha coincide.

---

## Saldo

Validar:

- saldo_disponible >= monto_pago

---

# Pago aprobado

## Debe

1. Descontar saldo.
2. Actualizar pago.
3. Actualizar pedido.
4. Registrar actividad.
5. Retornar éxito.

---

# Pago rechazado

## Debe

1. Mantener pedido pendiente.
2. Registrar motivo.
3. Registrar intento.
4. Retornar error.

---

# Estados requeridos

## Pago aprobado

### pagos.estado_pago

```txt
confirmado
```

---

### pagos.resultado_transaccion

```txt
aprobado
```

---

### pedidos.estado_pedido

```txt
pago_confirmado
```

---

# Estados rechazo

## saldo insuficiente

```txt
saldo_insuficiente
```

---

## tarjeta inválida

```txt
tarjeta_invalida
```

---

## tarjeta inactiva

```txt
tarjeta_inactiva
```

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

## Atómico

El flujo completo debe usar:

```sql
BEGIN
COMMIT
ROLLBACK
```

---

# Actualizaciones DB requeridas

## pagos

Actualizar:

```txt
estado_pago
resultado_transaccion
mensaje_transaccion
fecha_pago
tarjeta_prueba_id
```

---

## pedidos

Actualizar:

```txt
estado_pedido
```

---

## tarjetas_prueba

Actualizar:

```txt
saldo_disponible
```

---

# Activity log

## Registrar

- Pago aprobado.
- Pago rechazado.
- Motivo rechazo.

---

# Frontend requerido

## payment_form.js

Debe manejar:

- Validaciones frontend.
- Loading state.
- Submit API.
- Errores visuales.
- Resultado transacción.

---

# Página requerida

## payment.html

Debe incluir:

- Resumen pedido.
- Total.
- Formulario tarjeta.
- Estados visuales.

---

# Inputs requeridos

## Tarjeta

```txt
Número tarjeta
Titular
Expiración
CVV
```

---

# Validaciones frontend

## Número tarjeta

- Longitud.
- Solo números.

---

## CVV

- Longitud válida.
- Solo números.

---

## Expiración

Formato:

```txt
MM/YY
```

---

# UX requerida

## Loading state

Mientras procesa:

- Deshabilitar submit.
- Mostrar loading.
- Evitar doble submit.

---

# Resultado visual

## Aprobado

Mostrar:

- Confirmación.
- Estado.
- Número pedido.

---

## Rechazado

Mostrar:

- Motivo claro.
- Intentar nuevamente.

---

# Seguridad requerida

## Obligatorio

- Prepared statements.
- Validaciones backend.
- Sanitización.
- Transacciones.

---

# Restricciones importantes

## PROHIBIDO

- No APIs reales.
- No Stripe.
- No PayPal.
- No MercadoPago.
- No tarjetas reales.
- No PCI real.

---

# Objetivo académico

La finalidad es:

- Simular flujo real.
- Practicar backend.
- Practicar estados.
- Practicar transacciones.

---

# Responsive requerido

## Compatibilidad

- Desktop
- Tablet
- Mobile

---

# Validaciones

La implementación será válida si:

- Los pagos funcionan.
- El saldo se descuenta.
- Los estados cambian.
- Rollback funciona.
- Activity log funciona.
- Frontend funciona.

---

# Archivos mínimos esperados

## Frontend

```txt
public/payment.html
```

---

## JS

```txt
public/assets/js/payment_form.js
```

---

## API

```txt
api/payments/process.php
```

---

# Prompt sugerido para Codex

```txt
Implementa el spec:
docs/specs/0011_payment_validation_flow.md

Respeta:
- AGENTS.md
- docs/project/api_rules.md
- docs/project/database_rules.md
- docs/project/coding_rules.md

Ruta:
C:\Mayingo\Proyectos\InvitaStudio

Objetivo:
Implementar el flujo completo de pagos simulados.

IMPORTANTE:
- SOLO tarjetas de prueba
- SOLO pagos simulados
- Usar transactions

Restricciones:
- NO Stripe
- NO PayPal
- NO pagos reales
- NO frameworks

Entrega:
1. Plan breve
2. Archivos modificados
3. Implementación
4. Comandos manuales
5. Verificación DoD
```
