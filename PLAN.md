# PLAN.md — Plataforma JOTA-JOTI 2026

Fases de build. Cada fase tiene entregables y un criterio de cierre. No se empieza una fase sin
cerrar la anterior, salvo donde se indica que corren en paralelo. Contexto: `CLAUDE.md`.
Alcance: `legajo-plataforma-jota-joti-2026.md`.

Estado: ☐ pendiente · ◐ en curso · ☑ cerrada

---

## Fase 0 — Andamiaje ◐ (4–6 h)

- Laravel 13 + Livewire 4 + Tailwind v4 + Filament + Pest + Playwright.
- Verificar el PHP, las extensiones (gd/imagick, intl) y el cron del Plesk destino.
- Repo, `.env.example`, CI básico (Pest + Pint).
- Enums, migraciones vacías del modelo de §5.3 del legajo.

**Hecho (local):** Laravel 12.70 + Livewire 4.4 + Filament 5.10 + Tailwind v4 + Pest 3.8, enums,
migraciones del §5.3, CI (`.github/workflows/ci.yml`), `npm audit` y `composer audit` en 0.
**Pendiente:** Playwright, verificar PHP/extensiones/cron del Plesk destino, `php artisan test`
en staging.
**Desvío:** se usa Laravel 12 y no 13 porque el entorno local tiene PHP 8.2. Subir a Laravel 13
al tener PHP 8.3+ en local y en Plesk (el plugin Livewire de Pest 4 también pide 8.3+).

**Cierre:** `php artisan test` en verde en local y en staging.

## Fase 1 — UX: flujos y wireframes ☑ (10–14 h)

Con `ux-flow-designer`. Se cubren estos flujos, cada uno con estados vacío, error y éxito:

**Beneficiario**
- Activación (solo CI → crear PIN de 4 dígitos)
- Login
- Inicio
- Ver misión y entregar (individual y por patrulla)
- Entrega rechazada → volver a entregar
- Reservar turno (incluido el choque de horario)
- Escanear asistencia
- Pagar en tienda (incluido el saldo insuficiente)
- Ranking (por equipo y Rovers)
- Historial

**Dirigente**
- Cola de validación → revisar → aprobar o rechazar
- Abrir sesión de asistencia → QR en pantalla completa → cerrar
- Marcar patrullas como cumplidas
- Aplicar penalización

**Tienda**
- Cobrar (monto → QR → pagado)
- Devolver

**Admin**
- Carga CSV de participantes
- Crear misión y crear actividad con turnos
- Ranking completo
- Reseteo de PIN

**Hecho:** `docs/ux-flows/` con 17 casos de uso aprobados, 53 diagramas Mermaid (mapa general más
flujo, estados y secuencia por caso, todos validados), **115 pantallas HTML clickeables** con sus estados
vacío, de error y de éxito (`wireframes/index.html`) y el documento maestro `UX-FLOWS.md`.
Decisiones: PIN escrito dos veces, 5 pestañas con Escanear al centro, admin sin wireframes (Filament).
**Aprobada** por el responsable del proyecto (los detalles se ven en el QA completo). **Hueco a decidir:**
cancelar un cobro de la tienda no existe aún en el dominio.

**Cierre:** wireframes aprobados por Leonardo.

## Fase 2 — Copywriting y sistema de diseño ◐ (10–14 h)

- Copy real de todas las pantallas y mensajes, en español paraguayo, tono scout y cercano.
- Microcopy de los errores críticos: saldo insuficiente, misión vencida, choque de horario,
  QR vencido, CI no cargado, PIN incorrecto.
- `/impeccable init`, tokens de color, tipografía y espaciado. Identidad propia del evento (D11).
- Diseño de alta fidelidad de las pantallas clave a 360 px.
- Motion con `emil-design-eng` para: pago confirmado, puntos acreditados y QR que rota.

