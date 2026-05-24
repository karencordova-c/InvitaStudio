# Spec 0003 — Simulated Payment Cards

## Objetivo

Implementar la infraestructura inicial para pagos simulados mediante tarjetas de prueba dentro de InvitaStudio.

Esta spec permitirá:

- Simular pagos con tarjeta.
- Validar tarjetas registradas localmente.
- Aprobar o rechazar transacciones.
- Simular saldo disponible.
- Registrar resultados de transacciones.

IMPORTANTE:

Este sistema es únicamente para entorno académico/local.

NO debe utilizar tarjetas reales.

---

# Dependencias

Esta spec depende de:

- 0001_project_bootstrap
- 0002_local_database_schema

---

# Ruta del proyecto

```txt
C:\Mayingo\Proyectos\InvitaStudio
```

---

# Objetivo funcional

El sistema deberá permitir:

1. Registrar tarjetas de prueba.
2. Simular pagos locales.
3. Validar saldo.
4. Validar tarjeta activa.
5. Registrar pagos aprobados o rechazados.

---

# Restricciones importantes

## PROHIBIDO

- Usar tarjetas reales.
- Integrar Stripe.
- Integrar PayPal.
- Integrar MercadoPago.
- Guardar información bancaria real.
- Procesar pagos reales.

---

# Cambios requeridos

## Modificar

```txt
database/schema.sql
database/seed.sql
```

---

# Nueva tabla requerida

## tarjetas_prueba

Esta tabla almacenará tarjetas simuladas.

---

# Campos requeridos

```txt
id
titular
numero_tarjeta
fecha_expiracion
cvv
saldo_disponible
activa
created_at
updated_at
```

---

# Definición esperada

## titular

Nombre ficticio del titular.

Tipo:

```sql
VARCHAR(150)
```

---

## numero_tarjeta

Número simulado de tarjeta.

Tipo:

```sql
VARCHAR(25)
```

Debe ser UNIQUE.

---

## fecha_expiracion

Formato:

```txt
MM/YY
```

Tipo:

```sql
VARCHAR(5)
```

---

## cvv

Código simulado.

Tipo:

```sql
VARCHAR(4)
```

---

## saldo_disponible

Saldo virtual.

Tipo:

```sql
DECIMAL(10,2)
```

---

## activa

Indica si la tarjeta puede usarse.

Tipo:

```sql
BOOLEAN
```

---

# Cambios requeridos en tabla pagos

Agregar:

```txt
tarjeta_prueba_id
resultado_transaccion
mensaje_transaccion
```

---

# Relación requerida

## pagos → tarjetas_prueba

```txt
pagos.tarjeta_prueba_id
→ tarjetas_prueba.id
```

---

# Estados de transacción

## resultado_transaccion

Valores válidos:

```txt
aprobado
rechazado
saldo_insuficiente
tarjeta_invalida
tarjeta_inactiva
error
```

---

# Reglas funcionales

## Pago aprobado

El pago deberá aprobarse si:

- La tarjeta existe.
- La tarjeta está activa.
- El CVV coincide.
- La fecha coincide.
- El saldo es suficiente.

---

## Pago rechazado

El pago deberá rechazarse si:

- La tarjeta no existe.
- El CVV es incorrecto.
- La tarjeta está inactiva.
- El saldo es insuficiente.

---

# Comportamiento esperado

## Pago aprobado

El sistema deberá:

1. Descontar saldo.
2. Registrar pago.
3. Marcar pago confirmado.
4. Actualizar pedido.

---

## Pago rechazado

El sistema deberá:

1. Registrar intento.
2. Mantener pedido pendiente.
3. Registrar motivo del rechazo.

---

# Datos seed requeridos

## seed.sql

Debe incluir mínimo:

- 3 tarjetas válidas.
- 1 tarjeta inactiva.
- 1 tarjeta con saldo insuficiente.

---

# Tarjetas ejemplo

## Tarjeta válida

```txt
Número: 4111111111111111
CVV: 123
Exp: 12/30
Saldo: 5000
```

---

## Tarjeta saldo insuficiente

```txt
Número: 4000000000000002
CVV: 222
Exp: 12/30
Saldo: 50
```

---

## Tarjeta inactiva

```txt
Número: 4000000000000003
CVV: 333
Exp: 12/30
Activa: false
```

---

# Seguridad

## Obligatorio

- Aclarar que son tarjetas ficticias.
- NO usar información bancaria real.
- NO usar APIs reales.
- NO almacenar datos sensibles reales.

---

# Objetivo académico

La finalidad de esta spec es:

- Simular flujo de pagos.
- Practicar validaciones.
- Practicar lógica backend.
- Simular estados de transacción.

---

# Restricciones técnicas

## NO hacer

- No usar tokenización.
- No usar pasarelas reales.
- No usar PCI compliance real.
- No implementar cifrado bancario avanzado.

---

# Validaciones

La implementación será válida si:

- La tabla tarjetas_prueba existe.
- pagos tiene relación correcta.
- seed.sql crea tarjetas válidas.
- Existen tarjetas aprobadas y rechazadas.
- El saldo puede modificarse.
- Los estados de transacción funcionan.

---

# Prompt sugerido para Codex

```txt
Implementa el spec:
docs/specs/0003_simulated_payment_cards.md

Respeta:
- AGENTS.md
- docs/project/database_rules.md
- docs/project/api_rules.md

Ruta:
C:\Mayingo\Proyectos\InvitaStudio

Objetivo:
Agregar soporte para pagos simulados usando tarjetas de prueba locales.

IMPORTANTE:
- NO usar APIs reales
- NO usar Stripe
- NO usar PayPal
- NO usar tarjetas reales

Entrega:
1. Plan breve
2. Archivos modificados
3. Implementación
4. Comandos manuales
5. Verificación DoD
```
