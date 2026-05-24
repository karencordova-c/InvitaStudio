# Coding Rules — InvitaStudio

## Filosofía

El proyecto debe priorizar:

- Claridad.
- Simplicidad.
- Mantenibilidad.
- Escalabilidad razonable.

---

## Reglas generales

- Código entendible.
- Evitar complejidad innecesaria.
- Comentarios útiles.
- Mantener estructura modular.

---

## PHP

### Reglas
- Usar PHP 8+.
- Separar lógica.
- No mezclar HTML excesivamente.
- Funciones pequeñas.
- Validaciones explícitas.

### Prohibido
- SQL concatenado.
- Código duplicado excesivo.
- Variables ambiguas.

---

## SQL

### Reglas
- snake_case.
- Queries claras.
- Prepared statements.
- Relaciones consistentes.

---

## HTML

### Reglas
- HTML semántico.
- Formularios accesibles.
- Estructura limpia.

---

## CSS

### Objetivo visual
- Minimalista.
- Elegante.
- Responsive.
- Profesional.

### Organización
```txt
assets/css/
```

Separar:
- base.css
- layout.css
- components.css
- pages.css

---

## JavaScript

### Reglas
- Vanilla JS.
- Modular cuando sea posible.
- Evitar código global excesivo.

### Objetivo
- Formularios.
- Consumo API.
- Validaciones.
- Interacciones UI.

---

## Naming

### Variables
```txt
camelCase
```

### Funciones
```txt
camelCase
```

### Archivos
```txt
snake_case
```

---

## Comentarios

Comentar:
- Lógica importante.
- Decisiones complejas.
- Validaciones críticas.

No comentar cosas obvias.

---

## Seguridad

Siempre:
- Sanitizar entradas.
- Validar uploads.
- Escapar outputs.
- Validar formularios.

---

## Responsive

El sistema debe funcionar correctamente en:
- Desktop
- Tablet
- Mobile

---

## Objetivo V1

La prioridad es:
- Sistema funcional.
- Código entendible.
- Fácil mantenimiento.

No sobreingenierizar.
