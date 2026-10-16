# Informe de QA — Fase 10

Fecha: 16/10/2026. Qué se probó, cómo repetirlo y qué resultó.

## 1. Pruebas de punta a punta con navegador real (Playwright)

**50 pruebas, todas en verde** (`npm run e2e`, ~2 min). Corren un Chromium con el tamaño de un celular (360 × 740, como un Pixel 5)
contra un servidor y una base propios de pruebas (`jotajoti_e2e`, que se recrea en cada corrida); nunca tocan la base de desarrollo.

| Archivo | Qué recorre |
|---|---|
| `01-acceso` | Crear el PIN con solo el CI y entrar con la bienvenida; PIN incorrecto repetido 6 veces sin bloqueo; CI no cargado; CI sin PIN que prueba por «Ingresar» pasa a crearlo; cada rol cae en su pantalla; volver a la pantalla pedida después de ingresar; cerrar sesión. |
| `02-misiones` | Entregar con texto y foto → el dirigente la ve con la foto y aprueba → puntos y LisCoins en el saldo; rechazo con motivo obligatorio y nueva entrega; misión por patrulla entregada por un integrante y vista por los demás; entrega vacía. |
| `03-agenda` | Reservar, ver «Mis reservas», cancelar con confirmación; aviso de choque de horario; turno con cupo lleno; el dirigente cancela la reserva de otra persona y no ve las de otra unidad. |
| `04-asistencia` | El dirigente abre la sesión y ve el QR; la pantalla pública que se proyecta; el participante escanea (suma una sola vez); escanear de nuevo avisa «ya registrada»; al cerrar la sesión el QR deja de servir; código vencido o inventado. |
| `05-cumplidas-y-penalizaciones` | Marcar patrulla cumplida (acredita una sola vez); corregir «no cumplida»; penalizar resta puntos y no toca LisCoins; anular con motivo devuelve; el dirigente no encuentra a gente de otra unidad. |
| `06-tienda` | La tienda genera el cobro con el teclado y el QR; la persona paga y la tienda se entera sola; sin saldo avisa cuánto falta; cancelar el cobro invalida el QR; devolver respeta el tope; historial del puesto y de la persona. |
| `07-admin` | Panel de administración (todas las secciones, nombre nuevo de la moneda); usar las pantallas de dirigente sobre la unidad elegida y la tienda; los demás roles no entran. |
| `08-seguridad` | Encabezados y política de contenido (un script externo queda bloqueado); POST sin token → 419; cookie de sesión no legible; ids ajenos → 403; fotos ajenas → 403; ranking sin nombres ni CI; errores sin detalles internos. |
| `09-accesibilidad` | axe-core (WCAG 2.0/2.1/2.2 A y AA) sobre **todas** las pantallas de cada rol, la pantalla pública del QR y la página de error, más: sin desborde horizontal y objetivos táctiles de ≥ 44 px. |
| `10-reflujo` | A 320 px de ancho y con el texto al 200 %: sin desplazamiento horizontal y nada tapado por la barra de abajo. |

Además hay **1.023 tests de Pest** (reglas de negocio, policies, concurrencia con procesos reales, seguridad) en verde.

## 2. Accesibilidad AA en el celular

- **Automática (axe-core, WCAG 2.2 AA):** 0 violaciones en las ~30 pantallas y estados revisados (también sin violaciones «de mejores
  prácticas» en las pantallas de muestra). Una sola regla queda «incompleta» (contraste de las medallas del ranking sobre fondo de color):
  se verificó a mano con los pares del sistema de diseño (gris 900 sobre amarillo 8,7:1 y sobre cian 10,5:1).
- **Objetivos táctiles:** ningún botón ni enlace visible mide menos de 44 px (la prueba lo exige en cada pantalla).
- **Reflujo y texto grande:** sin desborde a 320 px y con el texto al 200 %.
- **Verificado en el diseño y el código:** idioma `es` en cada página, un solo `h1` por pantalla, título único por pantalla, etiquetas en
  todos los campos, errores con `role="alert"` y enlazados al campo, foco visible de 3 px, enlace «Saltar al contenido», estados que no
  dependen solo del color (ícono + texto), `prefers-reduced-motion` respetado (las animaciones se vuelven un fundido corto), cuerpo de
  texto de 16 px o más, contraste AA en todos los pares de color del sistema (`docs/design/identidad-visual.md`).
