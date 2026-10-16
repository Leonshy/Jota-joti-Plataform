---
name: JOTA-JOTI 2026 Plataforma
description: App móvil de juego scout con el color y la voz del sitio oficial jotajoti.info, puesta al servicio de tareas rápidas al aire libre.
colors:
  violeta-marca: "#622599"
  violeta-fuerte: "#4e1e7a"
  violeta-tinta: "#261940"
  lavanda-ficha: "#ebebff"
  lavanda-fondo: "#f7f7ff"
  azul-enlace: "#00639a"
  azul-foco: "#0079c0"
  cian-puntos: "#82e6de"
  cian-fondo: "#d9f7f4"
  rosa-jlis: "#ff8dff"
  rosa-fondo: "#ffe9ff"
  verde-exito: "#248737"
  verde-texto: "#1d6e2d"
  verde-fondo: "#e6f3ea"
  ambar-aviso: "#f9b700"
  ambar-texto: "#7a5800"
  ambar-fondo: "#fff4cc"
  rojo-error: "#ff5655"
  rojo-texto: "#b3261e"
  rojo-fondo: "#fdecec"
  blanco: "#ffffff"
  tinta: "#212529"
  gris-apoyo: "#5b636a"
  gris-campo: "#7b838a"
  gris-borde: "#dee2e6"
  gris-inactivo: "#e9ecef"
typography:
  display:
    fontFamily: "Poppins, ui-sans-serif, system-ui, sans-serif"
    fontSize: "2.25rem"
    fontWeight: 700
    lineHeight: 1.1
  headline:
    fontFamily: "Poppins, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1.75rem"
    fontWeight: 700
    lineHeight: 1.2
  title:
    fontFamily: "Poppins, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1.125rem"
    fontWeight: 700
    lineHeight: 1.45
  body:
    fontFamily: "Poppins, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1rem"
    fontWeight: 500
    lineHeight: 1.55
  label:
    fontFamily: "Poppins, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.875rem"
    fontWeight: 700
    lineHeight: 1.35
rounded:
  sm: "8px"
  md: "16px"
  lg: "24px"
  full: "9999px"
spacing:
  tactil: "48px"
  boton: "56px"
  pantalla-x: "16px"
  pantalla-y: "20px"
  gap-seccion: "20px"
components:
  button-primary:
    backgroundColor: "{colors.violeta-marca}"
    textColor: "{colors.blanco}"
    typography: "{typography.label}"
    rounded: "{rounded.full}"
    height: "{spacing.boton}"
    padding: "0 24px"
  button-primary-hover:
    backgroundColor: "{colors.violeta-fuerte}"
  button-secondary:
    backgroundColor: "{colors.blanco}"
    textColor: "{colors.violeta-marca}"
    rounded: "{rounded.full}"
    height: "{spacing.boton}"
  button-secondary-hover:
    backgroundColor: "{colors.lavanda-ficha}"
  button-danger:
    backgroundColor: "{colors.rojo-texto}"
    textColor: "{colors.blanco}"
    rounded: "{rounded.full}"
    height: "{spacing.boton}"
  button-disabled:
    backgroundColor: "{colors.gris-inactivo}"
    textColor: "{colors.gris-apoyo}"
    rounded: "{rounded.full}"
  card:
    backgroundColor: "{colors.blanco}"
    textColor: "{colors.tinta}"
    rounded: "{rounded.md}"
    padding: "16px"
  card-puntos:
    backgroundColor: "{colors.cian-puntos}"
    textColor: "{colors.violeta-tinta}"
    rounded: "{rounded.md}"
    padding: "16px"
  card-jlis:
    backgroundColor: "{colors.rosa-jlis}"
    textColor: "{colors.violeta-tinta}"
    rounded: "{rounded.md}"
    padding: "16px"
  field:
    backgroundColor: "{colors.blanco}"
    textColor: "{colors.tinta}"
    rounded: "{rounded.sm}"
    height: "{spacing.boton}"
    padding: "0 16px"
  badge-success:
    backgroundColor: "{colors.verde-fondo}"
    textColor: "{colors.verde-texto}"
    rounded: "{rounded.full}"
    padding: "4px 12px"
  badge-warning:
    backgroundColor: "{colors.ambar-fondo}"
    textColor: "{colors.ambar-texto}"
    rounded: "{rounded.full}"
    padding: "4px 12px"
  badge-error:
    backgroundColor: "{colors.rojo-fondo}"
    textColor: "{colors.rojo-texto}"
    rounded: "{rounded.full}"
    padding: "4px 12px"
  nav-scan-center:
    backgroundColor: "{colors.violeta-marca}"
    textColor: "{colors.blanco}"
    rounded: "{rounded.full}"
    size: "56px"
