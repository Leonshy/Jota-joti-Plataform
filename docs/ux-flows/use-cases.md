# Casos de uso — Plataforma JOTA-JOTI 2026

Fuente: `legajo-plataforma-jota-joti-2026.md` (§4 y §5), `PLAN.md` (Fase 1) y el comportamiento ya
construido en el dominio (Fases 3 a 8). Todos los mensajes de error citados salen de las reglas
reales del sistema; el copy definitivo se escribe en la Fase 2.

**Convenciones**
- Pantallas móviles a 360 px. Tres roles móviles: **Participante**, **Dirigente**, **Tienda**.
- El **Admin** trabaja en el panel Filament (ya construido, §UC-17): se documenta, sin wireframes.
- Cada caso define sus estados **vacío**, **error** y **éxito**.
- No hay WebSockets: lo "en vivo" es polling (ranking 30 s, cobro ~2 s, cola del dirigente 15–30 s).
- La hora que vale es la del servidor (America/Asuncion).

---

## Mapa de roles y navegación

| Rol | Navegación inferior | Entra por |
|---|---|---|
| Participante | Inicio · Misiones · **Escanear** (centro) · Actividades · Ranking | `/app` |
| Dirigente | Cola · Asistencia · Cumplimientos · Penalizaciones · Ranking | `/dirigente` |
| Tienda | Cobrar · Devolver · Historial | `/tienda` |
| Admin | Panel Filament | `/admin` |

El botón **Escanear** es único: sirve para asistencia y para pagar. La app reconoce de qué es el QR
por la dirección que trae (`/asistencia/…/registrar/…` o `/cobros/…`).

---

## A. Participante

### UC-01 · Activar la cuenta (primer ingreso)
| | |
|---|---|
| **Actores** | Participante, Sistema |
| **Precondiciones** | El admin cargó su CI. Todavía no tiene PIN. |
| **Flujo principal** | 1. Abre la app y elige "Primera vez". 2. Escribe su CI. 3. Escribe un PIN de 4 dígitos. 4. Confirma el PIN. 5. El sistema crea el PIN, inicia la sesión y lo lleva a Inicio. |
| **Flujos alternativos** | **CI no cargado**: "Ese CI no está cargado" + cómo pedir ayuda (hablar con un dirigente). **Ya tiene PIN**: lo manda a "Ingresar". **PIN inválido** (no son 4 números) o no coinciden las dos veces: error en el campo. **Dos personas activan el mismo CI a la vez**: gana la primera; la segunda ve "ya tiene PIN". |
| **Estados** | Vacío: formulario limpio. Error: por campo. Éxito: entra a Inicio con un mensaje de bienvenida. |
| **Postcondiciones** | Cuenta activada, sesión larga abierta. |

### UC-02 · Ingresar (login)
| | |
|---|---|
| **Actores** | Participante, Sistema |
| **Precondiciones** | Tiene PIN. |
| **Flujo principal** | 1. Escribe CI y PIN. 2. Entra a Inicio. |
| **Flujos alternativos** | **CI no cargado**. **Todavía no creó el PIN** → lleva a UC-01 con el CI ya escrito. **PIN incorrecto** → puede reintentar sin límite, sin bloqueo. **Venía de escanear un QR con la cámara del sistema** → después de ingresar completa la acción (asistencia o pago). **Olvidó el PIN** → "Pedile a un dirigente que te lo resetee" (lo resetea el admin). |
| **Estados** | Error: mensaje claro y el foco vuelve al PIN. Éxito: Inicio. |
| **Postcondiciones** | Sesión abierta durante todo el evento. |

### UC-03 · Inicio
| | |
|---|---|
| **Actores** | Participante |
| **Precondiciones** | Sesión iniciada. |
| **Flujo principal** | Ve su saldo de **puntos**, su saldo de **J-lis** y el **puntaje de su patrulla** (si tiene). Ve atajos: misiones nuevas o por entregar, próximo turno reservado, botón Escanear, historial. |
| **Flujos alternativos** | **Rover**: sin patrulla, solo sus puntos. **Sin nada pendiente**: "Todavía no hay misiones nuevas". **Sesión vencida o cerrada por el admin**: vuelve a Ingresar. **Sin conexión**: aviso "Necesitás internet" (no hay modo sin conexión). |
| **Estados** | Vacío: sin misiones ni reservas. Error: sin conexión. |
| **Postcondiciones** | — |