**Identidad (decidida):** el estilo visual de jotajoti.info y el logo oficial (uso autorizado). Investigación,
paleta con contraste verificado y qué se adopta / adapta / no se toma: `docs/design/identidad-visual.md`.
**Hecho:** `/impeccable init` (`PRODUCT.md`), tokens en `resources/css/app.css` (color, Poppins
autohospedada, escala, formas, sombras, movimiento), 18 componentes Blade en `components/ui`, layout móvil,
24 pantallas con sus estados en `/diseno` (solo fuera de producción) y la guía de estilo, copy completo en
`lang/es/jota.php` (revisión: `docs/design/copy.md`), los tres momentos de movimiento (pago confirmado,
puntos acreditados, QR que rota) con alternativa para «reducir movimiento». 826 tests verdes, entre ellos
los del sistema de diseño (un `h1` por pantalla, ningún color ni peso de fuente literal, claves de copy
existentes, etiquetas accesibles).
**Auditoría a 360 px reales:** `docs/design/auditoria-360.js` (0 problemas en 49 pantallas y estados).
OJO: una ventana de escritorio no baja de ~500 px; para medir 360 usar emulación de dispositivo o iframes.
**Cambios respecto de los wireframes aprobados:** pestaña «Actividades» → «Agenda»; la barra del dirigente
pasa de 5 a 4 pestañas (el ranking de la unidad queda como enlace en la Cola) porque «Asistencia» y
«Cumplidas» no caben a 360 px.
**Pantallas reales (Livewire), primer tramo — hecho:** ingreso con CI+PIN (`/ingresar`), creación de PIN en dos
pasos (`/activar`), cierre de sesión (`POST /salir`), `/` que redirige según rol, middleware `rol`, Inicio del
participante (`/app`, con `wire:poll.30s`) y Ranking anónimo (`/app/ranking`). Misiones, escanear, agenda,
historial y las pantallas de dirigente y tienda muestran por ahora un aviso «muy pronto» (`pronto.blade.php`).
873 tests en verde, Pint limpio, `npm audit` y `composer audit` en 0; verificado en emulación de 360 px.
**Pantallas reales, segundo tramo — hecho:** misiones del participante (lista con estados, detalle, entrega con
texto y hasta 4 fotos), cola de validación y revisión del dirigente (aprobar, rechazar con motivo, «ya revisada»),
ranking de la unidad del dirigente y páginas de error en español (403, 404, 419, 429, 500, 503). 894 tests.
Las fotos se procesan en cola: en local hace falta `php artisan queue:work` (en Plesk, el cron de la Fase 11).
Siguen con aviso «muy pronto»: Agenda, Escanear, Historial, Asistencia, Cumplidas, Penalizar y toda la tienda.
**Pantallas reales, tercer tramo — hecho:** Agenda del participante (`/app/actividades`: actividades de su unidad con
turnos, lugares, reservar con avisos de lleno/duplicada/choque/turno empezado, reservas por patrulla) y Mis reservas
(cancelar con confirmación). El dirigente ya puede cerrar sesión. Siguen con «muy pronto»: Escanear, Historial,
Asistencia, Cumplidas, Penalizar y la tienda.
**Gestión de reservas — hecho:** el admin cancela reservas desde el panel («Reservas de turnos») y el dirigente desde
`/dirigente/reservas` (solo las de su unidad, con búsqueda y confirmación); ambos pueden cancelar aunque el turno ya
haya empezado, la persona no. 914 tests.
**Pantallas reales, cuarto tramo — hecho:** asistencia con QR rotativo (el dirigente abre la sesión, ve cuántos marcaron,
proyecta el QR en pantalla completa y cierra), escáner de QR del participante (cámara en el navegador con jsQR como
respaldo, solo sigue enlaces de este sitio con forma de asistencia o cobro), resultado de asistencia con saldos nuevos,
Cumplidas (marcar patrullas con confirmación) y Penalizar (persona o patrulla, anular con motivo). La cámara del navegador
solo funciona con HTTPS o en localhost; sin eso aparece la ayuda para usar la cámara del teléfono. 936 tests.
Siguen con «muy pronto»: Historial del participante y toda la tienda.
**Administrador en las pantallas de dirigente — hecho:** el admin usa todas las pantallas de `/dirigente` (cola, revisión,
reservas, asistencia, cumplidas, penalizar, ranking) sobre la unidad que elige en un selector (queda en su sesión);
el panel tiene el enlace «Vista de dirigente (celular)». 950 tests.
**Pantallas reales, quinto tramo — hecho (cierra las pantallas móviles):** tienda (cobrar con teclado y QR que espera el pago,
cancelar cobro —nuevo en el dominio—, devolver con tope, historial del puesto), confirmación y resultado de pago del
participante (también al escanear con la cámara del sistema) e Historial del participante (puntos y LisCoins, con filtro).
Se quitó el aviso «muy pronto»: ya no queda ninguna pantalla pendiente. 973 tests.
**Administrador en la tienda — hecho:** el admin también cobra, devuelve y ve su historial (`/tienda`); el panel tiene «Vista de tienda (celular)». 974 tests.
**Moneda renombrada:** «J-lis» pasa a llamarse **LisCoin** (plural LisCoins) en toda la interfaz, el panel, los mensajes y los CSV, con un ícono propio de moneda con flor de lis (`resources/svg/moneda.svg`, `jota-moneda`). En el código y la base se mantiene `jlis`. El legajo y los wireframes aprobados conservan el nombre viejo.
**Estaciones JOTA — reglas nuevas (16/10/2026):** siempre por equipo (los Rovers, como unidad), un solo equipo por horario (cupo 1, disponible u
ocupado) y una sola estación reservada por equipo a la vez hasta que termine. Migración `equipos_por_unidad` (reservas y cumplimientos pueden
ser de una unidad); cada Rover recibe los puntos y LisCoins completos. 1.023 tests y 50 pruebas de navegador.
**Retomar el destino tras ingresar o activar — hecho:** quien escanea un QR sin sesión pasa por Ingresar (o Activar, si es su primer ingreso)
y vuelve a ese QR. La raíz `/` redirige: sin sesión a `/ingresar`, con sesión a la pantalla de su rol. 1.029 tests.
**Lugar de talleres, estaciones y misiones — hecho (16/10/2026):** columna `lugar` (migración `agrega_lugar_a_actividades_y_misiones`), campo obligatorio en el
panel para actividades y opcional en misiones, y visible para el participante en la agenda, mis reservas, el inicio (próximo turno), la lista y el detalle de misión. Lo ya cargado sin lugar no
muestra nada. 1.036 tests y 50 pruebas de navegador.
**Talleres con turnos y cupos (16/10/2026):** el cupo de cada turno de taller es obligatorio (personas, o patrullas si el taller es por equipo); el taller se crea con
todos sus turnos en el mismo formulario; en los talleres la gente se **apunta** («Apuntarme») y en las estaciones se **reserva**; un taller se cursa en **un solo turno**
(persona o equipo; cuenta también un turno ya pasado); se pueden cursar varios talleres a la vez mientras no se pisen los horarios, sin la regla de «una a la vez» de
las estaciones. 1.049 tests y 51 pruebas de navegador.
**Registro de contactos JOTA y JOTI (16/10/2026):** dos tarjetas en el Inicio; JOTA = libro de guardia (suma a la patrulla; a cada Rover en los Rovers), JOTI = datos
del grupo + captura (suma a la persona); local por indicativo ZP / país Paraguay; cuatro puntajes que define el admin en el panel («Puntaje de contactos»); validación
por el dirigente de la unidad o el admin, con rechazo definitivo; capturas privadas, sin EXIF y purgables. Legajo §4.5 bis. Tests de dominio, de pantallas y de
concurrencia real, y 3 pruebas de navegador nuevas.
**Cuenta de prueba en producción (17/10/2026):** `jota:eliminar-usuario-de-prueba {ci}` (excepción acordada a «los movimientos no se borran»): simulacro, respaldo previo,
confirmación escribiendo el CI, una sola transacción, y revierte lo que la cuenta acreditó a su patrulla y compañeros; se niega si hubo tienda o si un compañero ya
gastó esos LisCoins, y verifica que los saldos coincidan con los movimientos. Clave SSH limitada (`deploy/ssh-comandos.sh`). 1.120 tests.
**Ranking con nombres para el dirigente (17/10/2026):** el dirigente ve los nombres de las patrullas de su unidad (Tropa y Caminantes) con integrantes y desglose, o de las
personas (Rovers); sin CI ni ids y con caché propia. El participante sigue anónimo (regla 8). Reemplaza a D6. 1.129 tests y 56 pruebas de navegador.
**Inscripción a talleres hasta que terminen (17/10/2026):** un taller admite anotarse mientras el turno no termine (la agenda dice «En curso: todavía podés sumarte»); la
estación JOTA sigue cerrando al empezar. Quien ya está anotado no puede cancelar un turno que empezó (solo el dirigente o el admin).
**Orden de misiones y de la agenda (17/10/2026):** las misiones van rechazadas → nuevas → en revisión → aprobadas → vencidas → cerradas. La agenda separa «Talleres» y
«Estaciones JOTA»: talleres en curso → con lugar → apuntado → lleno; estaciones reservada → disponible → en curso de mi equipo, y **no se muestran** las ocupadas por otro equipo
ni las bloqueadas ni las ya empezadas ajenas.
**Pendiente:** aprobación del diseño y del copy por Leonardo; fuente definitiva y tema del panel Filament (sigue con los colores por defecto).

