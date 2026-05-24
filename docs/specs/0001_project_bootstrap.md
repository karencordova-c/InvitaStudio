# Spec 0001 — Project Bootstrap

## Objetivo

Crear la estructura inicial del proyecto InvitaStudio siguiendo las reglas definidas en:

- AGENTS.md
- docs/project/vision.md
- docs/project/architecture.md
- docs/project/database_rules.md
- docs/project/api_rules.md
- docs/project/coding_rules.md

El objetivo de esta spec es preparar una base sólida y organizada para futuras implementaciones.

---

# Alcance

Esta spec debe:

- Crear la estructura base de carpetas.
- Crear archivos iniciales públicos.
- Crear estructura inicial API.
- Crear configuración base.
- Crear placeholders mínimos.
- Preparar el proyecto para Laragon.

Esta spec NO debe:

- Implementar lógica de negocio.
- Implementar base de datos completa.
- Implementar autenticación.
- Implementar pagos reales.
- Implementar panel funcional.

---

# Ruta del proyecto

```txt
C:\Mayingo\Proyectos\InvitaStudio
```

---

# Estructura esperada

```txt
InvitaStudio/
│
├── public/
│   ├── assets/
│   │   ├── css/
│   │   ├── js/
│   │   └── img/
│   │
│   ├── index.html
│   ├── services.html
│   ├── gallery.html
│   ├── request.html
│   ├── status.html
│   └── contact.html
│
├── admin/
│   └── index.php
│
├── api/
│   ├── orders/
│   ├── payments/
│   ├── deliveries/
│   ├── messages/
│   ├── status/
│   └── auth/
│
├── config/
│   ├── database.php
│   └── app.php
│
├── database/
│   ├── schema.sql
│   └── seed.sql
│
├── storage/
│
├── uploads/
│
├── docs/
│   ├── project/
│   └── specs/
│
├── README.md
└── AGENTS.md
```

---

# Requisitos funcionales

## RF-0001

El sistema deberá crear automáticamente toda la estructura de carpetas base.

---

## RF-0002

El sistema deberá crear archivos HTML placeholder para:

- Inicio
- Servicios
- Galería
- Solicitud
- Estado
- Contacto

---

## RF-0003

El sistema deberá crear placeholders básicos para:

- CSS
- JavaScript
- Configuración PHP
- SQL inicial

---

## RF-0004

El sistema deberá dejar preparada la arquitectura API modular.

---

# Requisitos técnicos

## Frontend

### Tecnologías permitidas
- HTML5
- CSS3
- JavaScript Vanilla

### Restricciones
- No frameworks.
- No build systems.
- No dependencias externas.

---

## Backend

### Tecnologías permitidas
- PHP 8+

### Restricciones
- No frameworks.
- No Composer.
- No ORM.

---

# Archivos mínimos esperados

## public/index.html

Debe contener:

- Header simple.
- Navegación.
- Hero placeholder.
- Footer placeholder.

---

## public/assets/css/base.css

Debe contener:

- Reset básico.
- Variables iniciales.
- Tipografía base.
- Estilos base.

---

## public/assets/js/app.js

Debe contener:

- Inicialización base.
- Placeholder de módulos.

---

## config/database.php

Debe contener:

- Configuración PDO básica.
- Variables separadas.
- Manejo básico de errores.

---

## config/app.php

Debe contener:

- Configuración global.
- Variables del sistema.

---

## database/schema.sql

Debe contener comentarios placeholder para:

- clientes
- pedidos
- pagos
- entregas
- mensajes_pedido
- usuarios_admin

---

# Convenciones

## Naming

### Carpetas
snake_case

### Archivos
snake_case

### Variables JS
camelCase

### Funciones JS
camelCase

---

# Diseño visual inicial

El diseño inicial deberá:

- Ser minimalista.
- Ser responsive.
- Tener apariencia profesional.
- Mantener estructura limpia.

---

# README

El README debe incluir:

- Descripción del proyecto.
- Tecnologías.
- Requisitos.
- Cómo ejecutar en Laragon.
- Estructura del proyecto.

---

# Validaciones

La implementación será válida si:

- Todas las carpetas existen.
- Todos los archivos mínimos existen.
- Laragon puede servir el proyecto.
- No existen errores PHP básicos.
- La navegación HTML funciona.

---

# No hacer

- No implementar lógica de negocio.
- No agregar librerías.
- No agregar autenticación.
- No agregar pagos reales.
- No agregar frameworks.

---

# Prompt sugerido para Codex

```txt
Implementa el spec:
docs/specs/0001_project_bootstrap.md

Respeta:
- AGENTS.md
- docs/project/architecture.md
- docs/project/coding_rules.md
- docs/project/api_rules.md
- docs/project/database_rules.md

Ruta:
C:\Mayingo\Proyectos\InvitaStudio

Restricciones:
- NO frameworks
- NO Composer
- NO librerías externas
- NO pagos reales
- NO autenticación

Entrega:
1. Plan breve
2. Archivos modificados
3. Implementación
4. Comandos manuales
5. Verificación DoD
```