### UC-04 · Ver misiones y entregar (individual y por patrulla)
| | |
|---|---|
| **Actores** | Participante, Sistema |
| **Precondiciones** | La misión es de su unidad y ya pasó su hora de lanzamiento. |
| **Flujo principal** | 1. Abre **Misiones**: ve la lista con el estado de cada una (nueva · esperando revisión · aprobada · rechazada · vencida · cerrada). 2. Abre una misión: título, descripción, puntos, modalidad, vencimiento. 3. Toca "Entregar". 4. Escribe un texto y/o agrega hasta **4 fotos** (JPG, PNG o WebP, hasta 8 MB cada una). 5. Envía. 6. Ve "Entrega enviada, esperando revisión". |
| **Flujos alternativos** | **Misión por patrulla**: la entrega es en nombre de la patrulla; cualquier integrante puede enviarla y todos ven el estado. **Ya hay una pendiente** (propia, o de otro integrante si es por patrulla): no puede enviar otra; ve "ya hay una entrega esperando revisión". **Ya aprobada**: no puede volver a entregar. **Sin texto ni fotos**: "Escribí algo o subí al menos una foto". **Más de 4 fotos / foto muy pesada / formato no válido**: error por foto. **La misión venció mientras escribía**: no se envía; "La misión ya venció". **Sin patrulla en una misión por patrulla**: aviso. **Foto todavía procesándose**: la entrega se envía igual. |
| **Estados** | Vacío: "No hay misiones todavía". Cargando fotos: progreso por foto. Error: por campo y por foto. Éxito: confirmación + vuelve a la lista. |
| **Postcondiciones** | Entrega pendiente de revisión. |

### UC-05 · Entrega rechazada → volver a entregar
| | |
|---|---|
| **Actores** | Participante, Dirigente |
| **Precondiciones** | Su entrega fue rechazada y la misión sigue abierta. |
| **Flujo principal** | 1. Ve la misión como "Rechazada" con el **motivo** del dirigente. 2. Toca "Volver a entregar". 3. Envía una entrega nueva (la anterior queda en el historial de la misión). |
| **Flujos alternativos** | **La misión venció o se cerró**: ve el motivo del rechazo pero ya no puede reintentar ("Ya no se puede entregar"). **Aprobada mientras tanto por otra vía**: no aplica. |
| **Estados** | Éxito: vuelve a "Esperando revisión". |
| **Postcondiciones** | Nueva entrega pendiente. |

### UC-06 · Reservar un turno (incluido el choque de horario)
| | |
|---|---|
| **Actores** | Participante, Sistema |
| **Precondiciones** | La actividad es de su unidad. |
| **Flujo principal** | 1. Abre **Actividades**: talleres y estaciones JOTA, con tipo, puntos y modalidad. 2. Abre una actividad: ve sus turnos con horario y lugares libres. 3. Toca "Reservar" en un turno. 4. Ve la confirmación. Puede ver y cancelar sus reservas. |
| **Flujos alternativos** | **Actividad por equipo**: reserva cualquier integrante en nombre de la patrulla; se avisa que es "para toda la patrulla". **Turno lleno**: "Ya no hay lugares". **Choque de horario**: se bloquea e informa **con qué** choca y **de quién** ("Choca con «Taller de nudos» de la patrulla Águilas, de 15:00 a 16:00" / "…de Ana Benítez"). **Turno que ya empezó**: no se puede reservar ni cancelar. **Ya reservado**. **Sin patrulla en una actividad por equipo**. **Dos reservas a la vez**: la segunda se rechaza. **Cancelar**: libera el lugar. |
| **Estados** | Vacío: "No hay actividades para tu unidad". Turno lleno / pasado: deshabilitado con motivo. |
| **Postcondiciones** | Reserva registrada (o cancelada). |

