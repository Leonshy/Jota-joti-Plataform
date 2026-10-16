# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

Participantes del **JOTA-JOTI 2026** del Grupo Scout General Genes (con el G.S. General Aquino), de tres
unidades: **Tropa** (11–14 años), **Comunidad de Caminantes** (15–17) y **Clan Rover** (18–21). Entran desde
su **propio celular**, mayormente Android de gama media o baja, **al aire libre** (sol fuerte, señal
irregular, pantallas rayadas), a veces apurados entre una actividad y otra.

Otros tres roles, también en el celular durante el evento:
- **Dirigentes**, cada uno acotado a su unidad: revisan entregas en la cola, abren sesiones de asistencia
  con un QR, marcan patrullas como cumplidas y aplican penalizaciones. Revisan de parado, rápido.
- **Usuario de tienda**: cobra en LisCoins con un QR y hace devoluciones.
- **Administrador** (responsable técnico/del grupo): trabaja en un panel aparte, antes y durante el evento.

## Product Purpose

Una app web móvil de **juego** para el JOTA-JOTI 2026. Los participantes reciben **misiones** por horario,
entregan texto o fotos y un dirigente las valida. Reservan turnos en **talleres y estaciones** y marcan
asistencia escaneando un **QR que rota**. Acumulan **puntos** (para el ranking) y **LisCoins** (la moneda del
evento) que gastan en la **tienda** escaneando un **QR de cobro**.

Éxito: que el evento fluya sin filas ni papeles, que ningún saldo se pierda ni se duplique, y que el
premio al mejor equipo y al mejor beneficiario se pueda definir con números confiables.

## Positioning

Es la plataforma **del evento y de su gente**, no una app genérica de gamificación: reglas scouts reales
(patrullas, dirigentes por unidad, premio al mejor equipo), dos monedas independientes (puntos y LisCoins) y
un ranking que no expone nombres. Un núcleo reutilizable para gamificar otros eventos de webparaguay
(misiones + validación + registros contables + QR rotativo + QR de cobro).

## Operating Context

- Se usa durante un fin de semana de evento, en un lugar con **internet irregular**; no hay modo sin
  conexión. Mientras tanto hay un **responsable técnico presente** y un respaldo en papel para
  validaciones (R1).
- Varios cientos de participantes comparten la misma IP; hasta ~300 usuarios simultáneos.
- Los participantes se cargan antes del evento (CSV o uno por uno) y crean su PIN con solo su CI.
- La hora que vale es la del servidor (America/Asuncion). Despliegue en Plesk de webparaguay.
- El QR de asistencia se proyecta en la pantalla del dirigente y tiene que leerse a unos 3 metros.

## Capabilities and Constraints

- Roles: participante, dirigente, tienda, admin. Panel del admin en Filament (ya construido).
- Acceso: **CI + PIN de 4 dígitos**, sin bloqueo ni límite de intentos (decisión de producto: practicidad
  sobre seguridad). Primer ingreso solo con el CI.
- Los registros de puntos y LisCoins son **de solo agregar**; el saldo de LisCoins nunca es negativo (los puntos
  sí pueden serlo por penalizaciones).
- **Ranking anónimo** para el participante y el dirigente: solo puesto y puntaje, sin nombres ni marcas.
- Fotos de entregas: máximo 4, hasta 8 MB, JPG/PNG/WebP; se re-codifican y se les borra el EXIF.
- Datos personales mínimos: CI, nombre, grupo, unidad y patrulla.
- Sin WebSockets (polling), sin notificaciones push, sin app nativa (web con «agregar a pantalla de inicio»).
- Stack fijado: Laravel 12 + Livewire 4 + Tailwind v4 + Alpine; Filament para el admin.
- Fecha del evento del grupo (D1): **abierta**.

## Brand Commitments

- **Estilo visual de [jotajoti.info](https://jotajoti.info)** (JOTA-JOTI 2026 oficial): vinculante para
  toda la plataforma. El usuario lo definió así; el detalle (paleta, tipografía, formas) se documenta en
  `docs/design/identidad-visual.md` y se vuelve tokens al construir el sistema de diseño.
- **Logo oficial del JOTA-JOTI 2026: uso autorizado** (confirmado por el responsable del proyecto). Se
  tomó del propio sitio; si existe un kit oficial en mejor calidad, reemplaza al archivo del sitio.
- Nombre: «JOTA-JOTI 2026», organizado por el Grupo Scout General Genes con el G.S. General Aquino.
- **Voz**: español paraguayo con voseo («ingresá», «escaneá»), tono scout, cercano y directo; frases cortas
  y en segunda persona, como el sitio oficial. Ningún texto de la interfaz en inglés.

## Evidence on Hand

- `legajo-plataforma-jota-joti-2026.md` (alcance, reglas y decisiones), `CLAUDE.md`, `PLAN.md`.
- **Wireframes aprobados** (115 pantallas clickeables) y 53 diagramas de flujo en `docs/ux-flows/`.
- Dominio y panel admin construidos y probados; faltan las pantallas móviles.
- **No hay** fotos propias del evento, testimonios, métricas ni datos reales de participantes: no inventarlos.
  Las imágenes del sitio oficial muestran a menores y no se reutilizan.

## Product Principles

1. **Entrar rápido y sin trabas.** Un chico con un celular modesto, al sol y apurado tiene que poder
   ingresar, entregar y pagar en pocos toques; la seguridad de acceso es deliberadamente liviana.
2. **Los números son sagrados.** Saldos, puntos y LisCoins exactos y auditables; nada se duplica ni se pierde,
   y cada resultado repetido se informa como «ya registrado», no como error.
3. **Justo y anónimo.** El ranking del participante muestra solo puesto y puntaje; ninguna pantalla
   delata qué equipo es el propio ni expone a otros.
4. **El dirigente decide en segundos.** La cola, la revisión y el QR proyectado están pensados para usarse
   de parado y con una mano.
5. **Aguantar el campo.** Funciona con señal mala: cada acción informa su resultado, ninguna se hace a
   medias y el servidor decide.

## Accessibility & Inclusion

- **WCAG AA en móvil**, con foco en lectura **bajo sol directo** (contraste alto; cuerpo de texto de 16 px o más y etiquetas de apoyo de 14 px como mínimo).
- No depender solo del color para dar un estado (iconos y texto además).
- Objetivos táctiles de al menos 44 px; usable con una mano y con pantallas rayadas o de baja calidad.
- El QR de asistencia proyectado debe leerse a ~3 metros; el de cobro, a distancia de brazo.
- Español claro para chicos de 11 años en adelante; sin jerga técnica.
