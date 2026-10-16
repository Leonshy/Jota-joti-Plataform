# UX Flows — Plataforma JOTA-JOTI 2026

Documento maestro de la **Fase 1** (flujos y wireframes). Es la entrega para la **Fase 2**
(copywriting y sistema de diseño). Cierra cuando Leonardo apruebe los wireframes.

- Casos de uso: [use-cases.md](use-cases.md)
- Mapa de pantallas: [diagrams/screen-map.md](diagrams/screen-map.md)
- Índice de diagramas: [diagrams/INDEX.md](diagrams/INDEX.md)
- **Prototipo clickeable: abrir [wireframes/index.html](wireframes/index.html) en el navegador**

## Decisiones tomadas con el equipo

| Tema | Decisión |
|---|---|
| Casos de uso | Aprobados: 17 móviles (participante, dirigente, tienda) + admin documentado |
| Crear el PIN | Se escribe **dos veces** para evitar un PIN mal tipeado |
| Navegación del participante | **5 pestañas** con **Escanear** al centro: Inicio · Misiones · Escanear · Actividades · Ranking |
| Admin | Sin wireframes: ya está construido en Filament |
| Login | CI + PIN de 4 dígitos, sin bloqueo ni límite de intentos |

## Mapa general de pantallas

Ver [diagrams/screen-map.md](diagrams/screen-map.md).

## Inventario de pantallas

115 pantallas HTML (principales y sus estados vacío, de error y de éxito). Detalle por pantalla en [wireframes/INDEX.md](wireframes/INDEX.md).

| Rol | Pantallas | Entrada |
|---|---|---|
| Participante y acceso | 63 | [ingresar.html](wireframes/ingresar.html) |
| Dirigente | 38 | [d-cola.html](wireframes/d-cola.html) |
| Tienda | 14 | [t-cobrar.html](wireframes/t-cobrar.html) |

## Casos de uso y diagramas

