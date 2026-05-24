# Spec 0016 — Basic Notification System

## Objetivo

Implementar un sistema básico de notificaciones y correos automáticos para InvitaStudio.

Esta spec permitirá:

- Enviar correos básicos.
- Confirmar registro de pedidos.
- Confirmar pagos.
- Notificar entregas.
- Notificar mensajes/aclaraciones.

El objetivo es mejorar la experiencia del cliente y cumplir con los requerimientos del documento escolar relacionados con confirmaciones y seguimiento.

La implementación deberá quedar preparada para futura migración al servidor final de producción.

---

# Dependencias

Esta spec depende de:

- 0007_order_registration_flow
- 0011_payment_validation_flow
- 0013_delivery_upload_system
- 0014_client_clarification_messages

---

# Ruta del proyecto

```txt
C:\Mayingo\Proyectos\InvitaStudio
```

---

# Objetivo funcional

El sistema deberá enviar notificaciones cuando:

1. Se registra un pedido.
2. Se confirma un pago.
3. Se entrega una invitación.
4. Existe un nuevo mensaje/aclaración.

---

# Tipo de notificaciones

## V1 Escolar

Solo:

```txt
Correos electrónicos básicos
```

---

# Arquitectura requerida

## IMPORTANTE

Toda la configuración SMTP deberá centralizarse en:

```txt
config/mail.php
```

Los módulos del sistema NO deberán contener credenciales hardcodeadas.

---

# Flujo requerido

```txt
Módulo sistema
→ mail_service.php
→ config/mail.php
→ SMTP
```

---

# Tecnologías permitidas

## Permitido

- PHPMailer (RECOMENDADO)
- SMTP estándar
- Gmail SMTP
- Mailtrap

---

# Tecnologías NO recomendadas

## Evitar

```txt
mail() nativo PHP
```

porque puede fallar en entornos locales.

---

# Restricciones

## NO hacer

- No Firebase.
- No OneSignal.
- No SMS.
- No WhatsApp API.
- No push notifications.
- No colas complejas.

---

# Configuración requerida

## config/mail.php

Debe contener:

```txt
MAIL_HOST
MAIL_PORT
MAIL_USERNAME
MAIL_PASSWORD
MAIL_FROM
MAIL_FROM_NAME
MAIL_ENCRYPTION
```

---

# Ejemplo esperado

```php
<?php

return [
    'driver' => 'smtp',

    'host' => 'smtp.gmail.com',
    'port' => 587,

    'username' => 'invitastudio.demo@gmail.com',
    'password' => 'password_aqui',

    'from_email' => 'invitastudio.demo@gmail.com',
    'from_name' => 'InvitaStudio',

    'encryption' => 'tls',
];
```

---

# Compatibilidad futura requerida

## IMPORTANTE

La arquitectura debe permitir cambiar fácilmente a:

```txt
Servidor SMTP de DirectInc
```

sin modificar:

- endpoints
- lógica negocio
- templates
- módulos sistema

Solo deberá requerirse modificar:

```txt
config/mail.php
```

---

# Servicio requerido

## Archivo sugerido

```txt
api/shared/mail_service.php
```

---

# Funciones requeridas

## sendOrderConfirmation()

Enviar cuando:

```txt
Pedido registrado
```

---

## sendPaymentConfirmation()

Enviar cuando:

```txt
Pago aprobado
```

---

## sendDeliveryNotification()

Enviar cuando:

```txt
Pedido entregado
```

---

## sendClarificationNotification()

Enviar cuando:

```txt
Nuevo mensaje
```

---

# Correos requeridos

## Confirmación pedido

Debe incluir:

```txt
Número pedido
Fecha evento
Estado inicial
Información básica
```

---

# Confirmación pago

Debe incluir:

```txt
Número pedido
Monto
Estado confirmado
```

---

# Entrega final

Debe incluir:

```txt
Número pedido
Mensaje entrega
Instrucciones descarga
```

