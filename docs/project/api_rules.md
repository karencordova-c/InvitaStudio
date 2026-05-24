# API Rules — InvitaStudio

## Arquitectura API

La API utilizará endpoints PHP simples.

Formato:

```txt
/api/modulo/accion.php
```

Ejemplo:

```txt
/api/orders/create.php
/api/orders/list.php
/api/orders/update_status.php
```

---

## Formato de respuesta

Todas las respuestas deben ser JSON.

Ejemplo éxito:

```json
{
  "success": true,
  "message": "Pedido registrado",
  "data": {}
}
```

Ejemplo error:

```json
{
  "success": false,
  "message": "Campos inválidos"
}
```

---

## Headers

Todos los endpoints deben retornar:

```php
header('Content-Type: application/json');
```

---

## Validaciones

Validar SIEMPRE:

- Campos obligatorios.
- Tipos de datos.
- Longitudes.
- Formato email.
- Estados válidos.
- IDs válidos.

---

## Seguridad mínima

- Sanitizar entradas.
- Usar prepared statements.
- Nunca concatenar SQL.
- Validar uploads.
- Limitar tipos de archivo.

---

## Endpoints V1

### Orders
- create
- list
- details
- update_status

### Payments
- register
- confirm
- reject

### Deliveries
- upload
- details

### Messages
- create
- list

### Status
- lookup

---

## Códigos HTTP

### Éxito
- 200 OK
- 201 Created

### Error cliente
- 400 Bad Request
- 401 Unauthorized
- 404 Not Found

### Error servidor
- 500 Internal Server Error

---

## Organización interna

Cada endpoint debe:

1. Validar método.
2. Validar datos.
3. Ejecutar lógica.
4. Retornar JSON.

---

## Métodos HTTP

### GET
Consultas.

### POST
Creación.

### PUT
Actualización.

### DELETE
Eliminación futura.

---

## Reglas importantes

- Mantener endpoints pequeños.
- NO mezclar HTML con API.
- NO imprimir texto fuera del JSON.
- Registrar errores importantes.
- Mantener consistencia.