---

# Design System: JOTA-JOTI 2026 Plataforma

## Overview

**Creative North Star: "El pañuelo del evento"**

La app habla el idioma visual de jotajoti.info (decisión fijada por el usuario, no una dirección sorteada): fondo claro y limpio, violeta scout como único color de acción, botones en píldora, Poppins gruesa. Es una herramienta de tarea para chicos de 11 a 21 años con el celular al sol, así que la alegría viene del color y la forma, no de decoración: un acento, bloques saturados solo cuando hay recompensa, y movimiento solo en tres momentos.

Densidad de modo Operate: una columna de 448 px como máximo (`max-w-md`), centrada en pantallas grandes, cuerpo de 16 px, objetivos táctiles de 48 a 56 px. Los títulos de pantalla van en violeta de marca, y las frases grises llevan énfasis en violeta. Rechaza el tablero genérico de gamificación: tarjetas idénticas con ícono y números enormes sin contexto.

**Key Characteristics:**
- Un solo acento de acción (violeta `#622599`); azul solo para enlaces y foco.
- Cian = puntos, rosa = LisCoins: los dos bloques saturados son las monedas del juego.
- Poppins Medium 500 en cuerpo, Poppins Bold 700 en títulos, botones y etiquetas. No existe otro peso.
- Píldoras para acciones y estados; 16 px de radio para tarjetas; 8 px para campos.
- Profundidad con sombras suaves violeta-tinta; nada de halos ni sombras duras.
- Todo sale de tokens de `resources/css/app.css`; los componentes no llevan colores ni fuentes literales.

## Colors

Paleta de marca medida en jotajoti.info sobre blanco y lavanda; los colores semánticos de texto se oscurecieron para cumplir AA.