### UC-07 · Escanear asistencia
| | |
|---|---|
| **Actores** | Participante, Dirigente (muestra el QR), Sistema |
| **Precondiciones** | El dirigente tiene una sesión de asistencia abierta (UC-12). |
| **Flujo principal** | 1. Toca **Escanear**. 2. Apunta la cámara al QR. 3. La app reconoce que es de asistencia y la registra. 4. Ve "¡Listo! +20 puntos y +20 J-lis" con el saldo nuevo. |
| **Flujos alternativos** | **Cámara sin permiso o no disponible**: explica cómo darle permiso y sugiere la cámara del teléfono (abre el navegador; si pide ingresar, completa la acción al volver). **QR vencido** (cambia cada 60 s; hay 10 s de gracia): "Ese QR ya venció, escaneá el que está en pantalla ahora". **Sesión cerrada**. **Ya registraste tu asistencia** (no suma de nuevo). **No es de tu unidad**. **QR que no es de la plataforma**: "No reconocemos este QR". **Actividad de 0 puntos**: se registra sin sumar. |
| **Estados** | Cargando: cámara abriéndose. Error: por causa. Éxito: puntos acreditados al instante. |
| **Postcondiciones** | Asistencia registrada, una sola vez por actividad. |

### UC-08 · Pagar en la tienda (incluido el saldo insuficiente)
| | |
|---|---|
| **Actores** | Participante, Usuario de tienda, Sistema |
| **Precondiciones** | La tienda generó un cobro (UC-15). |
| **Flujo principal** | 1. Toca **Escanear** y apunta al QR de cobro. 2. Ve la **pantalla de confirmación**: monto, nombre de la tienda, segundos restantes, su saldo. 3. Toca "Pagar". 4. Ve "¡Pago listo!" con su saldo nuevo. |
| **Flujos alternativos** | **Saldo insuficiente**: "No te alcanzan los J-lis. Te faltan 25", sin descontar nada; el cobro sigue vigente si consigue J-lis antes de que venza. **Cobro vencido** (2 minutos): "El cobro venció, pedile a la tienda uno nuevo". **Ya pagado**: "Este cobro ya fue pagado". **Cobro inexistente**. **Se cierra la pantalla sin pagar**: no pasa nada. **Doble toque en "Pagar"**: se descuenta una sola vez. **Escanea con la cámara del sistema sin sesión**: ingresa y vuelve a la confirmación. |
| **Estados** | Cargando: pagando. Error: por causa. Éxito: confirmación con saldo. |
| **Postcondiciones** | J-lis descontados una vez; los puntos no cambian. |

### UC-09 · Ranking
| | |
|---|---|
| **Actores** | Participante |
| **Precondiciones** | Sesión iniciada. |
| **Flujo principal** | 1. Abre **Ranking**. 2. Ve la tabla de su unidad: solo **puesto y puntaje**, sin nombres. 3. Aparte ve sus puntos individuales y los de su patrulla. Se actualiza solo cada 30 s. |
| **Flujos alternativos** | **Rovers**: ranking individual (puesto y puntaje de todos, sin nombres). **Empates**: comparten puesto. **Sin datos**: "Todavía no hay puntajes". **La tabla nunca marca cuál es su equipo**. |
| **Estados** | Vacío / cargando / sin conexión (último dato visto + aviso). |
| **Postcondiciones** | — |

### UC-10 · Historial
| | |
|---|---|
| **Actores** | Participante |
| **Precondiciones** | Sesión iniciada. |
| **Flujo principal** | 1. Abre **Historial** desde Inicio. 2. Ve sus movimientos de puntos y de J-lis, del más nuevo al más viejo: cuánto, por qué ("Misión aprobada", "Asistencia", "Compra en la tienda", "Devolución", "Penalización"), cuándo. 3. Puede filtrar por Puntos / J-lis. |
| **Flujos alternativos** | **Sin movimientos**: "Todavía no tenés movimientos". **Muchos movimientos**: carga por tandas. |
| **Estados** | Vacío / cargando / error. |
| **Postcondiciones** | — |

---

## B. Dirigente (siempre acotado a su unidad)