| Caso | Pantalla de entrada | Flujo | Estados | Secuencia |
|---|---|---|---|---|
| **UC-01** · Activar la cuenta | [activar-ci.html](wireframes/activar-ci.html) | [flow](diagrams/uc-01-activar-cuenta/flow.md) | [states](diagrams/uc-01-activar-cuenta/states.md) | [sequence](diagrams/uc-01-activar-cuenta/sequence.md) |
| **UC-02** · Ingresar | [ingresar.html](wireframes/ingresar.html) | [flow](diagrams/uc-02-ingresar/flow.md) | [states](diagrams/uc-02-ingresar/states.md) | [sequence](diagrams/uc-02-ingresar/sequence.md) |
| **UC-03** · Inicio | [inicio.html](wireframes/inicio.html) | [flow](diagrams/uc-03-inicio/flow.md) | [states](diagrams/uc-03-inicio/states.md) | [sequence](diagrams/uc-03-inicio/sequence.md) |
| **UC-04** · Ver misiones y entregar | [misiones.html](wireframes/misiones.html) | [flow](diagrams/uc-04-entregar-mision/flow.md) | [states](diagrams/uc-04-entregar-mision/states.md) | [sequence](diagrams/uc-04-entregar-mision/sequence.md) |
| **UC-05** · Volver a entregar tras un rechazo | [mision-rechazada.html](wireframes/mision-rechazada.html) | [flow](diagrams/uc-05-reentregar/flow.md) | [states](diagrams/uc-05-reentregar/states.md) | [sequence](diagrams/uc-05-reentregar/sequence.md) |
| **UC-06** · Reservar un turno | [actividades.html](wireframes/actividades.html) | [flow](diagrams/uc-06-reservar-turno/flow.md) | [states](diagrams/uc-06-reservar-turno/states.md) | [sequence](diagrams/uc-06-reservar-turno/sequence.md) |
| **UC-07** · Escanear asistencia | [escanear.html](wireframes/escanear.html) | [flow](diagrams/uc-07-escanear-asistencia/flow.md) | [states](diagrams/uc-07-escanear-asistencia/states.md) | [sequence](diagrams/uc-07-escanear-asistencia/sequence.md) |
| **UC-08** · Pagar en la tienda | [confirmar-pago.html](wireframes/confirmar-pago.html) | [flow](diagrams/uc-08-pagar-tienda/flow.md) | [states](diagrams/uc-08-pagar-tienda/states.md) | [sequence](diagrams/uc-08-pagar-tienda/sequence.md) |
| **UC-09** · Ranking | [ranking.html](wireframes/ranking.html) | [flow](diagrams/uc-09-ranking/flow.md) | [states](diagrams/uc-09-ranking/states.md) | [sequence](diagrams/uc-09-ranking/sequence.md) |
| **UC-10** · Historial | [historial.html](wireframes/historial.html) | [flow](diagrams/uc-10-historial/flow.md) | [states](diagrams/uc-10-historial/states.md) | [sequence](diagrams/uc-10-historial/sequence.md) |
| **UC-11** · Cola de validación | [d-cola.html](wireframes/d-cola.html) | [flow](diagrams/uc-11-cola-validacion/flow.md) | [states](diagrams/uc-11-cola-validacion/states.md) | [sequence](diagrams/uc-11-cola-validacion/sequence.md) |
| **UC-12** · Sesión de asistencia y QR | [d-asistencia.html](wireframes/d-asistencia.html) | [flow](diagrams/uc-12-sesion-asistencia/flow.md) | [states](diagrams/uc-12-sesion-asistencia/states.md) | [sequence](diagrams/uc-12-sesion-asistencia/sequence.md) |
| **UC-13** · Cumplimientos por equipo | [d-cumplimientos.html](wireframes/d-cumplimientos.html) | [flow](diagrams/uc-13-cumplimientos/flow.md) | [states](diagrams/uc-13-cumplimientos/states.md) | [sequence](diagrams/uc-13-cumplimientos/sequence.md) |
| **UC-14** · Penalizaciones | [d-penalizaciones.html](wireframes/d-penalizaciones.html) | [flow](diagrams/uc-14-penalizaciones/flow.md) | [states](diagrams/uc-14-penalizaciones/states.md) | [sequence](diagrams/uc-14-penalizaciones/sequence.md) |
| **UC-15** · Cobrar | [t-cobrar.html](wireframes/t-cobrar.html) | [flow](diagrams/uc-15-cobrar/flow.md) | [states](diagrams/uc-15-cobrar/states.md) | [sequence](diagrams/uc-15-cobrar/sequence.md) |
| **UC-16** · Devolver | [t-devolver.html](wireframes/t-devolver.html) | [flow](diagrams/uc-16-devolver/flow.md) | [states](diagrams/uc-16-devolver/states.md) | [sequence](diagrams/uc-16-devolver/sequence.md) |
| **UC-16b** · Historial del puesto | [t-historial.html](wireframes/t-historial.html) | [flow](diagrams/uc-16b-historial-puesto/flow.md) | [states](diagrams/uc-16b-historial-puesto/states.md) | [sequence](diagrams/uc-16b-historial-puesto/sequence.md) |
| **UC-17** · Admin (Filament) | — | [flow](diagrams/uc-17-admin/flow.md) | — | — |

## Enlaces del prototipo clickeable

Por cada pantalla, a qué otras pantallas se puede ir. Las que terminan en un estado (`-vacio`, `-error`…) se alcanzan desde la franja amarilla de la pantalla principal.

### Participante y acceso