**Cierre:** diseño aprobado.

## Fase 3 — Núcleo ◐ (16–22 h)

- Modelo completo y migraciones con índices y restricciones únicas.
- `Ledger\Puntos` y `Ledger\Jlis` con transacción y bloqueo. Comando `jota:reconciliar`.
- Acceso:
  - activación solo con el CI (crea su PIN de 4 dígitos)
  - login CI + PIN, sin bloqueo ni límite de intentos
  - reseteo por admin
- Policies por rol y unidad.
- Seeder de pruebas (CLAUDE.md §6).

**Hecho:** modelo y migraciones, `Ledger\Puntos`, `Ledger\Jlis`, `Reconciliador` + `jota:reconciliar`
(`--corregir` reescribe los saldos), acceso (`ActivarCuenta`, `LoginCiPin`, `ResetearPin`,
), policies por unidad, seeder de personas y estructura. Acceso simplificado por decisión de producto.
**Pendiente:** pantallas de login/activación (dependen de la Fase 2),
misiones y actividades de ejemplo en el seeder (Fases 4 y 5).

**Tests obligatorios:**
- Concurrencia de acreditación (10 acreditaciones simultáneas → saldo exacto).
- LisCoins nunca negativos.
- Una cuenta activada no se puede reactivar ni pisar el PIN.
- Equivocarse el PIN no bloquea la cuenta ni limita intentos.