---

# Aclaraciones

Debe incluir:

```txt
Nuevo mensaje asociado pedido
```

---

# Plantillas requeridas

## Crear plantillas HTML simples

```txt
templates/emails/
```

---

# Archivos sugeridos

```txt
templates/emails/order_confirmation.php
templates/emails/payment_confirmation.php
templates/emails/delivery_notification.php
templates/emails/clarification_notification.php
```

---

# Diseño correos

## Estilo

- Limpio.
- Minimalista.
- Profesional.
- Compatible email.

---

# Reglas HTML email

## IMPORTANTE

Usar:

- Tablas simples.
- CSS inline básico.
- Diseño compatible Gmail/Outlook.

---

# Integraciones requeridas

## order_registration_flow

Enviar:

```txt
Confirmación pedido
```

---

## payment_validation_flow

Enviar:

```txt
Confirmación pago
```

---

## delivery_upload_system

Enviar:

```txt
Entrega final
```

---

## clarification_messages

Enviar:

```txt
Nueva aclaración
```

---

# Manejo errores

## IMPORTANTE

Si falla correo:

- NO romper flujo principal.
- Registrar error.
- Continuar operación.

---

# Activity log

Registrar:

- Correo enviado.
- Error envío.
- Tipo notificación.

---

# Seguridad básica

## Obligatorio

- Validar correos.
- Escape HTML.
- Sanitización.
- No exponer passwords SMTP.

---

# Configuración escolar

## Permitido

Para proyecto escolar:

- Mailtrap
- SMTP Gmail pruebas
- SMTP local

---

# Configuración futura producción

## Compatible con:

- SMTP DirectAdmin
- SMTP DirectInc
- SMTP hosting tradicional

---

# Responsive requerido

## Correos

Compatibles con:

- Desktop
- Mobile

---

# UX requerida

## Cliente

Debe sentir:

- Confirmación clara.
- Seguimiento profesional.
- Información organizada.

---

# Restricciones adicionales

## NO hacer

- No queue system.
- No workers.
- No cron complejo.
- No templates avanzados.

---

# Objetivo escolar

La prioridad es demostrar:

- Automatización básica.
- Confirmaciones.
- Integración módulos.
- Flujo completo sistema.

Pero dejando preparada la arquitectura para producción futura.

---

# Validaciones de aceptación

La implementación será válida si:

- Se envía correo pedido.
- Se envía correo pago.
- Se envía correo entrega.
- Se envía correo aclaraciones.
- El flujo principal no se rompe si falla mail.
- Las plantillas funcionan.
- Toda configuración SMTP está centralizada.
- Cambiar SMTP solo requiere modificar config/mail.php.

---

# Archivos mínimos esperados

## Config

```txt
config/mail.php
```

---

## Shared

```txt
api/shared/mail_service.php
```

---

## Templates

```txt
templates/emails/order_confirmation.php
templates/emails/payment_confirmation.php
templates/emails/delivery_notification.php
templates/emails/clarification_notification.php
```

---

# Prompt sugerido para Codex

```txt
Implementa el spec:
docs/specs/0016_basic_notification_system.md

Respeta:
- AGENTS.md
- docs/project/api_rules.md
- docs/project/coding_rules.md

Ruta:
C:\Mayingo\Proyectos\InvitaStudio

Objetivo:
Implementar sistema básico de correos automáticos para InvitaStudio.

IMPORTANTE:
- Proyecto escolar/local
- Usar PHPMailer recomendado
- Centralizar SMTP en config/mail.php
- Preparar arquitectura para SMTP futuro en DirectInc
- Si falla correo NO romper flujo

Restricciones:
- NO Firebase
- NO WhatsApp API
- NO push notifications
- NO colas complejas
- NO frameworks

Entrega:
1. Plan breve
2. Archivos modificados
3. Implementación
4. Comandos manuales
5. Verificación DoD
```
