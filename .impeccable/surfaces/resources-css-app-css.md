---
version: 1
slug: "resources-css-app-css"
primary_target: "resources/css/app.css"
related_targets: ["resources/views/components","resources/views/diseno"]
---

# Superficie: sistema de diseño móvil de la plataforma JOTA-JOTI 2026

## Alcance y modo
Modo **Operate** (app móvil de uso en tarea). Cubre el sistema de diseño (tokens, componentes, layout móvil)
y las pantallas clave a 360 px de participante, dirigente y tienda. Panel admin (Filament) fuera de alcance
salvo el tema.

## Audiencia, tarea y restricciones
Participantes de 11 a 21 años con celular propio, mayormente Android de gama media/baja, al aire libre. Tareas:
entrar, entregar misiones, escanear, pagar, ver ranking. Dirigentes revisan de pie; la tienda cobra rápido.
Restricciones: WCAG AA, objetivos táctiles ≥48 px, cuerpo ≥16 px, copy en español paraguayo con voseo,
fuentes autohospedadas, sin colores ni tipografías literales en componentes (todo por tokens).

## Dirección elegida y momento memorable
Dirección **fijada por el usuario**: el estilo visual de jotajoti.info (sin sorteo de conceptos). Momentos
memorables: el pago confirmado, los puntos que se acreditan y el QR que rota.

## Decisiones abiertas
Kit oficial del logo en mejor calidad; fecha del evento (D1); copy final a validar con Leonardo.

## Direction contract

THESIS: Una app que se siente del evento: la alegría del JOTA-JOTI oficial (violeta scout, píldoras, Poppins
gruesa) puesta al servicio de una tarea rápida. Rechaza el tablero genérico de gamificación (tarjetas
idénticas con ícono, números enormes sin contexto).

OWN-WORLD: Fondo claro y limpio (blanco, lavanda `#F7F7FF`), violeta `#622599` como único acento de acción;
azul para enlaces; cian, rosa y amarillo solo para sellos y momentos de recompensa; verde y rojo para estados.
Poppins Bold en títulos y botones, Poppins Medium en el cuerpo. Botones en píldora de 56 px, tarjetas de
16 px con sombra suave, énfasis en violeta dentro de frases grises. Íconos Heroicons de trazo uniforme.

STORY: El chico entiende en un vistazo cuánto tiene y qué puede hacer ahora; cree que los números son justos y
exactos; y entrega, escanea o paga en dos o tres toques, con confirmación clara.

FIRST VIEWPORT: Inicio a 360 px: saludo, dos saldos grandes (puntos y J-lis) en tarjetas lado a lado, la
patrulla debajo, y bajo ellos la lista «Para vos»; barra inferior de 5 pestañas con Escanear al centro como
botón circular violeta elevado. La acción principal siempre al alcance del pulgar.

FORM: Pinned by the user (estilo jotajoti.info), no direction roll; rank on the user's request, seed key:
none (pinned). Operate surface, tareas en celular.

FINISH: unreviewed and undocumented is unfinished; this build ends with the finish review, the verdict, DESIGN.md, and every shipping raster carrying its provenance