| Desde | Hacia |
|---|---|
| [acceso-denegado.html](wireframes/acceso-denegado.html) | inicio.html |
| [activar-ci-error.html](wireframes/activar-ci-error.html) | activar-ci.html, activar-pin.html, ingresar.html |
| [activar-ci-ya.html](wireframes/activar-ci-ya.html) | activar-ci.html, ingresar.html |
| [activar-ci.html](wireframes/activar-ci.html) | activar-ci-error.html, activar-ci-ya.html, activar-pin.html, ingresar.html |
| [activar-pin-error.html](wireframes/activar-pin-error.html) | activar-ci.html, activar-pin.html, inicio-bienvenida.html |
| [activar-pin.html](wireframes/activar-pin.html) | activar-ci.html, activar-pin-error.html, inicio-bienvenida.html |
| [actividad-empezo.html](wireframes/actividad-empezo.html) | actividades.html |
| [actividad-equipo.html](wireframes/actividad-equipo.html) | actividad-sin-patrulla.html, actividades.html, reserva-choque-equipo.html, reserva-ok.html |
| [actividad-sin-patrulla.html](wireframes/actividad-sin-patrulla.html) | actividad-equipo.html, actividades.html |
| [actividad.html](wireframes/actividad.html) | actividad-empezo.html, actividades.html, reserva-choque.html, reserva-lleno.html, reserva-ok.html |
| [actividades-vacio.html](wireframes/actividades-vacio.html) | actividades.html, escanear.html, inicio.html, misiones.html, ranking.html |
| [actividades.html](wireframes/actividades.html) | actividad-equipo.html, actividad.html, actividades-vacio.html, actividades.html, escanear.html, inicio.html, mis-reservas.html, misiones.html, ranking.html |
| [ayuda-pin.html](wireframes/ayuda-pin.html) | activar-ci.html, ingresar.html |
| [cancelar-reserva.html](wireframes/cancelar-reserva.html) | mis-reservas-vacio.html, mis-reservas.html |
| [confirmar-pago-saldo.html](wireframes/confirmar-pago-saldo.html) | confirmar-pago.html, inicio.html |
| [confirmar-pago.html](wireframes/confirmar-pago.html) | confirmar-pago-saldo.html, inicio.html, pago-no-existe.html, pago-ok.html, pago-vencido.html, pago-ya-pagado.html |
| [entrega-enviada.html](wireframes/entrega-enviada.html) | mision-pendiente.html, misiones.html |
| [entregar-error.html](wireframes/entregar-error.html) | entrega-enviada.html, entregar-vencida.html, entregar.html, mision.html |
| [entregar-subiendo.html](wireframes/entregar-subiendo.html) | entregar.html, mision.html |
| [entregar-vencida.html](wireframes/entregar-vencida.html) | mision.html, misiones.html |
| [entregar.html](wireframes/entregar.html) | entrega-enviada.html, entregar-error.html, entregar-subiendo.html, mision.html |
| [escanear-no-reconocido.html](wireframes/escanear-no-reconocido.html) | escanear.html |
| [escanear-sin-camara.html](wireframes/escanear-sin-camara.html) | escanear.html |
| [escanear.html](wireframes/escanear.html) | actividades.html, confirmar-pago.html, escanear-no-reconocido.html, escanear-sin-camara.html, escanear.html, inicio.html, misiones.html, ranking.html, resultado-asistencia-ok.html, resultado-asistencia-vencido.html, resultado-asistencia-ya.html |
| [historial-vacio.html](wireframes/historial-vacio.html) | inicio.html, misiones.html |
| [historial.html](wireframes/historial.html) | historial-vacio.html, historial.html, inicio.html |
| [ingresar-ci-error.html](wireframes/ingresar-ci-error.html) | ayuda-pin.html, ingresar.html, inicio.html |
| [ingresar-error.html](wireframes/ingresar-error.html) | activar-ci.html, ingresar.html, inicio.html |
| [ingresar.html](wireframes/ingresar.html) | activar-ci.html, ayuda-pin.html, d-cola.html, ingresar-ci-error.html, ingresar-error.html, inicio.html, sesion-vencida.html, t-cobrar.html |
| [inicio-bienvenida.html](wireframes/inicio-bienvenida.html) | actividades.html, escanear.html, inicio.html, misiones.html, ranking.html |
| [inicio-vacio.html](wireframes/inicio-vacio.html) | actividades.html, escanear.html, inicio.html, misiones.html, ranking.html |
| [inicio.html](wireframes/inicio.html) | actividades.html, escanear.html, historial.html, ingresar.html, inicio-bienvenida.html, inicio-vacio.html, inicio.html, mis-reservas.html, misiones.html, ranking.html, sin-conexion.html |
| [mis-reservas-vacio.html](wireframes/mis-reservas-vacio.html) | actividades.html |
| [mis-reservas.html](wireframes/mis-reservas.html) | actividades.html, cancelar-reserva.html, mis-reservas-vacio.html |
| [mision-aprobada.html](wireframes/mision-aprobada.html) | mision-aprobada.html, mision-patrulla.html, mision-pendiente.html, mision-rechazada.html, mision-vencida.html, mision.html, misiones.html |
| [mision-patrulla-libre.html](wireframes/mision-patrulla-libre.html) | entregar.html, misiones.html |
| [mision-patrulla.html](wireframes/mision-patrulla.html) | mision-patrulla-libre.html, misiones.html |
| [mision-pendiente.html](wireframes/mision-pendiente.html) | mision-aprobada.html, mision-patrulla.html, mision-pendiente.html, mision-rechazada.html, mision-vencida.html, mision.html, misiones.html |
| [mision-rechazada.html](wireframes/mision-rechazada.html) | entregar.html, mision-aprobada.html, mision-patrulla.html, mision-pendiente.html, mision-rechazada.html, mision-vencida.html, mision.html, misiones.html |
| [mision-vencida.html](wireframes/mision-vencida.html) | mision-aprobada.html, mision-patrulla.html, mision-pendiente.html, mision-rechazada.html, mision-vencida.html, mision.html, misiones.html |
| [mision.html](wireframes/mision.html) | entregar.html, mision-aprobada.html, mision-patrulla.html, mision-pendiente.html, mision-rechazada.html, mision-vencida.html, mision.html, misiones.html |
| [misiones-vacio.html](wireframes/misiones-vacio.html) | actividades.html, escanear.html, inicio.html, misiones.html, ranking.html |
| [misiones.html](wireframes/misiones.html) | actividades.html, escanear.html, inicio.html, mision-aprobada.html, mision-patrulla.html, mision-pendiente.html, mision-rechazada.html, mision-vencida.html, mision.html, misiones-vacio.html, misiones.html, ranking.html |
| [pago-no-existe.html](wireframes/pago-no-existe.html) | escanear.html |
| [pago-ok.html](wireframes/pago-ok.html) | historial.html, inicio.html |
| [pago-vencido.html](wireframes/pago-vencido.html) | inicio.html |
| [pago-ya-pagado.html](wireframes/pago-ya-pagado.html) | inicio.html |
| [ranking-rover.html](wireframes/ranking-rover.html) | actividades.html, escanear.html, inicio.html, misiones.html, ranking.html |
| [ranking-sin-conexion.html](wireframes/ranking-sin-conexion.html) | actividades.html, escanear.html, inicio.html, misiones.html, ranking.html |
| [ranking-vacio.html](wireframes/ranking-vacio.html) | actividades.html, escanear.html, inicio.html, misiones.html, ranking.html |
| [ranking.html](wireframes/ranking.html) | actividades.html, escanear.html, inicio.html, misiones.html, ranking-rover.html, ranking-sin-conexion.html, ranking-vacio.html, ranking.html |
| [reserva-choque-equipo.html](wireframes/reserva-choque-equipo.html) | actividad-equipo.html |
| [reserva-choque.html](wireframes/reserva-choque.html) | actividad.html, mis-reservas.html |
| [reserva-lleno.html](wireframes/reserva-lleno.html) | actividad.html |
| [reserva-ok.html](wireframes/reserva-ok.html) | actividades.html, mis-reservas.html |
| [resultado-asistencia-cerrada.html](wireframes/resultado-asistencia-cerrada.html) | inicio.html |
| [resultado-asistencia-ok.html](wireframes/resultado-asistencia-ok.html) | escanear.html, inicio.html |
| [resultado-asistencia-unidad.html](wireframes/resultado-asistencia-unidad.html) | inicio.html |
| [resultado-asistencia-vencido.html](wireframes/resultado-asistencia-vencido.html) | escanear.html |
| [resultado-asistencia-ya.html](wireframes/resultado-asistencia-ya.html) | inicio.html, resultado-asistencia-cerrada.html, resultado-asistencia-unidad.html |
| [sesion-vencida.html](wireframes/sesion-vencida.html) | ingresar.html |
| [sin-conexion.html](wireframes/sin-conexion.html) | inicio.html |

