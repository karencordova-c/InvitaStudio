# Architecture — InvitaStudio

## Arquitectura general

InvitaStudio utilizará una arquitectura web tradicional separando:

- Frontend público.
- Panel administrativo.
- API PHP.
- Base de datos MySQL.

---

## Estructura del sistema

```txt
Cliente Web
    ↓
Frontend HTML/CSS/JS
    ↓
API PHP
    ↓
MySQL / MariaDB
```

---

## Organización de carpetas

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
│
├── api/
│   ├── orders/
│   ├── payments/
│   ├── deliveries/
│   ├── messages/
│   └── auth/
│
├── config/
│
├── database/
│
├── storage/
│
├── uploads/
│
└── docs/
```

---

## Frontend

### Responsabilidades
- Mostrar información.
- Capturar formularios.
- Consumir API.
- Mostrar estados.
- Navegación responsive.

### Tecnologías
- HTML
- CSS
- JavaScript Vanilla

---

## Backend PHP

### Responsabilidades
- Validar datos.
- Procesar solicitudes.
- Conectar base de datos.
- Gestionar pedidos.
- Responder JSON.

### Organización
Cada módulo tendrá endpoints separados.

Ejemplo:

```txt
api/orders/create.php
api/orders/list.php
api/orders/update_status.php
```

---

## Base de datos

### Motor
- MariaDB
- MySQL

### Objetivos
- Simplicidad.
- Integridad relacional.
- Fácil mantenimiento.

---

## Flujo principal

### Registro de pedido

```txt
Cliente
→ Formulario
→ API create_order
→ Base de datos
→ Número de pedido
```

### Validación de pago

```txt
Administrador
→ Panel admin
→ Validar pago
→ Actualizar estado
```

### Entrega final

```txt
Administrador
→ Subir archivo
→ Registrar entrega
→ Cliente recibe acceso
```

---

## Principios arquitectónicos

- Separación de responsabilidades.
- Código simple.
- Baja complejidad.
- Escalable.
- Fácil migración futura.

---

## Arquitectura V1

### Prioridad
Primero funcionalidad estable local.

### NO prioridad
- Microservicios.
- Docker.
- Kubernetes.
- Arquitecturas complejas.
- Frameworks enterprise.

---

## Preparación futura

La arquitectura deberá permitir:

- Integrar login.
- Integrar pagos reales.
- Migrar frontend.
- Migrar backend.
- Migrar a hosting productivo.
