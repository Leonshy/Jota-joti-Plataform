# Wireframes — inventario de pantallas

Prototipo clickeable: abrí [`index.html`](index.html) en el navegador. Sin JavaScript; todo es navegación entre archivos.

**115 pantallas.** Las pantallas con sufijo de estado (`-vacio`, `-error`, `-ok`…) son las variantes de la pantalla principal.

## Participante y acceso

| Pantalla | Archivo | Casos de uso | Elementos clave | Enlaces a |
|---|---|---|---|---|
| Acceso denegado | [acceso-denegado.html](acceso-denegado.html) | Transversal | × Esto no es para tu usuario No tenés permiso para ver esta pantalla. Ir al inicio | inicio.html |
| Primera vez · CI no cargado | [activar-ci-error.html](activar-ci-error.html) | UC-01 | Creá tu PIN Paso 1 de 2 Tu número de CI 7654321 Ese CI no está cargado. Hablá con un dirig | activar-ci.html, activar-pin.html, ingresar.html |
| Primera vez · ya tiene PIN | [activar-ci-ya.html](activar-ci-ya.html) | UC-01 | ! Ya tenés un PIN Ingresá con tu CI y tu PIN. Ir a ingresar | activar-ci.html, ingresar.html |
| Primera vez · tu CI | [activar-ci.html](activar-ci.html) | UC-01 | Creá tu PIN Paso 1 de 2 Tu número de CI 1234567 El mismo que figura en tu inscripción. Con | activar-ci-error.html, activar-ci-ya.html, activar-pin.html, ingresar.html |
| Primera vez · PIN con error | [activar-pin-error.html](activar-pin-error.html) | UC-01 | Elegí tu PIN Paso 2 de 2 Tu PIN •••• Repetí tu PIN •••• Los dos PIN no son iguales. Crear  | activar-ci.html, activar-pin.html, inicio-bienvenida.html |
| Primera vez · tu PIN | [activar-pin.html](activar-pin.html) | UC-01 | Elegí tu PIN Paso 2 de 2 · 4 números que puedas recordar Tu PIN •••• Repetí tu PIN •••• Cr | activar-ci.html, activar-pin-error.html, inicio-bienvenida.html |
| Actividad · turno que empezó | [actividad-empezo.html](actividad-empezo.html) | UC-06 | Taller de nudos 13:00 – 14:00 Ya empezó Reservar Este turno ya empezó: no se puede reserva | actividades.html |
| Actividad por equipo | [actividad-equipo.html](actividad-equipo.html) | UC-06 | Gran juego de patrullas Estación JOTA Por equipo 60 puntos Reservás para toda la patrulla  | actividad-sin-patrulla.html, actividades.html, reserva-choque-equipo.html, reserva-ok.html |
| Actividad · sin patrulla | [actividad-sin-patrulla.html](actividad-sin-patrulla.html) | UC-06 | ! Necesitás una patrulla Esta actividad es por equipo y no pertenecés a ninguna patrulla.  | actividad-equipo.html, actividades.html |
| Actividad | [actividad.html](actividad.html) | UC-06 | Taller de nudos Taller Individual 20 puntos Aprendé los nudos básicos con los dirigentes.  | actividad-empezo.html, actividades.html, reserva-choque.html, reserva-lleno.html, reserva-ok.html |
| Actividades · vacío | [actividades-vacio.html](actividades-vacio.html) | UC-06 | No hay actividades para tu unidad todavía. | actividades.html, escanear.html, inicio.html, misiones.html, ranking.html |
| Actividades | [actividades.html](actividades.html) | UC-06 | Mis reservas Taller de nudos Individual · 20 puntos Taller Estación JOTA: radioaficionados | actividad-equipo.html, actividad.html, actividades-vacio.html, actividades.html, escanear.html, inicio.html… |
| Ayuda con el PIN | [ayuda-pin.html](ayuda-pin.html) | UC-02 | ¿Olvidaste tu PIN? No hay problema 1. Pedile a un dirigente que te resetee el PIN. 2. Cuan | activar-ci.html, ingresar.html |
| Cancelar reserva | [cancelar-reserva.html](cancelar-reserva.html) | UC-06 | ¿Cancelar la reserva? Taller de nudos · hoy 15:00. Se libera el lugar. Sí, cancelar No, la | mis-reservas-vacio.html, mis-reservas.html |
| Confirmar pago · saldo insuficiente | [confirmar-pago-saldo.html](confirmar-pago-saldo.html) | UC-08 | Confirmá el pago Vas a pagar 30 J-lis No te alcanzan los J-lis. Te faltan 25. No se descon | confirmar-pago.html, inicio.html |
| Confirmar pago | [confirmar-pago.html](confirmar-pago.html) | UC-08 | Confirmá el pago Vas a pagar 30 J-lis a Tienda JOTA Vence en 1:42 Tu saldo 100 J-lis Te qu | confirmar-pago-saldo.html, inicio.html, pago-no-existe.html, pago-ok.html, pago-vencido.html, pago-ya-pagado.html |
| Entrega enviada | [entrega-enviada.html](entrega-enviada.html) | UC-04 | ✓ ¡Entrega enviada! Tu dirigente la va a revisar. Te avisamos acá cuando esté. Ver la misi | mision-pendiente.html, misiones.html |
| Entregar · con errores | [entregar-error.html](entregar-error.html) | UC-04 | Tu entrega Contanos qué hiciste &nbsp; Escribí algo o subí al menos una foto. FOTOS (1 de  | entrega-enviada.html, entregar-vencida.html, entregar.html, mision.html |
| Entregar · subiendo | [entregar-subiendo.html](entregar-subiendo.html) | UC-04 | Tu entrega Contanos qué hiciste Soy Ana… Foto 1 Subiendo… 60% Enviar entrega | entregar.html, mision.html |
| Entregar · misión vencida | [entregar-vencida.html](entregar-vencida.html) | UC-04 | × La misión ya venció Tu entrega no se envió. Volver a misiones | mision.html, misiones.html |
| Entregar | [entregar.html](entregar.html) | UC-04 | Tu entrega Texto y/o hasta 4 fotos Contanos qué hiciste Soy Ana, de Asunción… Hasta 2000 c | entrega-enviada.html, entregar-error.html, entregar-subiendo.html, mision.html |
| Escanear · QR no reconocido | [escanear-no-reconocido.html](escanear-no-reconocido.html) | UC-07 | × No reconocemos este QR Tiene que ser el de una asistencia o el de un cobro de la tienda. | escanear.html |
| Escanear · sin cámara | [escanear-sin-camara.html](escanear-sin-camara.html) | UC-07 | ! No podemos usar la cámara 1. Tocá el candado o el ícono junto a la dirección del sitio.  | escanear.html |
| Escanear | [escanear.html](escanear.html) | UC-07 | Apuntá al código QR. Sirve para marcar asistencia y para pagar en la tienda. No me abre la | actividades.html, confirmar-pago.html, escanear-no-reconocido.html, escanear-sin-camara.html, escanear.html, inicio.html… |
| Historial · vacío | [historial-vacio.html](historial-vacio.html) | UC-10 | Todavía no tenés movimientos. Ver misiones | inicio.html, misiones.html |
| Historial | [historial.html](historial.html) | UC-10 | Todo Puntos J-lis Asistencia a «Taller de nudos» Hoy 15:20 +20 pts Asistencia a «Taller de | historial-vacio.html, historial.html, inicio.html |
| Prototipo JOTA-JOTI 2026 | [index.html](index.html) | Entrada | Prototipo clickeable Wireframes de las pantallas móviles. Elegí con qué rol querés recorre | d-cola.html, ingresar.html, inicio.html, t-cobrar.html |
| Ingresar · CI no cargado | [ingresar-ci-error.html](ingresar-ci-error.html) | UC-02 | JOTA-JOTI 2026 Ingresá Tu número de CI 7654321 Ese CI no está cargado en la plataforma. Tu | ayuda-pin.html, ingresar.html, inicio.html |
| Ingresar · PIN incorrecto | [ingresar-error.html](ingresar-error.html) | UC-02 | JOTA-JOTI 2026 Ingresá Tu número de CI 1234567 Tu PIN •••• PIN incorrecto. Probá de nuevo. | activar-ci.html, ingresar.html, inicio.html |
| Ingresar | [ingresar.html](ingresar.html) | UC-02 | JOTA-JOTI 2026 Ingresá Con tu CI y tu PIN de 4 números Tu número de CI 1234567 Tu PIN •••• | activar-ci.html, ayuda-pin.html, d-cola.html, ingresar-ci-error.html, ingresar-error.html, inicio.html… |
| Inicio · bienvenida | [inicio-bienvenida.html](inicio-bienvenida.html) | UC-01 | ¡Listo, Ana! Ya tenés tu PIN. Usalo cada vez que ingreses. Mis puntos 10 Mis J-lis 70 Mi p | actividades.html, escanear.html, inicio.html, misiones.html, ranking.html |
| Inicio · sin novedades | [inicio-vacio.html](inicio-vacio.html) | UC-03 | Hola, Ana Mis puntos 10 Mis J-lis 70 Mi patrulla · Águilas 100 puntos de la patrulla Todav | actividades.html, escanear.html, inicio.html, misiones.html, ranking.html |
| Inicio | [inicio.html](inicio.html) | UC-03 | Hola, Ana Mis puntos 10 Mis J-lis 70 Mi patrulla · Águilas 100 puntos de la patrulla PARA  | actividades.html, escanear.html, historial.html, ingresar.html, inicio-bienvenida.html, inicio-vacio.html… |
| Mis reservas · vacío | [mis-reservas-vacio.html](mis-reservas-vacio.html) | UC-06 | No tenés reservas. Ver actividades | actividades.html |
| Mis reservas | [mis-reservas.html](mis-reservas.html) | UC-06 | PARA VOS Taller de nudos Individual Hoy 15:00 – 16:00 Cancelar reserva PARA TU PATRULLA Gr | actividades.html, cancelar-reserva.html, mis-reservas-vacio.html |
| Misión | [mision-aprobada.html](mision-aprobada.html) | UC-04 | Bienvenida al JOTA-JOTI Individual 10 puntos Vence hoy 18:00 Sacate una foto con tu unifor | mision-aprobada.html, mision-patrulla.html, mision-pendiente.html, mision-rechazada.html, mision-vencida.html, mision.html… |
| Misión por patrulla · libre | [mision-patrulla-libre.html](mision-patrulla-libre.html) | UC-04 | Nudos en equipo Por patrulla 50 puntos Armen entre toda la patrulla un nudo de ocho y mand | entregar.html, misiones.html |
| Misión por patrulla | [mision-patrulla.html](mision-patrulla.html) | UC-04 | Nudos en equipo Por patrulla 50 puntos Vence 22:00 Armen entre toda la patrulla un nudo de | mision-patrulla-libre.html, misiones.html |
| Misión | [mision-pendiente.html](mision-pendiente.html) | UC-04 | Bienvenida al JOTA-JOTI Individual 10 puntos Vence hoy 18:00 Sacate una foto con tu unifor | mision-aprobada.html, mision-patrulla.html, mision-pendiente.html, mision-rechazada.html, mision-vencida.html, mision.html… |
| Misión | [mision-rechazada.html](mision-rechazada.html) | UC-05 | Bienvenida al JOTA-JOTI Individual 10 puntos Vence hoy 18:00 Sacate una foto con tu unifor | entregar.html, mision-aprobada.html, mision-patrulla.html, mision-pendiente.html, mision-rechazada.html, mision-vencida.html… |
| Misión | [mision-vencida.html](mision-vencida.html) | UC-05 | Bienvenida al JOTA-JOTI Individual 10 puntos Vence hoy 18:00 Sacate una foto con tu unifor | mision-aprobada.html, mision-patrulla.html, mision-pendiente.html, mision-rechazada.html, mision-vencida.html, mision.html… |
| Misión | [mision.html](mision.html) | UC-04 | Bienvenida al JOTA-JOTI Individual 10 puntos Vence hoy 18:00 Sacate una foto con tu unifor | entregar.html, mision-aprobada.html, mision-patrulla.html, mision-pendiente.html, mision-rechazada.html, mision-vencida.html… |
| Misiones · vacío | [misiones-vacio.html](misiones-vacio.html) | UC-04 | Todavía no hay misiones. Cuando se lancen, aparecen acá. | actividades.html, escanear.html, inicio.html, misiones.html, ranking.html |
| Misiones | [misiones.html](misiones.html) | UC-04 | Todas Para entregar Revisadas Nudos en equipo Por patrulla · 50 puntos Pendiente Bienvenid | actividades.html, escanear.html, inicio.html, mision-aprobada.html, mision-patrulla.html, mision-pendiente.html… |
| Pago · cobro inexistente | [pago-no-existe.html](pago-no-existe.html) | UC-08 | × Ese cobro no existe Pedile a la tienda que genere uno nuevo. Escanear otro | escanear.html |
| Pago listo | [pago-ok.html](pago-ok.html) | UC-08 | ✓ ¡Pago listo! Pagaste 30 J-lis a Tienda JOTA. Te quedan 70 J-lis Ir al inicio Ver mi hist | historial.html, inicio.html |
| Pago · cobro vencido | [pago-vencido.html](pago-vencido.html) | UC-08 | ! El cobro venció Pedile a la tienda que genere uno nuevo. Volver al inicio | inicio.html |
| Pago · ya pagado | [pago-ya-pagado.html](pago-ya-pagado.html) | UC-08 | ✓ Este cobro ya fue pagado No se descuenta de nuevo. Volver al inicio | inicio.html |
| Ranking · Rovers | [ranking-rover.html](ranking-rover.html) | UC-09 | Ranking de Rovers Individual · se actualiza cada 30 segundos Puesto Puntaje 1° 80 1° 80 3° | actividades.html, escanear.html, inicio.html, misiones.html, ranking.html |
| Ranking · sin conexión | [ranking-sin-conexion.html](ranking-sin-conexion.html) | UC-09 | Sin conexión. Mostramos lo último que viste. Puesto Puntaje 1° 140 2° 130 3° 95 4° 0 | actividades.html, escanear.html, inicio.html, misiones.html, ranking.html |
| Ranking · vacío | [ranking-vacio.html](ranking-vacio.html) | UC-09 | Todavía no hay puntajes. Cuando alguien sume, aparece acá. | actividades.html, escanear.html, inicio.html, misiones.html, ranking.html |
| Ranking | [ranking.html](ranking.html) | UC-09 | Ranking de Tropa Por equipo · se actualiza cada 30 segundos Puesto Puntaje 1° 140 2° 130 3 | actividades.html, escanear.html, inicio.html, misiones.html, ranking-rover.html, ranking-sin-conexion.html… |
| Choque de horario (equipo) | [reserva-choque-equipo.html](reserva-choque-equipo.html) | UC-06 | ! Un integrante ya tiene algo a esa hora Choca con «Taller de nudos» de Beto Cardozo , de  | actividad-equipo.html |
| Choque de horario | [reserva-choque.html](reserva-choque.html) | UC-06 | ! Se pisa con otra reserva Choca con «Gran juego de patrullas» de la patrulla Águilas, de  | actividad.html, mis-reservas.html |
| Turno lleno | [reserva-lleno.html](reserva-lleno.html) | UC-06 | × Ya no hay lugares Probá con otro turno. Volver a los turnos | actividad.html |
| Reserva confirmada | [reserva-ok.html](reserva-ok.html) | UC-06 | ✓ ¡Reservado! Taller de nudos · hoy 15:00 a 16:00 Ver mis reservas Seguir mirando activida | actividades.html, mis-reservas.html |
| Asistencia · sesión cerrada | [resultado-asistencia-cerrada.html](resultado-asistencia-cerrada.html) | UC-07 | × La sesión ya se cerró El dirigente cerró la asistencia de esta actividad. Ir al inicio | inicio.html |
| Asistencia registrada | [resultado-asistencia-ok.html](resultado-asistencia-ok.html) | UC-07 | ✓ ¡Listo! Registramos tu asistencia a «Taller de nudos». Puntos +20 J-lis +20 Tus puntos 3 | escanear.html, inicio.html |
| Asistencia · otra unidad | [resultado-asistencia-unidad.html](resultado-asistencia-unidad.html) | UC-07 | × Esta actividad no es para tu unidad Ir al inicio | inicio.html |
| Asistencia · QR vencido | [resultado-asistencia-vencido.html](resultado-asistencia-vencido.html) | UC-07 | ! Ese QR ya venció Cambia cada minuto. Escaneá el que está en pantalla ahora. Escanear de  | escanear.html |
| Asistencia · ya registrada | [resultado-asistencia-ya.html](resultado-asistencia-ya.html) | UC-07 | ✓ Ya registraste tu asistencia No suma de nuevo. Ir al inicio | inicio.html, resultado-asistencia-cerrada.html, resultado-asistencia-unidad.html |
| Sesión vencida | [sesion-vencida.html](sesion-vencida.html) | UC-02 | ! Tu sesión se cerró Volvé a ingresar con tu CI y tu PIN. Ingresar | ingresar.html |
| Sin conexión | [sin-conexion.html](sin-conexion.html) | Transversal | ! No hay internet La plataforma necesita conexión para funcionar. Reintentar | inicio.html |

## Dirigente

| Pantalla | Archivo | Casos de uso | Elementos clave | Enlaces a |
|---|---|---|---|---|
| Penalización anulada | [d-anulada.html](d-anulada.html) | UC-14 | ✓ Anulada Se devolvieron 5 puntos a Ana Benítez. Volver a penalizaciones | d-penalizaciones.html |
| Ya anulada | [d-anular-ya.html](d-anular-ya.html) | UC-14 | ! Esta penalización ya fue anulada Volver | d-penalizaciones.html |
| Anular penalización | [d-anular.html](d-anular.html) | UC-14 | Anular la penalización Ana Benítez · −5 puntos · «Llegó tarde» Se le devuelven los puntos. | d-anulada.html, d-anular-ya.html, d-penalizaciones.html |
| Entrega aprobada (patrulla) | [d-aprobada-patrulla.html](d-aprobada-patrulla.html) | UC-11 | ✓ Aprobada 50 puntos a la patrulla Zorros y 13 J-lis a cada integrante. Volver a la cola | d-cola.html |
| Entrega aprobada | [d-aprobada.html](d-aprobada.html) | UC-11 | ✓ Aprobada Se acreditaron 10 puntos y 10 J-lis a Ana Benítez. Siguiente entrega Volver a l | d-cola.html, d-revisar.html |
| Asistencia · vacío | [d-asistencia-vacio.html](d-asistencia-vacio.html) | UC-12 | No hay actividades individuales para tu unidad. | d-asistencia.html, d-cola.html, d-cumplimientos.html, d-penalizaciones.html, d-ranking.html |
| Sesiones de asistencia | [d-asistencia.html](d-asistencia.html) | UC-12 | Asistencia Actividades individuales de tu unidad Taller de nudos Individual · 20 puntos Si | d-asistencia-vacio.html, d-asistencia.html, d-cola.html, d-cumplimientos.html, d-penalizaciones.html, d-ranking.html… |
| Cerrar sesión | [d-cerrar-confirmar.html](d-cerrar-confirmar.html) | UC-12 | ¿Cerrar la sesión? El QR y el enlace dejan de funcionar. Las asistencias ya registradas qu | d-sesion-cerrada.html, d-sesion.html |
| Cola vacía | [d-cola-vacia.html](d-cola-vacia.html) | UC-11 | ✓ No hay nada para revisar Buen trabajo. Cuando llegue una entrega, aparece acá. | d-asistencia.html, d-cola.html, d-cumplimientos.html, d-penalizaciones.html, d-ranking.html |
| Cola de validación | [d-cola.html](d-cola.html) | UC-11 | 7 por revisar · Tropa Ana Benítez · Águilas Bienvenida al JOTA-JOTI · hace 12 min 2 fotos  | d-asistencia.html, d-cola-vacia.html, d-cola.html, d-cumplimientos.html, d-penalizaciones.html, d-ranking.html… |
| Confirmar cumplida | [d-cumplimiento-confirmar.html](d-cumplimiento-confirmar.html) | UC-13 | ¿Marcar a Águilas como cumplida? Se acreditan 60 puntos a la patrulla y 15 J-lis a cada in | d-cumplimiento-ok.html, d-cumplimiento.html |
| No cumplida registrada | [d-cumplimiento-nocumplida.html](d-cumplimiento-nocumplida.html) | UC-13 | ✓ Marcada como no cumplida No se acreditó nada. Se puede corregir después. Volver a las pa | d-cumplimiento.html |
| Cumplida registrada | [d-cumplimiento-ok.html](d-cumplimiento-ok.html) | UC-13 | ✓ Águilas: cumplida 60 puntos a la patrulla y 15 J-lis a cada integrante. Volver a las pat | d-cumplimiento.html |
| Ya cumplida | [d-cumplimiento-ya.html](d-cumplimiento-ya.html) | UC-13 | ! Esta patrulla ya figura como cumplida No se acredita de nuevo. Volver | d-cumplimiento.html |
| Gran juego de patrullas | [d-cumplimiento.html](d-cumplimiento.html) | UC-13 | Gran juego de patrullas 60 puntos a la patrulla Águilas Sin marcar Cumplida No cumplida Pu | d-cumplimiento-confirmar.html, d-cumplimiento-nocumplida.html, d-cumplimiento-ya.html, d-cumplimientos.html |
| Cumplimientos · vacío | [d-cumplimientos-vacio.html](d-cumplimientos-vacio.html) | UC-13 | No hay actividades por equipo para tu unidad. | d-asistencia.html, d-cola.html, d-cumplimientos.html, d-penalizaciones.html, d-ranking.html |
| Cumplimientos por equipo | [d-cumplimientos.html](d-cumplimientos.html) | UC-13 | Cumplimientos Actividades por equipo de tu unidad Gran juego de patrullas 60 puntos 4 patr | d-asistencia.html, d-cola.html, d-cumplimiento.html, d-cumplimientos-vacio.html, d-cumplimientos.html, d-penalizaciones.html… |
| Penalizaciones · vacío | [d-penalizaciones-vacio.html](d-penalizaciones-vacio.html) | UC-14 | Nueva penalización Sin penalizaciones todavía. | d-asistencia.html, d-cola.html, d-cumplimientos.html, d-penalizaciones.html, d-penalizar.html, d-ranking.html |
| Penalizaciones | [d-penalizaciones.html](d-penalizaciones.html) | UC-14 | A una persona A una patrulla Nueva penalización RECIENTES Ana Benítez · −5 puntos «Llegó t | d-anular.html, d-asistencia.html, d-cola.html, d-cumplimientos.html, d-penalizaciones-vacio.html, d-penalizaciones.html… |
| Penalizar · con errores | [d-penalizar-error.html](d-penalizar-error.html) | UC-14 | Ana Benítez Cantidad de puntos a restar 0 Tiene que ser mayor a cero. Motivo (obligatorio) | d-penalizar-form.html, d-penalizar-ok.html, d-penalizar.html |
| Penalizar · datos | [d-penalizar-form.html](d-penalizar-form.html) | UC-14 | PENALIZÁS A Ana Benítez Águilas · tiene 10 puntos Cantidad de puntos a restar 5 Motivo (ob | d-penalizar-error.html, d-penalizar-ok.html, d-penalizar.html |
| Penalización aplicada | [d-penalizar-ok.html](d-penalizar-ok.html) | UC-14 | ✓ Penalización aplicada Ana Benítez ahora tiene 5 puntos (−5). Volver a penalizaciones | d-penalizaciones.html |
| Penalizar a una patrulla | [d-penalizar-patrulla.html](d-penalizar-patrulla.html) | UC-14 | Penalizar a una patrulla Águilas 100 puntos de la patrulla Pumas 50 puntos de la patrulla  | d-penalizaciones.html, d-penalizar-form.html |
| Penalizar a una persona | [d-penalizar.html](d-penalizar.html) | UC-14 | Penalizar a una persona Buscá por nombre o CI Ana Ana Benítez · CI 1234567 Águilas · 10 pu | d-penalizaciones.html, d-penalizar-form.html |
| QR · sin conexión | [d-qr-sin-conexion.html](d-qr-sin-conexion.html) | UC-12 | QR Sin conexión. Reintentando… El último código puede estar vencido. Reintentar | d-qr.html |
| QR en pantalla completa | [d-qr.html](d-qr.html) | UC-12 | Estación JOTA: radioaficionados QR 0:42 El código cambia solo cada minuto Escaneá con la a | d-qr-sin-conexion.html, d-sesion-cerrada.html, d-sesion.html |
| Ranking de la unidad | [d-ranking.html](d-ranking.html) | UC-09 | Ranking de Tropa Sin nombres: solo el administrador los ve Puesto Puntaje 1° 140 2° 130 3° | d-asistencia.html, d-cola.html, d-cumplimientos.html, d-penalizaciones.html, d-ranking.html |
| Entrega rechazada | [d-rechazada.html](d-rechazada.html) | UC-11 | ✓ Rechazada Ana ya puede ver el motivo y volver a entregar si la misión sigue abierta. Vol | d-cola.html |
| Rechazar · sin motivo | [d-rechazar-error.html](d-rechazar-error.html) | UC-11 | Rechazar la entrega de Ana Motivo (obligatorio) &nbsp; Tenés que indicar el motivo. Rechaz | d-rechazada.html, d-rechazar.html, d-revisar.html |
| Rechazar entrega | [d-rechazar.html](d-rechazar.html) | UC-11 | Rechazar la entrega de Ana Ella va a ver el motivo. Motivo (obligatorio) La foto está borr | d-rechazada.html, d-rechazar-error.html, d-revisar.html |
| Ya revisada | [d-revisada.html](d-revisada.html) | UC-11 | ! Esa entrega ya fue revisada Otro dirigente se adelantó. No se acreditó nada de más. Volv | d-cola.html |
| Revisar entrega por patrulla | [d-revisar-patrulla.html](d-revisar-patrulla.html) | UC-11 | Patrulla Zorros La entregó Carla Duarte MISIÓN Nudos en equipo 50 puntos a la patrulla · J | d-aprobada-patrulla.html, d-cola.html, d-rechazar.html |
| Revisar · foto procesándose | [d-revisar-procesando.html](d-revisar-procesando.html) | UC-11 | Ana Benítez Foto 1 · se está procesando… Podés revisar el texto mientras tanto. Aprobar Re | d-aprobada.html, d-cola.html, d-rechazar.html |
| Revisar entrega | [d-revisar.html](d-revisar.html) | UC-11 | Ana Benítez Patrulla Águilas · hace 12 min MISIÓN Bienvenida al JOTA-JOTI Individual · 10  | acceso-denegado.html, d-aprobada.html, d-cola.html, d-rechazar.html, d-revisada.html, d-revisar-patrulla.html… |
| Abrir sesión | [d-sesion-abrir.html](d-sesion-abrir.html) | UC-12 | Taller de nudos Individual · 20 puntos Al abrir la sesión se genera un QR que cambia cada  | d-asistencia.html, d-sesion.html |
| Sesión cerrada | [d-sesion-cerrada.html](d-sesion-cerrada.html) | UC-12 | ✓ Sesión cerrada Se registraron 14 asistencias. Volver a asistencia | d-asistencia.html |
| Sesión ya abierta | [d-sesion-ya-abierta.html](d-sesion-ya-abierta.html) | UC-12 | Esta actividad ya tenía una sesión abierta . Te mostramos esa. Ir a la sesión | d-asistencia.html, d-sesion.html |
| Sesión abierta | [d-sesion.html](d-sesion.html) | UC-12 | Estación JOTA: radioaficionados Sesión abierta · desde las 15:02 Asistentes 12 Mostrar QR  | d-asistencia.html, d-cerrar-confirmar.html, d-qr.html, d-sesion-ya-abierta.html |

## Tienda

| Pantalla | Archivo | Casos de uso | Elementos clave | Enlaces a |
|---|---|---|---|---|
| Cobrar · monto no válido | [t-cobrar-error.html](t-cobrar-error.html) | UC-15 | Monto a cobrar 0 J-lis El monto tiene que ser mayor a cero. 1 2 3 4 5 6 7 8 9 C 0 &larr; G | t-cobrar.html, t-devolver.html, t-historial.html, t-qr.html |
| Cobrar | [t-cobrar.html](t-cobrar.html) | UC-15 | Monto a cobrar 30 J-lis 1 2 3 4 5 6 7 8 9 C 0 &larr; Generar cobro | t-cobrar-error.html, t-cobrar.html, t-devolver.html, t-historial.html, t-qr.html |
| Devolver · pocas letras | [t-devolver-corto.html](t-devolver-corto.html) | UC-16 | CI o nombre An Escribí al menos 3 letras del nombre, o el CI completo. | t-cobrar.html, t-devolver.html, t-historial.html |
| Devolver · datos | [t-devolver-form.html](t-devolver-form.html) | UC-16 | DEVOLVÉS A Ana Benítez CI 1234567 Gastó en la tienda 40 Ya devuelto 10 Se pueden devolver  | t-devolver-nada.html, t-devolver-ok.html, t-devolver-tope.html, t-devolver.html |
| Devolver · no gastó | [t-devolver-nada.html](t-devolver-nada.html) | UC-16 | ! No hay nada para devolver Esta persona no gastó J-lis en la tienda. Buscar a otra person | t-devolver.html |
| Devolución hecha | [t-devolver-ok.html](t-devolver-ok.html) | UC-16 | ✓ Devolución hecha Ana Benítez recibió 20 J-lis y ahora tiene 90. Otra devolución Ver hist | t-devolver.html, t-historial.html |
| Devolver · pasa el tope | [t-devolver-tope.html](t-devolver-tope.html) | UC-16 | Ana Benítez Monto a devolver 50 Solo se pueden devolver hasta 30 J-lis: es lo que gastó y  | t-devolver-form.html, t-devolver-ok.html, t-devolver.html |
| Devolver · sin resultados | [t-devolver-vacio.html](t-devolver-vacio.html) | UC-16 | CI o nombre Zzz No encontramos a nadie con ese CI o nombre. | t-cobrar.html, t-devolver.html, t-historial.html |
| Devolver | [t-devolver.html](t-devolver.html) | UC-16 | Devolver J-lis Buscá a la persona por CI o por nombre CI o nombre Ana Ana Benítez · CI 123 | t-cobrar.html, t-devolver-corto.html, t-devolver-form.html, t-devolver-nada.html, t-devolver-vacio.html, t-devolver.html… |
| Historial · vacío | [t-historial-vacio.html](t-historial-vacio.html) | UC-16b | Todavía no hiciste cobros. Ir a cobrar | t-cobrar.html, t-devolver.html, t-historial.html |
| Historial del puesto | [t-historial.html](t-historial.html) | UC-16b | Total cobrado hoy 350 COBROS Ana Benítez · 30 J-lis 15:42 Pagado Beto Cardozo · 45 J-lis 1 | t-cobrar.html, t-devolver.html, t-historial-vacio.html, t-historial.html |
| Pagado | [t-pagado.html](t-pagado.html) | UC-15 | ✓ Pagado por Ana Benítez 30 J-lis · 15:42 Nuevo cobro Ver historial | t-cobrar.html, t-devolver.html, t-historial.html |
| QR de cobro | [t-qr.html](t-qr.html) | UC-15 | 30 J-lis Mostrale este código al cliente QR 1:58 Esperando el pago… Cancelar cobro | t-cobrar.html, t-devolver.html, t-historial.html, t-pagado.html, t-vencido.html |
| Cobro vencido | [t-vencido.html](t-vencido.html) | UC-15 | ! El cobro venció Nadie lo pagó en 2 minutos. Generar otro | t-cobrar.html, t-devolver.html, t-historial.html |
