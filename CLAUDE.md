# CLAUDE.md — Plataforma JOTA-JOTI 2026

Contexto permanente del repo para Claude Code y los subagentes. Leer completo antes de tocar
código. El alcance funcional está en `legajo-plataforma-jota-joti-2026.md` (§4 y §5) y las fases
en `PLAN.md`. Si algo de este archivo contradice al legajo, manda el legajo y se avisa.

---

## 1. Qué es

Aplicación web móvil de juego para el JOTA-JOTI 2026 del Grupo Scout General Genes (con el
G.S. General Aquino).

- Los participantes reciben **misiones** por horario, entregan texto o imágenes y un dirigente
  valida.
- Reservan turnos en **talleres y estaciones JOTA** y marcan asistencia con un **QR rotativo**.
- Acumulan **puntos** (ranking) y **LisCoins** (moneda oficial del evento; antes «J-lis», en el código y la base sigue llamándose `jlis`), y pagan en la **tienda** escaneando un
  **QR de cobro**.

Roles: `beneficiario`, `dirigente` (acotado a su unidad), `tienda`, `admin`.
Unidades: Tropa y Caminantes (ranking por equipo), Rovers (ranking individual).

---

## 2. Reglas no negociables

1. **Los registros contables son de solo agregar.**
   - `mov_puntos_individuales`, `mov_puntos_patrulla` y `mov_jlis` nunca se actualizan ni se
     borran.
   - Una corrección es un movimiento nuevo de signo contrario, con motivo.
2. **Todo cambio de saldo va por un servicio de dominio**, nunca con un `update` suelto.
   - Servicios: `App\Domain\Ledger\Puntos` y `App\Domain\Ledger\Jlis`.
   - Cada operación va en `DB::transaction` + `lockForUpdate` sobre la fila del saldo, inserta el
     movimiento y actualiza la columna cacheada.
3. **Idempotencia por base de datos.**
   - Restricciones únicas para: aprobación de una entrega, asistencia (`actividad_id`,
     `user_id`), cumplimiento (`actividad_id`, `patrulla_id`) y pago de cobro.
   - Capturar `UniqueConstraintViolationException` y responder como "ya registrado", no como
     error.
4. **Puntos y LisCoins son independientes.**
   - Penalizaciones → solo puntos.
   - Tienda → solo LisCoins.
   - Ver la tabla de acreditación en el legajo §4.5.
5. **LisCoins por patrulla = `(int) ceil($puntos / $integrantes)`**, por integrante activo al
   momento de acreditar. Los LisCoins son siempre de la persona.
6. **El saldo de LisCoins nunca es negativo.** Los puntos sí pueden serlo (D14).
7. **Alcance del dirigente con Policies.**
   - Un dirigente solo actúa sobre su `unidad_id`; el admin, sobre todo.
   - Nunca filtrar solo en la vista.
8. **El ranking del beneficiario es anónimo.**
   - Ni nombres, ni IDs, ni marca de la posición propia en el HTML, en el payload de Livewire o
     en atributos `wire:*`.
   - Exponer solo `posicion` y `puntaje`.
9. **Login simple: CI + PIN de 4 dígitos, sin bloqueo ni límite de intentos.**
   - Decisión de producto: que entren rápido y fácil. Primer ingreso solo con el CI (crea su PIN).
   - No agregar bloqueo, rate limit ni captcha al login sin pasarlo por el legajo (R3, D8).
10. **Imágenes:**
    - Disco `private`, validadas por MIME real, re-codificadas y sin EXIF.
    - Servidas solo por una ruta con Policy.
    - Nunca en `public/`.
11. **Hora del servidor:** `America/Asuncion`. Toda comparación de lanzamiento, vencimiento o
    turno usa `now()` del servidor, nunca la hora del cliente.
12. **Datos personales mínimos:** CI, nombre, grupo, unidad y patrulla. No agregar campos
    personales sin pasar por el legajo.
13. **Estaciones JOTA** (regla del grupo, 16/10/2026):
    - Son siempre **por equipo**: la patrulla en Tropa y Caminantes; en los Rovers, que no se arman por patrulla, el equipo es **toda la unidad**.
    - Cada horario es de **un solo equipo** (cupo 1): está disponible u ocupado.
    - Un equipo reserva **una sola estación a la vez**, a cualquier hora, hasta que termine la que reservó (o la cancele).
    - Al cumplirla: patrulla → puntos a la patrulla y ⌈P/n⌉ LisCoins a cada integrante; Rovers → **cada Rover** recibe +P puntos y +P LisCoins.
    - Los talleres no tienen esta regla (pueden ser individuales o por equipo, con cupo de personas).
14. **Copy en español paraguayo**, tono cercano y scout. Nada de textos en inglés en la UI.

---

## 3. Stack