### Primary
- **Violeta de marca** (#622599): botón primario, título h1 y de cabecera, ícono y texto de pestaña activa, foco de campo, barra del QR, `accent-color` y `caret-color`. Hover y estado presionado en **Violeta fuerte** (#4e1e7a).
- **Violeta tinta** (#261940): texto sobre los bloques cian y rosa; base de las sombras.

### Secondary
- **Azul de enlace** (#00639a): enlaces y botón de enlace. **Azul de foco** (#0079c0): anillo de foco de 3 px, el mismo en todo el sistema.

### Tertiary (recompensa)
- **Cian puntos** (#82e6de, fondo suave #d9f7f4): tarjeta de puntos, selección de texto, estado informativo, aro del sello de éxito.
- **Rosa LisCoins** (#ff8dff, fondo suave #ffe9ff): tarjeta de LisCoins, momentos de recompensa.
- **Ambar** (#f9b700; texto #7a5800; fondo #fff4cc): avisos y pendientes.

### Semantic
- **Éxito** verde (#248737; texto #1d6e2d; fondo #e6f3ea). **Error** (#ff5655 solo decorativo; texto y botón peligro #b3261e; fondo #fdecec). El texto semántico usa siempre las variantes oscurecidas.

### Neutral
- **Blanco** (#ffffff) fondo y superficie; **Lavanda de fondo** (#f7f7ff) fondo suave y fondo de escritorio fuera de la columna; **Lavanda ficha** (#ebebff) hover de secundario, pestaña activa, pista de la barra del QR.
- **Tinta** (#212529) texto; **Gris de apoyo** (#5b636a) texto secundario y placeholder (oscurecido para leer sobre lavanda); **Gris de campo** (#7b838a) borde de campo; **Gris de borde** (#dee2e6) divisores; **Gris inactivo** (#e9ecef) botón deshabilitado.

### Named Rules
**The One Action Colour Rule.** El violeta es el único color de acción. Azul es solo enlace y foco; nunca un segundo botón.
**The Currency Colour Rule.** Los bloques saturados cian (puntos) y rosa (LisCoins) son colores de moneda. No se usan para decorar ni para otros datos.
**The Darkened Text Rule.** El texto de estado usa las variantes oscuras (#b3261e, #7a5800, #1d6e2d, #5b636a) para AA. Los tonos claros (#ff5655, #f9b700, #248737) son fondos, discos e íconos grandes, no texto pequeño.
**The Not-Only-Colour Rule.** Éxito, aviso y error llevan ícono además de texto y color.

## Typography

**Display / Body Font:** Poppins (autohospedada, `font-display: swap`; fallback `ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif`)

**Character:** Geométrica, redonda y gruesa como el sitio oficial. Solo dos pesos: 500 para leer, 700 para mandar.

### Hierarchy
- **Display** (700, 2.25rem / 36 px, 1.1): saldos grandes (puntos y LisCoins) con números tabulares.
- **Headline** (700, 1.75rem / 28 px, 1.2; 1.375rem / 22 px, 1.3 para la escala `xl`): h1 de pantalla y de resultado (2xl), violeta de marca.
- **Title** (700, 1.125rem / 18 px, 1.45): título de cabecera fija, texto de campo.
- **Body** (500, 1rem / 16 px, 1.55): todo el cuerpo; apoyo en 0.9375rem (15 px) y 0.875rem (14 px).
- **Label** (700, 0.875rem / 14 px, 1.35): etiquetas de campo, insignias y pestañas de barra, siempre en minúscula normal y sin tracking extra.

### Named Rules
**The Two Weights Rule.** Solo existen 500 y 700. No usar `font-normal` ni `font-semibold`; el navegador sintetizaría un peso que no es de la marca.
**The Title Colour Rule.** h1 en violeta de marca; h2 a h4 en el color de texto. Los títulos usan `text-wrap: balance`.
**The Tabular Numbers Rule.** Saldos, puntajes y horas llevan `numeros` (tabular-nums) para alinear columnas. Los números se formatean con punto de miles.
**The Fixed Scale Rule.** Escala fija en rem, sin tamaños fluidos: es una app de tarea, no una página de marca.

## Layout

Una columna móvil de ancho completo hasta 448 px, centrada, con sombra elevada solo en `md` y fondo lavanda suave alrededor. Diseñada a 360 px. Márgenes de pantalla de 16 px horizontal y 20 px vertical, con 20 px entre bloques (`gap-5`); 12 a 16 px dentro de tarjetas y listas. El contenido deja 8 rem (`pb-32`) al final cuando hay barra inferior. Cabecera fija de 64 px con borde inferior, botón volver de 48 px y respeto de `safe-area-inset`. Primer viewport de Inicio: saludo, dos saldos lado a lado, patrulla, lista «Para vos». Cada pantalla tiene exactamente un h1; hay enlace de salto al contenido.

## Elevation & Depth

Híbrido: capas tonales para agrupar (lavanda, cian, rosa, estados) y sombras suaves con desplazamiento y desenfoque solo para despegar lo que se toca o escanea.

### Shadow Vocabulary
- **Tarjeta** (`0 1px 2px rgb(38 25 64 / 0.06), 0 4px 14px rgb(38 25 64 / 0.08)`): tarjeta base blanca y saldo blanco.
- **Elevada** (`0 2px 4px rgb(38 25 64 / 0.1), 0 10px 28px rgb(38 25 64 / 0.18)`): panel del QR, botón circular Escanear, columna en escritorio.

### Named Rules
**The Soft Shadow Rule.** Las sombras son violeta-tinta, difusas y de dos capas. Sin halos de color y sin sombras duras de desplazamiento sin desenfoque.
**The Tone Before Shadow Rule.** Las tarjetas de color (cian, rosa, estados) van planas; la sombra es de las tarjetas blancas.

## Shapes

Píldora para todo lo que se toca o etiqueta (botones, insignias, pestaña activa, botón volver, barra del QR); 16 px para tarjetas, avisos y panel del QR; 8 px para campos y el contorno de foco. Los discos de íconos y el sello son círculos completos. Campos con borde de 2 px. Íconos Heroicons de trazo uniforme (outline 24, mini para insignias), con `aria-hidden`.

## Components

### Buttons
- **Shape:** píldora completa, alto mínimo 56 px (`min-h-boton`), ancho completo por defecto, texto 16 px bold, ícono opcional de 20 px antes o después.
- **Primary:** violeta de marca con texto blanco; hover violeta fuerte; presionado `scale(0.98)`.
- **Secondary:** blanco con anillo interior de 2 px violeta y texto violeta; hover lavanda ficha.
- **Danger:** fondo #b3261e, texto blanco. **Link:** sin fondo, azul de enlace, 48 px de alto.
- **Disabled / loading:** gris inactivo con texto gris de apoyo; cargando muestra ícono giratorio y `aria-busy`.

### Cards / Containers
- 16 px de radio, 16 px de relleno. Tonos: base (blanco con sombra tarjeta), suave, info, recompensa, cian, rosa, éxito, aviso, error, marca (violeta con texto blanco).
- **Saldo:** etiqueta con ícono a 15 px, número en 36 px bold; blanco con número violeta, o bloque cian/rosa con todo en violeta tinta.

### Inputs / Fields
- Etiqueta bold 15 px sobre el campo; campo de 56 px, borde de 2 px gris de campo, 8 px de radio, texto 18 px. **Foco:** borde violeta más anillo azul de 3 px. **Error:** borde y fondo de error, mensaje bold con ícono y `role="alert"`. Ayuda en 14 px gris. Variante PIN: numérica, centrada, 24 px con tracking amplio.

### Badges and Notices
- **Insignia:** píldora de 14 px bold; neutro, marca, info, éxito, aviso, error; las tres de estado llevan ícono.
- **Aviso:** bloque de 16 px con ícono de 24 px en el color de texto del estado y texto en tinta; `role="alert"` para aviso y error, `status` para el resto.

### Navigation
- **Barra inferior fija** (columna de 448 px, borde superior, `safe-area`): etiquetas de 14 px bold; activa en violeta con píldora lavanda ficha tras el ícono, inactiva en gris de apoyo; `aria-current="page"`.
- **Participante:** 5 pestañas (Inicio, Misiones, Escanear, Actividades, Ranking); Escanear al centro como círculo violeta de 56 px, elevado 28 px, con sombra elevada y aro blanco de 4 px.
- **Dirigente:** 4 pestañas (Cola, Asistencia, Cumplimientos, Penalizaciones). La barra de tienda es una tercera variante del mismo componente.
- **Filas de lista:** 64 px de alto mínimo, disco de ícono de 44 px en lavanda ficha, chevron si navega.

### Signature: Sello de resultado
Disco sólido de 96 px del color de estado con aro de 8 px (éxito: verde con aro cian), un h1 violeta de 28 px, una frase gris y las acciones. Solo el éxito se anima: sello con escala, onda cian y trazo del tilde, luego el conteo del saldo.

### Signature: QR rotativo
QR siempre negro sobre blanco con zona de silencio, en panel blanco de 16 px con sombra elevada y ancho máximo de 288 px. Debajo, barra violeta de 8 px que se vacía en línea recta durante el ciclo de 60 s. Entra con `qr-entra` y sale con `qr-sale`.

### Motion
Tres momentos con nombre, el resto es respuesta de interfaz de 150 a 220 ms: (1) **sello de pago confirmado**: `sello` 420 ms + `onda` 600 ms + `trazo-check` 320 ms con retraso de 100 ms; (2) **puntos y LisCoins acreditados**: conteo numérico en el saldo; (3) **QR que rota**: `qr-entra` 280 ms, `qr-sale` 160 ms y la cuenta lineal. Entrada de texto con `sube` (10 px, 320 ms, escalonada 60 ms). Curva `cubic-bezier(0.16, 1, 0.3, 1)` (`ease-salida`). Con `prefers-reduced-motion`, sello, sube y qr-entra se reducen a un fundido de 160 ms; onda, qr-sale, pulso y trazo se quitan (el tilde queda dibujado); la barra de cuenta regresiva se conserva porque es información.

## Do's and Don'ts

### Do:
- **Do** sacar todo color, tamaño y fuente de los tokens de `app.css`; sin hex ni familias literales en componentes.
- **Do** usar el violeta (#622599) como única acción primaria, en píldora de 56 px.
- **Do** reservar cian para puntos y rosa para LisCoins, con texto violeta tinta (#261940) encima.
- **Do** dar a cada objetivo táctil al menos 48 px y al cuerpo 16 px.
- **Do** acompañar éxito, aviso y error con ícono y texto, y usar las variantes oscurecidas para texto de estado.
- **Do** poner foco visible (anillo azul de 3 px con 3 px de separación) y un único h1 por pantalla.
- **Do** formatear cantidades con el helper `cantidad()` y alinear números con `numeros`.
- **Do** mantener el QR negro sobre blanco, y animar solo los tres momentos con nombre.

### Don't:
- **Don't** usar pesos distintos de 500 y 700.
- **Don't** usar azul, cian o rosa como segundo color de acción o decoración.
- **Don't** repetir tarjetas idénticas con ícono y número enorme sin contexto (el tablero genérico de gamificación).
- **Don't** usar sombras duras sin desenfoque, halos de color ni sombras sobre tarjetas de color.
- **Don't** usar texto pequeño en #ff5655, #f9b700 o #248737; no pasan como texto.
- **Don't** animar con desplazamiento, escala o desenfoque sin cubrir el caso de movimiento reducido.
- **Don't** usar íconos de glifo o emoji: solo Heroicons de trazo.