### Dirigente

| Desde | Hacia |
|---|---|
| [d-anulada.html](wireframes/d-anulada.html) | d-penalizaciones.html |
| [d-anular-ya.html](wireframes/d-anular-ya.html) | d-penalizaciones.html |
| [d-anular.html](wireframes/d-anular.html) | d-anulada.html, d-anular-ya.html, d-penalizaciones.html |
| [d-aprobada-patrulla.html](wireframes/d-aprobada-patrulla.html) | d-cola.html |
| [d-aprobada.html](wireframes/d-aprobada.html) | d-cola.html, d-revisar.html |
| [d-asistencia-vacio.html](wireframes/d-asistencia-vacio.html) | d-asistencia.html, d-cola.html, d-cumplimientos.html, d-penalizaciones.html, d-ranking.html |
| [d-asistencia.html](wireframes/d-asistencia.html) | d-asistencia-vacio.html, d-asistencia.html, d-cola.html, d-cumplimientos.html, d-penalizaciones.html, d-ranking.html, d-sesion-abrir.html, d-sesion.html |
| [d-cerrar-confirmar.html](wireframes/d-cerrar-confirmar.html) | d-sesion-cerrada.html, d-sesion.html |
| [d-cola-vacia.html](wireframes/d-cola-vacia.html) | d-asistencia.html, d-cola.html, d-cumplimientos.html, d-penalizaciones.html, d-ranking.html |
| [d-cola.html](wireframes/d-cola.html) | d-asistencia.html, d-cola-vacia.html, d-cola.html, d-cumplimientos.html, d-penalizaciones.html, d-ranking.html, d-revisada.html, d-revisar.html, sin-conexion.html |
| [d-cumplimiento-confirmar.html](wireframes/d-cumplimiento-confirmar.html) | d-cumplimiento-ok.html, d-cumplimiento.html |
| [d-cumplimiento-nocumplida.html](wireframes/d-cumplimiento-nocumplida.html) | d-cumplimiento.html |
| [d-cumplimiento-ok.html](wireframes/d-cumplimiento-ok.html) | d-cumplimiento.html |
| [d-cumplimiento-ya.html](wireframes/d-cumplimiento-ya.html) | d-cumplimiento.html |
| [d-cumplimiento.html](wireframes/d-cumplimiento.html) | d-cumplimiento-confirmar.html, d-cumplimiento-nocumplida.html, d-cumplimiento-ya.html, d-cumplimientos.html |
| [d-cumplimientos-vacio.html](wireframes/d-cumplimientos-vacio.html) | d-asistencia.html, d-cola.html, d-cumplimientos.html, d-penalizaciones.html, d-ranking.html |
| [d-cumplimientos.html](wireframes/d-cumplimientos.html) | d-asistencia.html, d-cola.html, d-cumplimiento.html, d-cumplimientos-vacio.html, d-cumplimientos.html, d-penalizaciones.html, d-ranking.html |
| [d-penalizaciones-vacio.html](wireframes/d-penalizaciones-vacio.html) | d-asistencia.html, d-cola.html, d-cumplimientos.html, d-penalizaciones.html, d-penalizar.html, d-ranking.html |
| [d-penalizaciones.html](wireframes/d-penalizaciones.html) | d-anular.html, d-asistencia.html, d-cola.html, d-cumplimientos.html, d-penalizaciones-vacio.html, d-penalizaciones.html, d-penalizar-patrulla.html, d-penalizar.html, d-ranking.html |
| [d-penalizar-error.html](wireframes/d-penalizar-error.html) | d-penalizar-form.html, d-penalizar-ok.html, d-penalizar.html |
| [d-penalizar-form.html](wireframes/d-penalizar-form.html) | d-penalizar-error.html, d-penalizar-ok.html, d-penalizar.html |
| [d-penalizar-ok.html](wireframes/d-penalizar-ok.html) | d-penalizaciones.html |
| [d-penalizar-patrulla.html](wireframes/d-penalizar-patrulla.html) | d-penalizaciones.html, d-penalizar-form.html |
| [d-penalizar.html](wireframes/d-penalizar.html) | d-penalizaciones.html, d-penalizar-form.html |
| [d-qr-sin-conexion.html](wireframes/d-qr-sin-conexion.html) | d-qr.html |
| [d-qr.html](wireframes/d-qr.html) | d-qr-sin-conexion.html, d-sesion-cerrada.html, d-sesion.html |
| [d-ranking.html](wireframes/d-ranking.html) | d-asistencia.html, d-cola.html, d-cumplimientos.html, d-penalizaciones.html, d-ranking.html |
| [d-rechazada.html](wireframes/d-rechazada.html) | d-cola.html |
| [d-rechazar-error.html](wireframes/d-rechazar-error.html) | d-rechazada.html, d-rechazar.html, d-revisar.html |
| [d-rechazar.html](wireframes/d-rechazar.html) | d-rechazada.html, d-rechazar-error.html, d-revisar.html |
| [d-revisada.html](wireframes/d-revisada.html) | d-cola.html |
| [d-revisar-patrulla.html](wireframes/d-revisar-patrulla.html) | d-aprobada-patrulla.html, d-cola.html, d-rechazar.html |
| [d-revisar-procesando.html](wireframes/d-revisar-procesando.html) | d-aprobada.html, d-cola.html, d-rechazar.html |
| [d-revisar.html](wireframes/d-revisar.html) | acceso-denegado.html, d-aprobada.html, d-cola.html, d-rechazar.html, d-revisada.html, d-revisar-patrulla.html, d-revisar-procesando.html |
| [d-sesion-abrir.html](wireframes/d-sesion-abrir.html) | d-asistencia.html, d-sesion.html |
| [d-sesion-cerrada.html](wireframes/d-sesion-cerrada.html) | d-asistencia.html |
| [d-sesion-ya-abierta.html](wireframes/d-sesion-ya-abierta.html) | d-asistencia.html, d-sesion.html |
| [d-sesion.html](wireframes/d-sesion.html) | d-asistencia.html, d-cerrar-confirmar.html, d-qr.html, d-sesion-ya-abierta.html |

