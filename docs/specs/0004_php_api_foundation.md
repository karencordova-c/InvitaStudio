# Spec 0004 — PHP API Foundation

## Objetivo

Implementar la base arquitectónica de la API PHP de InvitaStudio.

La finalidad de esta spec es crear:

- Estructura API modular.
- Configuración compartida.
- Helpers reutilizables.
- Respuestas JSON estandarizadas.
- Manejo base de errores.
- Conexión reutilizable PDO.
- Sistema base de routing por endpoints.

Esta spec NO implementa lógica de negocio completa.

---

# Dependencias

Esta spec depende de:

- 0001_project_bootstrap
- 0002_local_database_schema
- 0003_simulated_payment_cards

---

# Ruta del proyecto

```txt
C:\Mayingo\Proyectos\InvitaStudio
```

---

# Objetivos técnicos

La API deberá:

- Ser modular.
- Ser simple.
- Ser mantenible.
- Ser consistente.
- Ser compatible con hosting PHP tradicional.

---

# Estructura esperada

```txt
api/
│
├── shared/
│   ├── response.php
│   ├── validation.php
│   ├── auth.php
│   ├── helpers.php
│   └── middleware.php
│
├── orders/
├── payments/
├── deliveries/
├── messages/
├── status/
└── auth/
```

---

# Configuración requerida

## config/database.php

Debe:

- Crear conexión PDO.
- Configurar UTF8MB4.
- Manejar errores PDO.
- Centralizar conexión.

---

## config/app.php

Debe contener:

```txt
APP_NAME
APP_ENV
BASE_URL
UPLOADS_PATH
TIMEZONE
```

---

# Helpers requeridos

## response.php

Debe contener helpers para:

```txt
successResponse()
errorResponse()
validationErrorResponse()
```

---

# Formato oficial JSON

## Respuesta éxito

```json
{
  "success": true,
  "message": "Operación exitosa",
  "data": {}
}
```

---

## Respuesta error

```json
{
  "success": false,
  "message": "Error",
  "errors": []
}
```

---

# Validation helper

## validation.php

Debe permitir:

- Validar requeridos.
- Validar email.
- Validar longitud.
- Validar números.
- Validar enums.

---

# Middleware básico

## middleware.php

Debe permitir:

- Validar método HTTP.
- Validar JSON.
- Validar headers.
- Centralizar validaciones futuras.

---

# Helpers generales

## helpers.php

Debe incluir:

- Generador número pedido.
- Sanitización básica.
- Helpers fecha.
- Helpers string.

---

# Generador de número de pedido

Formato esperado:

```txt
INV-2026-000001
```

Debe ser reutilizable.

---

# Reglas API

## Todos los endpoints

Deben:

- Retornar JSON.
- Configurar content-type.
- Usar UTF8.
- Validar método HTTP.

---

# Reglas de seguridad

## Obligatorio

- Prepared statements.
- Sanitización.
- Validaciones.
- Manejo controlado de errores.

---

# Manejo de errores

## NO permitido

```php
die();
var_dump();
print_r();
echo directo de errores SQL;
```

---

# Manejo esperado

Usar:

```txt
try/catch
JSON responses
HTTP codes
```

---

# HTTP Codes

## Éxito

```txt
200
201
```

---

## Cliente

```txt
400
401
403
404
422
```

---

## Servidor

```txt
500
```

---

# Timezone

Configurar:

```txt
America/Chihuahua
```

o equivalente definido en app.php.

---

# Endpoint placeholder requerido

## Crear endpoint inicial

```txt
api/status/health.php
```

Debe responder:

```json
{
  "success": true,
  "message": "API funcionando"
}
```

---

# Endpoint de prueba DB

## Crear endpoint

```txt
api/status/database.php
```

Debe:

- Intentar conexión PDO.
- Retornar estado correcto.

---

# Requisitos de arquitectura

## NO hacer

- No usar frameworks.
- No usar Laravel.
- No usar Symfony.
- No usar Composer.
- No usar rutas complejas.
- No usar MVC avanzado.

---

# Objetivo V1

Prioridad:

- Estabilidad.
- Claridad.
- Modularidad simple.

NO prioridad:

- Arquitectura enterprise.
- Microservicios.
- Containers.
- Dependency injection avanzada.

---

# Validaciones

La implementación será válida si:

- La conexión PDO funciona.
- health.php responde correctamente.
- database.php responde correctamente.
- Los helpers funcionan.
- Todas las respuestas son JSON.
- Los endpoints usan UTF8.

---

# Archivos mínimos esperados

## API Shared

```txt
api/shared/response.php
api/shared/validation.php
api/shared/helpers.php
api/shared/middleware.php
```

---

## Config

```txt
config/database.php
config/app.php
```

---

## Status

```txt
api/status/health.php
api/status/database.php
```

---

# Prompt sugerido para Codex

```txt
Implementa el spec:
docs/specs/0004_php_api_foundation.md

Respeta:
- AGENTS.md
- docs/project/api_rules.md
- docs/project/coding_rules.md
- docs/project/architecture.md

Ruta:
C:\Mayingo\Proyectos\InvitaStudio

Objetivo:
Crear la infraestructura base de la API PHP para InvitaStudio.

Restricciones:
- NO frameworks
- NO Composer
- NO Laravel
- NO Symfony
- NO MVC complejo

Entrega:
1. Plan breve
2. Archivos modificados
3. Implementación
4. Comandos manuales
5. Verificación DoD
```
