# Spec 0005 — Public Website Layout

## Objetivo

Implementar la estructura visual base del sitio público de InvitaStudio.

Esta spec deberá construir:

- Layout principal.
- Navegación pública.
- Estructura responsive.
- Diseño visual inicial.
- Sistema base de estilos.
- Componentes reutilizables simples.

La finalidad es crear la apariencia pública profesional inicial del sistema.

---

# Dependencias

Esta spec depende de:

- 0001_project_bootstrap
- 0004_php_api_foundation

---

# Ruta del proyecto

```txt
C:\Mayingo\Proyectos\InvitaStudio
```

---

# Objetivos visuales

El sitio deberá verse:

- Moderno.
- Minimalista.
- Elegante.
- Profesional.
- Responsive.
- Claro y limpio.

---

# Estilo visual esperado

## Inspiración

- Sitios modernos de eventos.
- Invitaciones digitales.
- Diseño elegante.
- Estética suave.

---

# Paleta visual sugerida

## Colores

### Primario
```txt
#D8B4F8
```

### Secundario
```txt
#F5EFFF
```

### Fondo
```txt
#FFFFFF
```

### Texto
```txt
#2B2B2B
```

IMPORTANTE:

Codex puede ajustar ligeramente los tonos manteniendo apariencia elegante.

---

# Tipografía

Usar:

```txt
sans-serif moderna
```

Ejemplo:

- Poppins
- Inter
- Nunito

---

# Estructura requerida

## Header

Debe incluir:

- Logo placeholder.
- Navegación.
- Botón principal.
- Responsive menu.

---

## Footer

Debe incluir:

- Información básica.
- Redes placeholder.
- Derechos.
- Navegación rápida.

---

# Páginas requeridas

## 1. index.html

Landing principal.

Debe contener:

- Hero section.
- Presentación del servicio.
- Beneficios.
- CTA principal.
- Preview visual placeholder.

---

## 2. services.html

Catálogo inicial.

Debe contener:

- Lista visual de servicios.
- Cards.
- Precios placeholder.
- Categorías placeholder.

---

## 3. gallery.html

Galería visual.

Debe contener:

- Grid responsive.
- Placeholders de invitaciones.
- Categorías visuales.

---

## 4. request.html

Página placeholder del formulario.

IMPORTANTE:

Aún NO implementar formulario funcional.

Solo estructura visual.

---

## 5. status.html

Pantalla visual placeholder para consulta de pedido.

---

## 6. contact.html

Página de contacto.

Debe contener:

- Información placeholder.
- Redes placeholder.
- Formulario visual placeholder.

---

# Sistema CSS requerido

## Archivos requeridos

```txt
public/assets/css/
│
├── base.css
├── layout.css
├── components.css
├── pages.css
└── responsive.css
```

---

# Reglas CSS

## Obligatorio

- Variables CSS.
- Mobile-first.
- Responsive.
- Layout limpio.
- Espaciado consistente.

---

# Componentes requeridos

## Buttons

Crear estilos para:

- Primary button
- Secondary button
- Outline button

---

## Cards

Crear estilos reutilizables para:

- Servicios
- Galería
- Información

---

## Navigation

Responsive.

Debe funcionar en mobile.

---

# Responsive requerido

## Compatibilidad

- Desktop
- Tablet
- Mobile

---

# Breakpoints sugeridos

```txt
768px
1024px
```

---

# JavaScript requerido

## app.js

Debe incluir:

- Mobile menu toggle.
- Navegación básica.
- Helpers UI simples.

---

# Assets

## Crear carpetas

```txt
public/assets/img/
public/assets/icons/
```

---

# Placeholders

Usar:

- Divs placeholder.
- Imagenes placeholder.
- Texto placeholder.

NO usar imágenes reales todavía.

---

# Accesibilidad mínima

## Obligatorio

- Labels básicos.
- Contraste aceptable.
- Navegación clara.
- Botones identificables.

---

# SEO básico

Agregar:

```txt
title
meta description
viewport
```

en páginas públicas.

---

# Restricciones

## NO hacer

- No frameworks CSS.
- No Bootstrap.
- No Tailwind.
- No React.
- No Vue.
- No Angular.

---

# Objetivo V1

La prioridad es:

- Apariencia profesional.
- Responsive.
- Navegación clara.
- Base visual reutilizable.

---

# Validaciones

La implementación será válida si:

- Todas las páginas existen.
- La navegación funciona.
- El sitio es responsive.
- Los CSS están separados correctamente.
- Mobile menu funciona.
- El diseño es consistente.

---

# Archivos mínimos esperados

## HTML

```txt
public/index.html
public/services.html
public/gallery.html
public/request.html
public/status.html
public/contact.html
```

---

## CSS

```txt
public/assets/css/base.css
public/assets/css/layout.css
public/assets/css/components.css
public/assets/css/pages.css
public/assets/css/responsive.css
```

---

## JS

```txt
public/assets/js/app.js
```

---

# Prompt sugerido para Codex

```txt
Implementa el spec:
docs/specs/0005_public_website_layout.md

Respeta:
- AGENTS.md
- docs/project/coding_rules.md
- docs/project/architecture.md

Ruta:
C:\Mayingo\Proyectos\InvitaStudio

Objetivo:
Crear la estructura visual pública inicial de InvitaStudio.

Restricciones:
- NO Bootstrap
- NO Tailwind
- NO frameworks JS
- NO React
- NO Vue
- NO Angular

Entrega:
1. Plan breve
2. Archivos modificados
3. Implementación
4. Comandos manuales
5. Verificación DoD
```