### Tienda

| Desde | Hacia |
|---|---|
| [t-cobrar-error.html](wireframes/t-cobrar-error.html) | t-cobrar.html, t-devolver.html, t-historial.html, t-qr.html |
| [t-cobrar.html](wireframes/t-cobrar.html) | t-cobrar-error.html, t-cobrar.html, t-devolver.html, t-historial.html, t-qr.html |
| [t-devolver-corto.html](wireframes/t-devolver-corto.html) | t-cobrar.html, t-devolver.html, t-historial.html |
| [t-devolver-form.html](wireframes/t-devolver-form.html) | t-devolver-nada.html, t-devolver-ok.html, t-devolver-tope.html, t-devolver.html |
| [t-devolver-nada.html](wireframes/t-devolver-nada.html) | t-devolver.html |
| [t-devolver-ok.html](wireframes/t-devolver-ok.html) | t-devolver.html, t-historial.html |
| [t-devolver-tope.html](wireframes/t-devolver-tope.html) | t-devolver-form.html, t-devolver-ok.html, t-devolver.html |
| [t-devolver-vacio.html](wireframes/t-devolver-vacio.html) | t-cobrar.html, t-devolver.html, t-historial.html |
| [t-devolver.html](wireframes/t-devolver.html) | t-cobrar.html, t-devolver-corto.html, t-devolver-form.html, t-devolver-nada.html, t-devolver-vacio.html, t-devolver.html, t-historial.html |
| [t-historial-vacio.html](wireframes/t-historial-vacio.html) | t-cobrar.html, t-devolver.html, t-historial.html |
| [t-historial.html](wireframes/t-historial.html) | t-cobrar.html, t-devolver.html, t-historial-vacio.html, t-historial.html |
| [t-pagado.html](wireframes/t-pagado.html) | t-cobrar.html, t-devolver.html, t-historial.html |
| [t-qr.html](wireframes/t-qr.html) | t-cobrar.html, t-devolver.html, t-historial.html, t-pagado.html, t-vencido.html |
| [t-vencido.html](wireframes/t-vencido.html) | t-cobrar.html, t-devolver.html, t-historial.html |

