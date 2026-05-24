# Spec 0008 — Admin Authentication

## Objetivo

Implementar el sistema inicial de autenticación administrativa para InvitaStudio.

Esta spec deberá permitir:

- Login administrativo.
- Validación de credenciales.
- Manejo de sesión.
- Protección básica de rutas.
- Logout.
- Middleware simple de autenticación.

IMPORTANTE:

Esta autenticación será únicamente para administradores.

NO existe login de clientes en V1.

---

# Dependencias

Esta spec depende de:

- 0002_local_database_schema
- 0004_php_api_foundation
- 0007_order_registration_flow

---

# Ruta del proyecto

```txt
C:\Mayingo\Proyectos\InvitaStudio
```

---

# Objetivo funcional

El sistema deberá permitir:

1. Mostrar login admin.
2. Validar credenciales.
3. Crear sesión PHP.
4. Restringir acceso.
5. Cerrar sesión.

---

# Arquitectura requerida

## Frontend

```txt
admin/login.php
```

---

## Backend

```txt
api/auth/login.php
api/auth/logout.php
api/shared/auth.php
```

---

# Tabla requerida

## usuarios_admin

Usar tabla creada en:

```txt
0002_local_database_schema
```

---

# Login requerido

## Campos

```txt
Correo
Contraseña
```

---

# Validaciones requeridas

## Backend

Validar:

- Email válido.
- Password requerido.
- Usuario existente.
- Usuario activo.

---

# Passwords

## Obligatorio

Usar:

```php
password_hash()
password_verify()
```

---

# PROHIBIDO

- Guardar passwords texto plano.
- Comparar passwords manualmente.

---

# Sesión requerida

## Usar

```php
$_SESSION
```

---

# Variables sesión sugeridas

```txt
admin_id
admin_nombre
admin_correo
admin_rol
admin_logged_in
```

---

# Protección de rutas

## auth.php

Debe incluir helper:

```txt
requireAdminAuth()
```

---

# Comportamiento esperado

## Si NO autenticado

- Redireccionar login.
- O responder 401 JSON.

---

## Si autenticado

- Permitir acceso.

---

# Login exitoso

## Debe

- Crear sesión.
- Actualizar ultimo_login.
- Registrar actividad.
- Retornar éxito.

---

# Login fallido

## Debe

- Retornar error genérico.
- NO revelar si correo existe.
- NO revelar información sensible.

---

# Logout

## Debe

- Destruir sesión.
- Limpiar variables.
- Redireccionar login.

---

# Frontend requerido

## admin/login.php

Debe contener:

- Formulario elegante.
- Inputs.
- Loading states.
- Errores visuales.
- Responsive.

---

# UX requerida

## Loading state

Mientras login:

- Deshabilitar botón.
- Mostrar loading.
- Evitar doble submit.

---

# Seguridad requerida

## Obligatorio

- session_regenerate_id()
- password_verify()
- Sanitización
- Validaciones backend

---

# Restricciones importantes

## NO hacer

- No JWT.
- No OAuth.
- No Google login.
- No autenticación compleja.
- No remember me.
- No MFA.

---

# Objetivo V1

Prioridad:

- Seguridad básica sólida.
- Simplicidad.
- Flujo estable.

---

# Middleware requerido

## auth.php

Debe permitir:

```txt
requireAdminAuth()
isAdminLoggedIn()
getAdminUser()
```

---

# Respuestas API

## Éxito

```json
{
  "success": true,
  "message": "Login exitoso"
}
```

---

## Error

```json
{
  "success": false,
  "message": "Credenciales inválidas"
}
```

---

# Activity log

Registrar:

- Login exitoso.
- Login fallido.
- Logout.

Usar:

```txt
actividad_log
```

---

# Manejo errores

## Obligatorio

- try/catch
- JSON responses
- Logs básicos

---

# Reglas UI

## Visual

Mantener estilo definido en:

```txt
0005_public_website_layout
```

---

# Responsive requerido

## Compatibilidad

- Mobile
- Tablet
- Desktop

---

# Validaciones

La implementación será válida si:

- Login funciona.
- Password hash funciona.
- Sesión funciona.
- Logout funciona.
- Rutas protegidas funcionan.
- Middleware auth funciona.

---

# Archivos mínimos esperados

## Frontend

```txt
admin/login.php
```

---

## API

```txt
api/auth/login.php
api/auth/logout.php
```

---

## Shared

```txt
api/shared/auth.php
```

---

# Prompt sugerido para Codex

```txt
Implementa el spec:
docs/specs/0008_admin_authentication.md

Respeta:
- AGENTS.md
- docs/project/api_rules.md
- docs/project/coding_rules.md
- docs/project/database_rules.md

Ruta:
C:\Mayingo\Proyectos\InvitaStudio

Objetivo:
Implementar autenticación administrativa básica usando sesiones PHP.

IMPORTANTE:
- Usar password_hash
- Usar password_verify
- Usar sesiones PHP

Restricciones:
- NO JWT
- NO OAuth
- NO frameworks
- NO login social

Entrega:
1. Plan breve
2. Archivos modificados
3. Implementación
4. Comandos manuales
5. Verificación DoD
```