## Fase 4 — Misiones y entregas ◐ (14–18 h)

- Visibilidad por unidad y hora de lanzamiento. Vencimiento y cierre manual.
- Entrega con texto + hasta 4 imágenes (cola: re-codificar, quitar EXIF, redimensionar).
- Reglas de bloqueo de entregas (pendiente, aprobada, rechazada + abierta).
- Aprobación:
  - individual → puntos individuales + LisCoins
  - patrulla → puntos de patrulla + ⌈P/n⌉ LisCoins a cada integrante
- Rechazo con motivo obligatorio.
- Cola de validación del dirigente, acotada a su unidad.

**Hecho:** `Mision`, `Entrega`, `EntregaImagen`; `EntregarMision`, `AprobarEntrega`,
`RechazarEntrega`, `CerrarMision`, `ColaValidacion`; job `ProcesarImagenEntrega` (Intervention
Image 3.11, GD: JPEG, máx. 1600 px, sin EXIF); ruta `/entregas/imagenes/{id}` con Policy en disco
`private`; misiones de ejemplo en el seeder. Aprobación protegida por bloqueo de fila y por un
índice único en base (una aprobada por misión y sujeto).
**Pendiente (dependen de la Fase 2):** pantallas Livewire (misiones, entrega, cola de validación,
revisar entrega). Para producción: worker de cola por cron en Plesk (`queue:work --stop-when-empty`).

**Tests:**
- Doble aprobación simultánea → una sola acreditación.
- Reparto con redondeo hacia arriba (50/3 = 17).
- El dirigente de otra unidad recibe 403.

