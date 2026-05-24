# Spec 0013 — Delivery Upload System

## Objetivo

Implementar el sistema administrativo de carga y entrega de archivos finales de invitaciones digitales.

Esta spec permitirá:

- Subir archivos finales.
- Asociar entregas a pedidos.
- Validar formatos.
- Registrar entregas.
- Permitir descarga al cliente.
- Actualizar estados automáticamente.

Este módulo representa la etapa final operativa del flujo de negocio.

---

# Dependencias

Esta spec depende de:

- 0010_order_management_panel
- 0011_payment_validation_flow
- 0012_order_status_lookup

---

# Ruta del proyecto

```txt
C:\Mayingo\Proyectos\InvitaStudio
```

---

# Objetivo funcional

El administrador deberá poder:

1. Seleccionar pedido.
2. Subir archivo final.
3. Registrar formato.
4. Registrar entrega.
5. Actualizar estado.
6. Permitir descarga cliente.

---

# Rutas requeridas

## Admin frontend

```txt
admin/deliveries/index.php
admin/deliveries/upload.php
```

---

## API

```txt
api/deliveries/upload.php
api/deliveries/details.php
```

---

# Protección requerida

## Obligatorio

Usar:

```php
requireAdminAuth()
```

---

# Tipos archivo permitidos

## Permitidos

```txt
jpg
jpeg
png
pdf
mp4
```

---

# PROHIBIDOS

```txt
exe
php
js
sh
bat
zip
rar
```

---

# Tamaño máximo sugerido

```txt
50MB
```

---

# Directorio requerido

## Uploads

```txt
uploads/deliveries/
```

---

# Estructura sugerida

```txt
uploads/deliveries/{numero_pedido}/
```

Ejemplo:

```txt
uploads/deliveries/INV-2026-000001/
```

---

# Flujo esperado

```txt
Admin
→ Selecciona pedido
→ Selecciona archivo
→ upload.php
→ Validación backend
→ Guardar archivo
→ Registrar DB
→ Actualizar pedido
→ Cliente disponible descarga
```

---

# Validaciones backend obligatorias

## Validar

- Sesión admin.
- Pedido existente.
- Archivo válido.
- Tamaño válido.
- MIME válido.
- Extensión válida.

---

# Tabla requerida

## entregas

Usar tabla creada en:

```txt
0002_local_database_schema
```

---

# Datos requeridos

## Registrar

```txt
pedido_id
formato_entrega
archivo_final
fecha_entrega
notas_entrega
```

---

# Actualización automática requerida

## pedidos.estado_pedido

Debe cambiar automáticamente a:

```txt
entregado
```

---

# Entrega visible cliente

## Condición

Solo visible si:

```txt
estado_pedido = entregado
```

---

# Seguridad uploads

## IMPORTANTE

- Validar MIME.
- Renombrar archivos.
- Evitar ejecución.
- NO confiar extensión cliente.

---

# Renombrado archivos

## Recomendado

Usar:

```txt
timestamp + random + extension
```

Ejemplo:

```txt
20260509_ab12cd34.pdf
```

---

# API response requerida

## Éxito

```json
{
  "success": true,
  "message": "Entrega registrada correctamente"
}
```

---

## Error

```json
{
  "success": false,
  "message": "Archivo inválido"
}
```

---

# UI requerida

## Upload form

Debe incluir:

- Selección archivo.
- Vista nombre archivo.
- Tipo entrega.
- Notas entrega.
- Loading state.

---

# Formatos entrega

## Opciones

```txt
Imagen
PDF
Video
```

---

# UX requerida

## Upload

Debe sentirse:

- Simple.
- Seguro.
- Profesional.
- Claro.

---

# Loading states

## Requeridos

Mientras sube:

- Progress básico.
- Botón disabled.
- Estado visual.

---

# Error states

## Mostrar mensajes claros

Ejemplo:

```txt
Formato no permitido
Archivo demasiado grande
```

---

# details.php

Debe retornar:

- Información entrega.
- Archivo.
- Fecha.
- Formato.

---

# Descarga cliente

## IMPORTANTE

La descarga debe:

- Validar pedido.
- Validar correo.
- Validar estado entregado.

---

# Responsive requerido

## Compatibilidad

- Desktop
- Tablet
- Mobile

---

# Activity log

## Registrar

- Upload archivo.
- Entrega registrada.
- Descarga cliente opcional.

---

# Restricciones

## NO hacer

- No almacenamiento cloud.
- No S3.
- No CDN.
- No procesamiento video.
- No previews avanzados.

---

# Objetivo V1

Prioridad:

- Upload seguro.
- Entrega funcional.
- Descarga estable.
- Flujo simple.

---

# Validaciones

La implementación será válida si:

- Upload funciona.
- Validaciones funcionan.
- Archivo se guarda.
- Entrega se registra.
- Estado pedido cambia.
- Cliente puede descargar.

---

# Archivos mínimos esperados

## Admin

```txt
admin/deliveries/index.php
admin/deliveries/upload.php
```

---

## API

```txt
api/deliveries/upload.php
api/deliveries/details.php
```

---

## JS

```txt
public/assets/js/delivery_upload.js
```

---

# Prompt sugerido para Codex

```txt
Implementa el spec:
docs/specs/0013_delivery_upload_system.md

Respeta:
- AGENTS.md
- docs/project/api_rules.md
- docs/project/database_rules.md
- docs/project/coding_rules.md

Ruta:
C:\Mayingo\Proyectos\InvitaStudio

Objetivo:
Implementar el sistema administrativo de entrega de archivos finales.

IMPORTANTE:
- Validar uploads
- Validar MIME
- Upload seguro obligatorio

Restricciones:
- NO cloud storage
- NO S3
- NO CDN
- NO frameworks

Entrega:
1. Plan breve
2. Archivos modificados
3. Implementación
4. Comandos manuales
5. Verificación DoD
```