### UC-11 · Cola de validación → revisar → aprobar o rechazar
| | |
|---|---|
| **Actores** | Dirigente, Participante, Sistema |
| **Precondiciones** | Hay entregas pendientes de su unidad. |
| **Flujo principal** | 1. Abre **Cola**: contador de pendientes y la lista, las más antiguas primero. 2. Abre una entrega: quién entregó (y patrulla), misión y puntos, texto y fotos (se amplían). 3. Toca **Aprobar** → ve cuánto se acreditó. 4. O toca **Rechazar** → escribe el **motivo (obligatorio)** → confirma. 5. Pasa a la siguiente. |
| **Flujos alternativos** | **Cola vacía**: "No hay nada para revisar". **Otro dirigente la revisó antes**: "Esa entrega ya fue revisada" y vuelve a la cola. **Rechazar sin motivo**: error. **Foto todavía procesándose**: placeholder "se está procesando". **Entrega de otra unidad** (enlace directo): acceso denegado. **Misión por patrulla**: se muestra que se acreditará a toda la patrulla. |
| **Estados** | Vacío / cargando / error de conexión. Éxito: la entrega sale de la cola. |
| **Postcondiciones** | Entrega aprobada (puntos y J-lis acreditados) o rechazada con motivo. |

### UC-12 · Abrir sesión de asistencia → QR en pantalla completa → cerrar
| | |
|---|---|
| **Actores** | Dirigente, Participantes |
| **Precondiciones** | Actividad individual de su unidad. |
| **Flujo principal** | 1. Abre **Asistencia** y elige la actividad (y el turno, si quiere). 2. Toca **Abrir sesión**. 3. Toca **Mostrar QR**: pantalla completa con el QR que **cambia cada 60 s** y una cuenta regresiva. 4. Los participantes escanean. 5. Ve cuántos registraron asistencia. 6. Toca **Cerrar sesión**: el QR y el enlace dejan de funcionar. |
| **Flujos alternativos** | **Ya hay una sesión abierta en esa actividad**: la muestra en vez de abrir otra. **Actividad por equipo**: no tiene asistencia, lo manda a Cumplimientos (UC-13). **Sin actividades**. **Se pierde la conexión con la pantalla del QR**: reintenta y avisa. **Cerrar dos veces**: no pasa nada. **Enlace público de una sesión cerrada**: "La sesión de asistencia ya se cerró". |
| **Estados** | Vacío / cargando / error. |
| **Postcondiciones** | Sesión cerrada; asistencias y puntos acreditados quedan. |

### UC-13 · Marcar patrullas como cumplidas
| | |
|---|---|
| **Actores** | Dirigente |
| **Precondiciones** | Actividad **por equipo** de su unidad. |
| **Flujo principal** | 1. Abre **Cumplimientos** y elige la actividad. 2. Ve las patrullas de su unidad con su estado (sin marcar · cumplida · no cumplida). 3. Toca **Cumplida** en una patrulla y confirma → se acreditan los puntos a la patrulla y J-lis a cada integrante. 4. O toca **No cumplida**. |
| **Flujos alternativos** | **Cumplida es definitiva** (se avisa antes de confirmar; no se revierte desde acá). **No cumplida → cumplida**: se permite. **Ya estaba cumplida**: "Esta patrulla ya figura como cumplida". **Actividad de 0 puntos**: se marca sin acreditar. **Actividad individual**: no aplica. |
| **Estados** | Vacío: "No hay actividades por equipo". |
| **Postcondiciones** | Cumplimiento registrado una vez por patrulla y actividad. |

### UC-14 · Aplicar una penalización (y anularla)
| | |
|---|---|
| **Actores** | Dirigente |
| **Precondiciones** | Persona o patrulla de su unidad. |
| **Flujo principal** | 1. Abre **Penalizaciones**. 2. Elige **Persona** (busca por nombre o CI) o **Patrulla**. 3. Escribe la **cantidad** y el **motivo (obligatorio)**. 4. Confirma. 5. Ve el nuevo puntaje. |
| **Flujos alternativos** | **Sin motivo o cantidad inválida**: error. **Los puntos quedan negativos**: se permite y se avisa. **No toca los J-lis**: se aclara en pantalla. **Anular**: en la lista de penalizaciones recientes, "Anular" + motivo; se devuelve el puntaje (una sola vez). **Persona de otra unidad**: no aparece. |
| **Estados** | Vacío: "Sin penalizaciones". |
| **Postcondiciones** | Movimiento negativo registrado con quién y por qué. |

---

## C. Tienda