## Fase 5 — Actividades ◐ (14–18 h)

- Actividades, turnos y cupos.
- Reservas individuales y por patrulla, con la regla de no superposición (D2, D3).
- Sesión de asistencia:
  - token HMAC por ventana de 60 s + 10 s de gracia
  - página pública firmada que rota el QR
  - cierre que invalida el enlace
- Registro de asistencia por escaneo con acreditación inmediata, una vez por actividad.
- Cumplimiento por equipo (cumplida / no cumplida) con acreditación a la patrulla.

**Hecho:** `Reservar` (cupo, no superposición individual y de patrulla, D2/D3/D5), `CancelarReserva`,
`TokenRotativo` (HMAC por ventana de 60 s + 10 s de gracia), `AbrirSesionAsistencia`,
`CerrarSesionAsistencia`, `RegistrarAsistencia`, `MarcarCumplimiento`, servicio compartido
`Ledger\AcreditarPatrulla` (también lo usa la aprobación de misiones). Pantalla pública firmada
`/asistencia/{sesion}` con QR en SVG (bacon-qr-code), endpoint `…/qr` y destino del QR
`…/registrar/{token}`. Actividades de ejemplo en el seeder.
**Pendiente (dependen de la Fase 2):** pantallas Livewire (actividades, reservas, escáner,
sesiones, cumplimientos) y el estilo de la pantalla pública del QR. El escáner de cámara en el
navegador (`html5-qrcode` o `BarcodeDetector`) se evalúa con la UI.

**Tests:**
- Cupo bajo concurrencia.
- Choque de horario individual vs. patrulla.
- Token vencido rechazado.
- Doble escaneo = un solo registro.

## Fase 6 — Tienda ◐ (8–12 h)

- Cobro: teclado → token de un solo uso, 2 min → QR → polling de estado.
- Pago: pantalla de confirmación del monto → transacción con verificación de saldo.
- Devolución por CI o nombre, con motivo.

**Hecho:** `CrearCobro` (token de 256 bits, vence a los 120 s), `PagarCobro`, `Devolver` (con
tabla `devoluciones` y tope: no se devuelve más de lo gastado en la tienda), `BuscarParticipante`
(CI exacto o nombre, mín. 3 letras), `QrCobro`, y las rutas `GET /cobros/{token}` y
`POST /cobros/{token}/pagar` (responden JSON hasta que llegue el diseño).
**Pendiente (dependen de la Fase 2):** pantallas de la tienda (teclado numérico, QR, confirmación
"Pagado por …", devoluciones, historial del puesto) y la pantalla de confirmación del monto del
participante. El polling de la tienda lee `Cobro::estado()`.

**Tests:**
- Saldo insuficiente.
- Doble pago del mismo cobro.
- Cobro vencido.
- Pago y devolución no tocan los puntos.

## Fase 7 — Ranking y penalizaciones ◐ (6–8 h)

- Ranking por equipo (patrulla + suma de individuales) y por Rovers (individual), con caché de
  30 s.
- Vista anónima del beneficiario y vista completa del admin.
- Penalizaciones a persona o patrulla, con motivo.

**Hecho:** `Ranking\RankingUnidad` (por equipo y Rovers, caché de 30 s, empates con posición
compartida, vistas separadas para beneficiario, dirigente y admin), `GET /ranking` (JSON, destino
del polling), `Penalizaciones\Penalizar` (persona o patrulla), `AnularPenalizacion` (movimiento
de signo contrario, una sola vez) y tabla `penalizaciones` con restricción de un solo objetivo.
**Regla para las pantallas:** las vistas Livewire del ranking deben pasar al componente SOLO el
arreglo que devuelve `vistaBeneficiario` / `vistaDirigente`; nunca modelos ni la caché del admin.
**Pendiente (dependen de la Fase 2):** pantallas de ranking y de penalizaciones del dirigente; el
ranking completo del admin se muestra en el panel Filament (Fase 8).

**Tests:**
- El payload del beneficiario no contiene nombres ni IDs de otros.
- La penalización no toca los LisCoins.