## Patrones de navegación

- **Barra inferior por rol**: Participante (5 pestañas, Escanear al centro, más grande), Dirigente (Cola · Asistencia · Cumplim. · Penaliz. · Ranking), Tienda (Cobrar · Devolver · Historial).
- **Flecha «Volver»** arriba a la izquierda en toda pantalla de detalle o de formulario; las pantallas de la barra inferior usan encabezado simple.
- **Resultado como pantalla propia** (éxito, error, vencido): tiene un único botón principal y uno secundario; nunca un callejón sin salida.
- **Un solo Escanear** para asistencia y pago: el tipo de QR se reconoce por la dirección que trae.
- **«Cumplida» y «Pagar» piden confirmación** porque no se pueden deshacer (el pago, además, muestra el monto antes de cobrar).
- **Pantalla completa** solo para el QR del dirigente (sin barra inferior, QR grande, cuenta regresiva).
- **Actualización automática** sin botón: ranking (30 s), cobro de la tienda (~2 s), cola del dirigente.

## Estados que toda pantalla de datos debe tener

Vacío (con una salida clara), cargando, error de conexión y, cuando aplica, «ya registrado» (acciones repetidas). Los wireframes los dibujan para las pantallas clave; el resto sigue el mismo patrón.

## Reglas que el diseño NO puede romper