### UC-15 · Cobrar (monto → QR → pagado)
| | |
|---|---|
| **Actores** | Usuario de tienda, Participante |
| **Precondiciones** | Sesión de tienda. |
| **Flujo principal** | 1. En **Cobrar**, escribe el monto con el **teclado numérico**. 2. Toca "Generar cobro". 3. Ve el **QR** y una cuenta regresiva de 2 minutos. 4. El participante paga. 5. La pantalla pasa sola a **"Pagado por {nombre}"**. 6. Toca "Nuevo cobro". |
| **Flujos alternativos** | **Monto 0 o demasiado grande**: error. **Se vence sin pagar**: "El cobro venció" + "Generar otro". **Cancelar el cobro** antes de que lo paguen. **Saldo insuficiente del cliente**: la tienda sigue viendo el cobro pendiente. **Cliente paga justo al vencer**: gana lo que el servidor registre. **Pierde conexión**: el estado se refresca al volver. |
| **Estados** | Pendiente / pagado / vencido. |
| **Postcondiciones** | Cobro pagado una sola vez; queda en el historial del puesto. |

### UC-16 · Devolver
| | |
|---|---|
| **Actores** | Usuario de tienda |
| **Precondiciones** | La persona ya gastó J-lis en la tienda. |
| **Flujo principal** | 1. En **Devolver**, busca al participante por **CI** o por **nombre**. 2. Lo elige: ve cuánto gastó y cuánto se le puede devolver. 3. Escribe el **monto** y el **motivo (obligatorio)**. 4. Confirma. |
| **Flujos alternativos** | **Sin resultados**. **Búsqueda por nombre con menos de 3 letras**: pide más. **Monto mayor al tope** (lo gastado menos lo ya devuelto): "Solo se pueden devolver hasta X J-lis". **Persona que no gastó nada**: "no hay nada para devolver". **Sin motivo**: error. **No toca los puntos**: se aclara. |
| **Estados** | Vacío / error. Éxito: confirmación con el saldo nuevo de la persona. |
| **Postcondiciones** | Devolución registrada con quién, a quién, cuánto y por qué. |

### UC-16b · Historial del puesto
| | |
|---|---|
| **Actores** | Usuario de tienda |
| **Flujo principal** | Ve los cobros de su puesto (monto, estado, quién pagó, hora) y las devoluciones, con el total cobrado. |
| **Estados** | Vacío: "Todavía no hiciste cobros". |

---

## D. Administrador (panel Filament, ya construido)

Estos flujos existen en `/admin`. Se documentan para revisarlos con Leonardo; **no llevan wireframes**
porque la interfaz es la de Filament.

### UC-17 · Flujos del admin
| Flujo | Dónde | Estado |
|---|---|---|
| **Carga CSV de participantes** → validar sin guardar → importar (todo o nada) con errores línea por línea | Importar participantes | Hecho |
| **Crear misión** (unidades, puntaje, lanzamiento, vencimiento) y **crear actividad con turnos** (cupo) | Misiones / Talleres y estaciones | Hecho |
| **Ranking completo** con nombres y exportación CSV | Rankings | Hecho |
| **Reseteo de PIN** y cierre de sesiones | Participantes | Hecho |
| Validación global, movimientos, auditoría | Juego / Registros | Hecho |

> La carga por CSV ya no genera tarjetas de activación: los participantes crean su PIN con solo su CI.

---

## Transversales

- **Cerrar sesión**: desde Inicio (y desde el menú de cada rol).
- **Sesión vencida / cerrada por el admin**: vuelve a Ingresar sin perder el destino (si venía de un QR, lo completa).
- **Sin conexión**: aviso persistente; las acciones no se pierden a medias (el servidor decide).
- **Acceso denegado**: pantalla propia ("Esto no es para tu usuario").

---

## Huecos que esta etapa deja a la vista (para las fases de pantallas)

El dominio ya cubre las reglas, pero estas **consultas de lectura** todavía no existen y las pantallas
las necesitan:

1. **Historial** del participante (UC-10): lista unificada de movimientos de puntos y J-lis.
2. **Inicio** (UC-03): un resumen con saldos, misiones nuevas y próxima reserva.
3. **Actividades visibles** de una unidad (UC-06), equivalente a `Mision::visiblesPara`.
4. **Estado de cada misión** para el participante (UC-04): nueva, pendiente, aprobada, rechazada con motivo.
5. **Historial del puesto** de la tienda (UC-16b).
6. **Penalizaciones recientes** y **cumplimientos por patrulla** para el dirigente (UC-13, UC-14).
7. **Conteo de asistentes** de una sesión abierta (UC-12).