## Fase 8 — Panel admin ◐ (12–16 h) · puede correr en paralelo con 6 y 7

- Recursos Filament: grupos, unidades, patrullas, participantes, misiones, actividades y turnos.
- Importación CSV de participantes con reporte de errores.
- Validación global, movimientos, auditoría y rankings completos.
- Exportación CSV de rankings y saldos.
- Comando `jota:purgar-entregas` (borrado de imágenes post-evento).

**Hecho:** panel Filament 5 en `/admin` solo para el admin, con login por CI (`jota:crear-admin` crea
admins con contraseña larga). Recursos: grupos, unidades, patrullas, participantes (resetear PIN,
cerrar sesiones; los saldos solo se ven), misiones, talleres/estaciones con turnos, validación
global de entregas, movimientos de puntos, de patrullas y de LisCoins (solo lectura) y auditoría.
Páginas: importación CSV (validar sin guardar → importar, todo o nada, errores línea por línea) y
rankings completos. Exportación CSV de rankings y saldos (anti inyección de fórmulas). Auditoría de
configuración con `spatie/laravel-activitylog` 4.x. `jota:purgar-entregas` con simulacro y
confirmación.
**Decisiones:** las unidades, grupos, patrullas, misiones, actividades y turnos con historia no se
borran ni se les cambia lo que dejaría datos inconsistentes (modo de ranking, puntaje, modalidad,
horario con reservas). El login del panel conserva el límite de intentos de Filament (5/min), a
diferencia del de participantes: acá se administran todos los saldos.
**Pendiente:** tema visual del panel (Fase 2) y la fuente definitiva; `spatie/laravel-backup` se
configura en la Fase 11 (respaldo horario).

## Fase 9 — Seguridad ☑ (strix pendiente de credenciales) (6–8 h)

- strix contra staging.
- Revisión manual de:
  - Policies
  - rutas de imágenes
  - fuga de datos en el ranking
  - tokens de QR y cobro
  - rate limits
- Headers de seguridad, cookies seguras, `APP_DEBUG=false`.

**Cierre:** 0 hallazgos Críticos/Altos. **Hecho:** `docs/seguridad/informe-fase-9.md` (7 hallazgos corregidos, riesgos aceptados
listados). **Pendiente:** correr `strix` contra staging (necesita credenciales de un modelo y Docker).

## Fase 10 — QA ☑ (10–14 h)

- Playwright E2E de todos los flujos de la Fase 1, por rol.
- Prueba de carga a 300 usuarios simultáneos, con mezcla realista:
  - polling del ranking
  - entregas con imagen
  - escaneos de asistencia
  - pagos
- Revisión de accesibilidad AA en móvil.
- `jota:reconciliar` sin diferencias después de la prueba de carga.

**Hecho:** `docs/qa/informe-fase-10.md`: 46 pruebas de navegador (`npm run e2e`), axe-core AA sin violaciones, reflujo a 320 px,
prueba de carga de 300 usuarios sin errores y con integridad total (`npm run carga`). **Pendiente:** una pasada con lector de pantalla
(TalkBack / VoiceOver) por una persona.

## Fase 11 — Despliegue, ensayo y evento ☐ (8–12 h)

- Producción en Plesk con SSL, cron, cola y respaldo horario.
- Carga real de participantes, **una semana antes**.
- **Ensayo general con dirigentes** en condiciones reales, si es posible en el lugar para medir
  la señal.
- Congelamiento de despliegues desde 48 h antes hasta el cierre del evento.
- Durante el evento: responsable técnico en el lugar, monitoreo y `jota:reconciliar` cada hora.
- Cierre:
  - exportación de resultados
  - reconciliación final
  - purga de imágenes según lo defina el grupo
  - documentación del núcleo reutilizable

---

## Decisiones abiertas que bloquean fases

| Decisión | Bloquea |
|---|---|
| D1 Fecha del evento | Calendario de todo el plan |
| D11 Identidad visual | Fase 2 |
| D2 / D3 Reservas por patrulla | Fase 5 (se avanza con el valor por defecto) |
| D13 Dominio | Fase 11 |