1. **Ranking anónimo**: ni nombres, ni marcar «tu equipo» en la tabla. Lo propio va aparte.
2. **Saldos**: los J-lis nunca se muestran negativos; los puntos sí pueden serlo (penalizaciones).
3. **Copy en español paraguayo** (voseo: «ingresá», «escaneá»), tono scout y cercano.
4. **360 px** es el ancho de revisión (los wireframes se dibujaron a 375 px).
5. **Nada de colores ni tipografías literales** en componentes: todo por tokens (Fase 2).
6. **Accesibilidad AA en móvil**: contraste, tamaño táctil mínimo, el QR legible a 3 metros.

## Preguntas abiertas (para la Fase 2)

1. **Identidad visual (D11)**: logo, colores y tipografía del evento. Sin logos oficiales hasta confirmar.
2. **Escáner de cámara**: `html5-qrcode` o `BarcodeDetector` nativo con respaldo; se decide con la UI real.
3. **Fotos de las entregas**: ¿el dirigente puede ampliarlas y hacer zoom? Se asume que sí.
4. **Sonido o vibración** al pagar y al sumar puntos (microinteracciones de la Fase 2).
5. **Rover**: ¿se muestra algo en lugar de «Mi patrulla» en Inicio? Hoy se oculta la tarjeta.
6. **Historial**: ¿se agrupa por día? Hoy es una lista simple del más nuevo al más viejo.

## Huecos de lectura que las pantallas necesitan (para las fases de implementación)

El dominio ya cubre las reglas, pero faltan estas **consultas de lectura**:

1. Historial unificado del participante (UC-10).
2. Resumen de Inicio: saldos, misiones nuevas y próxima reserva (UC-03).
3. Actividades visibles para una unidad (UC-06).
4. Estado de cada misión para el participante, con el motivo del rechazo (UC-04, UC-05).
5. Historial del puesto de la tienda (UC-16b).
6. Penalizaciones recientes y cumplimientos por patrulla del dirigente (UC-13, UC-14).
7. Conteo de asistentes de una sesión abierta (UC-12).
8. **Cancelar un cobro** de la tienda antes de que lo paguen (UC-15): esa acción todavía no existe en el dominio; hay que construirla o quitar el botón del wireframe.

## Siguiente paso

Aprobación de los wireframes por Leonardo (cierre de la Fase 1) y arranque de la Fase 2 con `/impeccable init`.
Los wireframes se pueden exportar a Figma si se pide («exportar a Figma»).