| Capa | Elección |
|---|---|
| Framework | Laravel 13, PHP 8.3+ |
| Base de datos | MySQL 8 (InnoDB, `utf8mb4`) |
| UI móvil | Livewire 4 + Alpine.js + Tailwind CSS v4 |
| Panel admin | Filament (última estable compatible con Laravel 13 — verificar con context7) |
| Assets | Vite, fuentes autohospedadas |
| Tiempo real | Polling de Livewire (`wire:poll`). **Sin WebSockets** |
| Colas | Driver `database` (procesamiento de imágenes) |
| Caché | `database` o `file` (no asumir Redis en Plesk) |
| QR (generar) | `bacon/bacon-qr-code` en servidor (SVG), o JS en la página pública rotativa |
| QR (escanear) | Librería JS de escaneo por cámara (evaluar `html5-qrcode` o `BarcodeDetector` nativo con respaldo) |
| Imágenes | `intervention/image` |
| Auditoría | Los propios movimientos + `spatie/laravel-activitylog` para cambios de configuración |
| Respaldos | `spatie/laravel-backup` |
| Tests | Pest (unit + feature), Playwright (E2E) |
| Despliegue | Plesk de webparaguay |

Versiones fijadas por **PHP 8.2** (se pueden subir al pasar a PHP 8.3/8.4 y Laravel 13): Laravel 12,
Filament 5, Livewire 4, `intervention/image` 3.11 (la 4.x pide 8.3), `spatie/laravel-activitylog` 4.12
(la 5.x pide 8.4), Pest 3 (el plugin de Livewire de Pest pide 8.3+).

Toda librería nueva se verifica con **context7** (versión y compatibilidad) antes de instalarla.
Toda skill o herramienta nueva para el entorno pasa por **skill-security-auditor**.

---

## 4. Estructura sugerida

```
app/
  Domain/
    Ledger/          Puntos.php, Jlis.php, Reconciliador.php
    Misiones/        EntregarMision.php, AprobarEntrega.php, RechazarEntrega.php
    Actividades/     Reservar.php, AbrirSesionAsistencia.php, RegistrarAsistencia.php,
                     MarcarCumplimiento.php, TokenRotativo.php
    Tienda/          CrearCobro.php, PagarCobro.php, Devolver.php
    Ranking/         RankingUnidad.php
    Acceso/          ActivarCuenta.php, LoginCiPin.php
  Enums/             Rol, Modalidad, ModoRanking, EstadoEntrega, TipoActividad
  Livewire/
    Beneficiario/    Inicio, Misiones, Mision, Actividades, Escanear, Ranking, Historial
    Dirigente/       ColaValidacion, RevisarEntrega, Sesiones, Cumplimientos, Penalizaciones
    Tienda/          Cobrar, Devolver, Historial
  Filament/          Recursos del admin
  Policies/
routes/web.php       /app, /dirigente, /tienda, /asistencia/{sesion}, /admin
```

Las acciones de dominio son clases invocables y testeables, sin lógica en componentes Livewire
ni en recursos de Filament.

---

## 5. Herramientas y cuándo usarlas

| Herramienta | Uso en este repo |
|---|---|
| `ux-flow-designer` | Fase 1: flujos por rol y wireframes móviles |
| `impeccable` | Fase 2: sistema de diseño. Correr `/impeccable init` al inicio. **Nunca colores ni tipografías literales en componentes: todo por tokens** |
| `emil-design-eng` | Microinteracciones: confirmación de pago, acreditación de puntos, QR que rota |
| `context7` | Antes de usar cualquier API de Laravel 13, Livewire 4, Filament o Tailwind v4 |
| `playwright-cli` | E2E por rol. Solo credenciales de prueba del seeder |
| `strix` | Fase 9, solo contra el entorno de staging propio |
| Subagentes de The Agency | Revisión de código, testing y seguridad en paralelo |

---

## 6. Datos de prueba

`DatabaseSeeder` crea:
- 2 grupos y 3 unidades.
- 4 patrullas por unidad con equipo (de 3 a 7 integrantes, a propósito desparejas).
- 6 rovers.
- 1 dirigente por unidad, 1 usuario tienda y 1 admin.
- Misiones y actividades de ejemplo con lanzamientos en el pasado y en el futuro.

Las credenciales de prueba van solo en el seeder y en `.env.example`; no se repiten en chat ni
en commits de documentación.

---

## 7. Definición de terminado (por funcionalidad)

- Regla de negocio cubierta con test Pest, incluido el caso de concurrencia cuando toca saldo,
  cupo o idempotencia.
- Policy probada: el dirigente de otra unidad recibe 403.
- Vista móvil revisada a 360 px.
- Copy en español, sin textos de relleno.
- Sin consultas N+1 en listados (revisar con la barra de debug en local).