- **No cubierto, a revisar con una persona usando lector de pantalla** (TalkBack en Android, VoiceOver en iPhone) antes del evento:
  el orden de lectura del teclado numérico de la tienda y los avisos que aparecen solos (reserva hecha, pago confirmado).

## 3. Prueba de carga: 300 usuarios

Script: `npm run carga` (`tests/carga/carga.mjs`). Habla HTTP real (incluido el protocolo de Livewire, la subida de fotos y los pagos)
contra un servidor y una base propios (`jotajoti_carga`). Informes completos: `informe-carga.md` y `informe-carga-estres.md`.

**Escenario realista** — 300 participantes + 3 dirigentes + 1 tienda durante 4 minutos, 24 procesos PHP, sondeo cada 30 s como en la app:

- 4.300 peticiones, **0 errores** de servidor (ni 5xx, ni 419, ni cortes), 0 errores en el registro.
- Tiempos: sondeos (Inicio, Ranking, Agenda, Misiones) **p50 ≈ 45 ms**; escanear el QR **p50 53 ms, p99 99 ms**; pagar **p50 38 ms**;
  enviar una entrega **p50 41 ms**; subir una foto de 1,5 MB **p50 42 ms**; aprobar **p50 48 ms**.
- Lo más lento es el **ingreso** (bcrypt): con los 300 entrando en 20 s, **p50 ≈ 1 s**. Es un pico de arranque; por eso la plantilla de
  producción usa `BCRYPT_ROUNDS=10` (4 veces menos trabajo por ingreso).
- Las fotos se procesan en la cola y terminaron **1 s** después de la última subida.

**Estrés** — mismo escenario con sondeo cada 3 s (**10 veces** más pedidos que la app real, 93 peticiones por segundo): 17 cortes de
conexión de 13.844 (0,12 %) en el servidor incorporado de PHP, que se satura; 0 errores de la aplicación. Apache/PHP-FPM de Plesk con
más procesos lo atiende sin problema.

**Integridad después de ambas corridas (lo más importante):**
- Asistencias en la base = escaneos exitosos; **ninguna asistencia duplicada**.
- Cobros pagados = pagos exitosos; los **dobles toques en «Pagar» no cobraron dos veces**; ningún cobro con más de un descuento.
- Entregas, aprobaciones y reservas en la base = las que hicieron los usuarios; todas las fotos procesadas.
- **Ningún saldo negativo** y **`jota:reconciliar` sin diferencias.**

## 4. Hallazgos de esta fase

| Hallazgo | Estado |
|---|---|
| El servidor anunciaba la versión de PHP (`X-Powered-By`). | Corregido (se quita en cada respuesta). |
| Con el ingreso masivo (bcrypt con 12 vueltas) los primeros segundos son lentos. | Mitigado: `BCRYPT_ROUNDS=10` en la plantilla de producción. |
| Los avisos de tipo «Posible adivinación de PIN» se escriben en el mismo registro que los errores. | Aceptado: se buscan por el texto. |
| Ningún defecto funcional nuevo: los fallos que mostraron las pruebas al armarlas eran de las propias pruebas (selectores, datos de partida). | — |

## 5. Para repetirlo

```bash
npm run e2e          # pruebas de navegador (crea y usa la base jotajoti_e2e)
npm run e2e:informe  # abre el informe HTML con capturas
npm run carga        # 300 usuarios, 4 minutos (crea y usa la base jotajoti_carga)
node tests/carga/carga.mjs --usuarios 300 --segundos 150 --sondeo 3 --salida docs/qa/informe-carga-estres.md   # estrés
vendor/bin/pest      # reglas de negocio, policies, concurrencia, seguridad
```
