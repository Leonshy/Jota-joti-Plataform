# Legajo Técnico — Plataforma JOTA-JOTI 2026

> Formato estándar de webparaguay. Documentos operativos hermanos: `CLAUDE.md` (contexto de
> repo para los agentes) y `PLAN.md` (fases de build). Las decisiones abiertas están listadas
> en §8.3 con un valor por defecto: si nadie las cambia, se construye con el valor por defecto.

---

## 0. Ficha del proyecto

| Campo | Valor |
|---|---|
| **Nombre** | Plataforma JOTA-JOTI 2026 — misiones, puntos, talleres y tienda J-lis |
| **Cliente** | Grupo Scout General Genes (Fernando de la Mora) |
| **Participantes** | G.S. General Genes y G.S. General Aquino |
| **Lugar del evento** | Estación Terrena de Areguá |
| **Fecha del evento** | **A confirmar** (D1) |
| **Estado** | Descubrimiento cerrado — listo para UX |
| **Responsable** | Leonardo Chi (webparaguay) |
| **Equipo** | 2 desarrolladores senior + 1 junior avanzado, con asistencia de IA |
| **Tipo** | Proyecto de cliente — aplicación web de uso en vivo durante un evento |
| **Versión del legajo** | 1.0 |
| **Fecha** | 15 de octubre de 2026 |

---

## 1. Resumen ejecutivo

El JOTA-JOTI es el evento mundial de comunicación del movimiento scout. El Grupo Scout General
Genes lo organiza en la Estación Terrena de Areguá, con la participación del Grupo Scout General
Aquino. Este proyecto construye la **plataforma de juego que los chicos usan desde el celular
durante el evento**: reciben misiones que se liberan por horario, entregan sus resultados con
texto o imágenes, reservan turnos en talleres y estaciones JOTA, marcan asistencia escaneando un
QR, acumulan puntos para el ranking y ganan **J-lis** (la moneda del evento, de *Jamboree* + *flor
de lis*) que canjean por cosas físicas en la tienda.

No es una herramienta de organización ni de inscripción: todo lo previo al evento lo carga el
administrador. La plataforma vive durante el evento y su trabajo es que la dinámica de juego
funcione sin papeles, sin planillas y sin discusiones sobre quién tiene cuántos puntos.

**Por qué así:** llevar puntajes, monedas y asistencias a mano con dos grupos, tres unidades y
muchas patrullas es lento y propenso a errores, y los errores de puntaje en un juego con premios
son el problema más visible del evento. Una plataforma con registros contables de puntos y
monedas, validación por dirigentes y QR deja todo trazable. Se construye para esta edición, con
el modelo pensado para reutilizarse en 2027 si la experiencia es buena.

---

## 2. Problema y oportunidad

### El dolor concreto

- **Puntajes a mano:** sumar puntos individuales y por patrulla, con bonos y penalizaciones, en
  papel o planilla, genera errores y reclamos.
- **Moneda física:** manejar billetes de juego implica imprimirlos, que se pierdan, se presten o
  se falsifiquen. Con J-lis digitales, cada movimiento queda registrado.
- **Asistencia a talleres:** pasar lista quita tiempo del taller.
- **Reservas de turnos:** sin control, dos patrullas aparecen en el mismo turno o un chico se
  anota en dos talleres a la misma hora.
- **Ranking:** calcularlo en el momento de la premiación es lento y discutible.

### Quién paga y por qué

El Grupo Scout General Genes, organizador del evento. La modalidad económica está por definir
(D12).

### Oportunidad para webparaguay

1. **Patrón reutilizable de "plataforma de gamificación de eventos"**: misiones, validación,
   registro de puntos, moneda interna, QR de asistencia y de cobro. Aplica a otros grupos
   scouts, a eventos distritales o nacionales de la ASP, a colegios (semana del estudiante,
   ferias de ciencias, campamentos) y a eventos corporativos.
2. **Vitrina:** cientos de chicos, dirigentes y padres usando una plataforma de webparaguay en
   un evento con visibilidad dentro del movimiento scout.
3. **Reutilización 2027:** si funciona, la próxima edición cuesta una fracción de esta.

---

## 3. Visión y propuesta de valor

**Una app en el celular donde cada chico ve qué misión tiene, entrega, suma, compra y compite,
y donde el dirigente valida en segundos y nadie discute un puntaje porque todo queda registrado.**

| Diferenciador | Cómo se aprovecha |
|---|---|
| **Servidores locales en Paraguay** | Latencia baja desde Areguá. Datos de menores (CI, nombre) dentro del país |
| **Atención cercana** | Soporte en vivo durante el evento: el responsable del proyecto está en el lugar |
| **IA en el desarrollo** | Proceso completo (UX, copy, diseño, backend, seguridad, QA) con un equipo de tres |
| **Stack propio y probado** | Laravel + Livewire + Filament sobre Plesk. Cero infraestructura nueva |

---

## 4. Alcance por etapas (columna vertebral)

### MVP — Plataforma para el JOTA-JOTI 2026

**Entra:**

**4.1 Acceso**
- Ingreso con **número de CI + PIN de 4 dígitos**. No hay registro abierto: solo entran los CI
  cargados por el administrador.
- **Primer ingreso solo con el CI**: la persona ingresa su CI y crea su PIN. No hay código ni
  tarjeta. Prioriza que entren rápido y fácil.
- El administrador puede resetear el PIN: la persona vuelve a crearlo con su CI.
- **Sin bloqueo ni límite de intentos**: equivocarse no traba a nadie.

**4.2 Estructura del evento**
- Grupos scouts (General Genes, General Aquino).
- Unidades: **Tropa**, **Comunidad de Caminantes**, **Clan Rover**. Cada unidad tiene un modo de
  ranking: por equipo (Tropa, Caminantes) o individual (Rovers).
- Patrullas o equipos, que pertenecen a un grupo y a una unidad. Los Rovers no tienen patrulla.
- Carga de participantes y patrullas por el administrador, uno por uno o por importación CSV,
  antes del evento. Solo el administrador agrega participantes durante el evento.

**4.3 Misiones**
- Cada misión tiene título, descripción, modalidad (**individual** o **por patrulla**), puntaje,
  unidades destino, **hora de lanzamiento** y **vencimiento opcional**.
- Antes de la hora de lanzamiento, la misión no se ve. El administrador puede cerrarla
  manualmente.
- Entrega con texto y/o imágenes.
- En misiones por patrulla, entrega cualquier integrante en nombre de la patrulla.
- El dirigente de la unidad aprueba o rechaza. Al rechazar, indica un motivo obligatorio.
- Una misión rechazada se puede volver a entregar mientras siga abierta (no vencida ni cerrada).
- La aprobación otorga siempre el puntaje fijo de la misión. No hay puntajes parciales.

**4.4 Talleres y estaciones JOTA ("actividades")**
- Cada actividad tiene tipo (taller o estación JOTA), modalidad (**individual** o **por
  equipo**), unidades destino, puntaje y turnos con horario.
- **Reservas:**
  - Las actividades individuales tienen cupo por turno.
  - En las actividades por equipo, reserva la patrulla.
  - **Regla de no superposición:** ninguna persona puede tener dos reservas que se pisen en
    horario, sumando sus reservas individuales y las de su patrulla.
- **Asistencia en actividades individuales:**
  - El dirigente activa una sesión de asistencia y obtiene un **enlace público** que muestra un
    **QR que cambia cada 60 segundos** mientras la sesión esté activa.
  - Cualquier participante que lo escanee estando logueado marca asistencia y recibe al
    instante los puntos y los J-lis. No necesita reserva ni aprobación.
  - Cada persona suma una sola vez por actividad.
- **Actividades por equipo:** no registran asistencia. El dirigente marca a cada patrulla como
  **cumplida / no cumplida**, y al marcarla cumplida se acreditan los puntos.

**4.5 Registros de puntos y J-lis**
- Tres registros contables independientes, de solo agregar (cada movimiento es una fila, nunca
  se edita):
  1. **Puntos individuales** (por persona)
  2. **Puntos de patrulla** (por patrulla)
  3. **J-lis** (siempre por persona — la moneda nunca es del equipo)
- **Reglas de acreditación:**

| Evento | Puntos individuales | Puntos de patrulla | J-lis |
|---|---|---|---|
| Misión individual aprobada | +P a la persona | — | +P a la persona |
| Misión por patrulla aprobada | — | +P a la patrulla (una vez) | +⌈P / n⌉ a cada integrante |
| Asistencia a actividad individual (QR) | +P a la persona | — | +P a la persona |
| Actividad por equipo marcada cumplida | — | +P a la patrulla (una vez) | +⌈P / n⌉ a cada integrante |
| Contacto JOTI aprobado (internet) | +P a la persona | — | +P a la persona |
| Contacto JOTA aprobado (radio) | — | +P a la patrulla (una vez) | +⌈P / n⌉ a cada integrante |
| Penalización a persona | −X a la persona | — | sin efecto |
| Penalización a patrulla | — | −X a la patrulla | sin efecto |
| Cobro en la tienda | sin efecto | sin efecto | −M a la persona |
| Devolución en la tienda | sin efecto | sin efecto | +M a la persona |

  *P = puntaje de la misión o actividad; n = integrantes de la patrulla al momento de acreditar;
  ⌈ ⌉ = redondeo hacia arriba. Ejemplo: misión de patrulla de 50 puntos con 3 integrantes → 50
  al registro de la patrulla y 17 J-lis a cada uno.*

- **Penalizaciones:** apartado propio, con motivo obligatorio y cantidad. Se aplican a una persona
  o a una patrulla, según el caso. Las aplica el dirigente de la unidad o el administrador.

**4.5 bis Registro de contactos JOTA y JOTI** *(agregado el 16/10/2026, a pedido del grupo)*
- En el Inicio del participante hay dos tarjetas, **Registro JOTA** (radio) y **Registro JOTI** (internet).
- **JOTA** copia el libro de guardia: fecha, hora, estación (indicativo), RST de emisión y de recepción, frecuencia, modo de
  emisión, observaciones y las casillas QSL R / QSL E. Lo carga cualquier integrante de la patrulla y **suma a la patrulla**
  (en los Rovers, a cada Rover). Es **local** si el indicativo empieza con **ZP** (Paraguay). No lleva captura.
- **JOTI** pide los datos del grupo con el que se habló (grupo scout, ciudad, unidad y país) y **una captura de pantalla de la
  conversación**. **Suma a la persona.** Es **local** si el país es Paraguay. Sin tope de contactos por persona.
- Cada contacto lo **valida el dirigente de la unidad o el administrador**: aprueba o rechaza con motivo. **Un contacto
  rechazado queda rechazado: no se puede reenviar ni volver a revisar.**
- El administrador define en el panel **cuatro puntajes**: JOTA local, JOTA exterior, JOTI local y JOTI exterior. Los LisCoins
  son el mismo monto (con la regla de siempre para la patrulla). **Sin puntaje definido no se puede aprobar.**
- **Datos de terceros, mínimos:** se guardan solo el grupo, la ciudad, la unidad y el país del otro scout, y en JOTA el
  indicativo y las observaciones. **Nunca nombres, teléfonos ni correos.** Las capturas siguen las reglas de las fotos de las
  misiones (disco privado, re-codificadas sin EXIF, solo las ven quien las cargó, el dirigente de la unidad y el admin) y se
  borran después del evento con `jota:purgar-entregas`.

**4.6 Tienda**
- El usuario de tienda ingresa el monto y genera un **QR de cobro**, de un solo uso y con
  vencimiento corto.
- El participante lo escanea desde la app, **ve el monto y confirma**, y se descuenta.
- El saldo de J-lis **nunca puede quedar negativo**: si no alcanza, el cobro se rechaza.
- La pantalla de la tienda ve la confirmación en el momento.
- **Devolución:** el usuario de tienda busca al participante por CI o nombre y le acredita J-lis.
- No se lleva inventario ni detalle de lo comprado.

**4.7 Ranking**
- **Tropa y Caminantes**, puntaje del equipo = puntos de patrulla + suma de los puntos
  individuales de sus integrantes.
  - El participante ve la tabla con posición y puntaje, **sin nombres y sin marcar en qué lugar
    está su equipo**.
  - Por otro lado ve su puntaje individual y el puntaje de su patrulla.
- **Rovers:** ranking individual. Muestra posición y puntaje de todos, sin nombres y sin marcar
  la posición propia.
- **Administrador:** ve rankings completos con nombres, por equipo e individual, en todas las
  unidades. Así se definen los premios al mejor equipo y al mejor beneficiario.
- Los rankings se calculan por unidad, mezclando los dos grupos scouts (D7).

**4.8 Roles**

| Rol | Puede |
|---|---|
| **Beneficiario** | Ver misiones y actividades de su unidad, entregar, reservar, escanear QR de asistencia, pagar en la tienda, ver sus saldos y el ranking anónimo |
| **Dirigente** | Todo lo anterior en modo consulta, **solo sobre su unidad**: validar entregas, activar sesiones de asistencia, marcar actividades por equipo como cumplidas, aplicar penalizaciones |
| **Tienda** | Generar QR de cobro, hacer devoluciones, ver el historial de cobros de su puesto |
| **Administrador** | Todo: participantes, patrullas, unidades, misiones, actividades, turnos, validación en cualquier unidad, rankings completos, códigos de activación, reseteo de PIN, exportación de resultados |

**4.9 Operación y cierre**
- Registro de auditoría de todo movimiento de puntos y J-lis: quién, qué, cuándo, por qué.
- Exportación de rankings y saldos a CSV para la premiación.
- Comando para **borrar las imágenes de las entregas** después del evento. Lo ejecuta el
  responsable del grupo.

**NO entra en el MVP:**

- **Bono por rapidez** a los primeros que entregan (pasa a la edición 2027)
- Manada (lobatos) como unidad participante
- Registro abierto, inscripción online, autorizaciones de padres
- Acceso de padres o talleristas externos
- Inventario de la tienda o catálogo de productos
- Modo sin conexión: la plataforma requiere internet
- App nativa: es web responsiva, con opción de "agregar a pantalla de inicio"
- Chat, notificaciones push, integración con plataformas oficiales de JOTA-JOTI
- Puntajes parciales en la aprobación de misiones
- Multi-evento o multi-edición en simultáneo

### Edición 2027 — si la experiencia es buena

- Bono configurable por orden de entrega (por ejemplo +20 / +10 / +5)
- Configuración de edición: clonar unidades, misiones y actividades de un año al siguiente
- Congelamiento del ranking antes de la premiación
- Evaluar la Manada como unidad, con un flujo adaptado a su edad

### Producto derivado (interno de webparaguay)

Extracción del núcleo de **misiones + validación + registros de puntos y moneda + QR rotativo +
QR de cobro** como base reutilizable para gamificar eventos. Se documenta al cierre.

---

## 5. Especificación técnica

> Fuente de verdad del stack y de las reglas para agentes: `CLAUDE.md`. Resumen acá.

### 5.1 Arquitectura

Monolito Laravel renderizado en servidor. **Sin SPA y sin API separada.**

- **Panel de administración:** Filament.
- **Interfaces móviles** (beneficiario, dirigente, tienda): Livewire + Alpine.
- **Tiempo real** (QR rotativo, confirmación de cobro, cola de validación, ranking): polling
  de Livewire. No hay WebSockets, porque no hacen falta y complican el despliegue en Plesk.

```
Celular del participante / dirigente / tienda
   │
   ├── /app            Beneficiario   (Livewire, mobile-first)
   ├── /dirigente      Dirigente      (Livewire, mobile-first)
   ├── /tienda         Tienda         (Livewire, mobile-first)
   ├── /asistencia/{sesion}  Página pública firmada con QR rotativo
   └── /admin          Administrador  (Filament)
            │
      Laravel 13 · PHP 8.3+
            │
   ┌────────┼──────────────┐
 MySQL 8   Disco privado    Cola (database driver)
           (imágenes)       para procesar imágenes
```

### 5.2 Stack

Laravel 13 · PHP 8.3+ · MySQL 8 · Livewire 4 · Alpine.js · Tailwind CSS v4 · Filament
(panel admin) · Vite · Pest · Playwright. Despliegue en el Plesk de webparaguay. Detalle de
paquetes y versiones en `CLAUDE.md` §3.

### 5.3 Modelo de datos (resumen)

| Tabla | Campos clave |
|---|---|
| `grupos` | nombre |
| `unidades` | nombre (Tropa, Caminantes, Rovers), `modo_ranking` (equipo / individual) |
| `patrullas` | nombre, `grupo_id`, `unidad_id` |
| `users` | `ci` (único), nombre, `rol` (beneficiario / dirigente / tienda / admin), `grupo_id`, `unidad_id`, `patrulla_id` nullable, `pin_hash`, `codigo_activacion_hash`, `activado_at`, `bloqueado_hasta` |
| `misiones` | título, descripción, `modalidad`, `puntos`, `lanza_at`, `vence_at` nullable, `cerrada_at` nullable · pivote `mision_unidad` |
| `entregas` | `mision_id`, `user_id` (quien entrega), `patrulla_id` nullable, texto, `estado` (pendiente / aprobada / rechazada), `motivo_rechazo`, `revisado_por`, `revisado_at` |
| `entrega_imagenes` | `entrega_id`, ruta en disco privado |
| `actividades` | tipo (taller / estación JOTA), título, `modalidad`, `puntos` · pivote `actividad_unidad` |
| `turnos` | `actividad_id`, `inicio`, `fin`, `cupo` nullable |
| `reservas` | `turno_id`, `user_id` nullable, `patrulla_id` nullable |
| `sesiones_asistencia` | `actividad_id`, `turno_id` nullable, `abierta_por`, `secreto`, `abierta_at`, `cerrada_at` |
| `asistencias` | `actividad_id`, `user_id`, `sesion_id` — único (`actividad_id`, `user_id`) |
| `cumplimientos_equipo` | `actividad_id`, `patrulla_id`, `cumplida`, `marcado_por` — único (`actividad_id`, `patrulla_id`) |
| `mov_puntos_individuales` | `user_id`, `cantidad` (±), `origen_type` / `origen_id`, `motivo`, `registrado_por` |
| `mov_puntos_patrulla` | `patrulla_id`, `cantidad` (±), `origen_type` / `origen_id`, `motivo`, `registrado_por` |
| `mov_jlis` | `user_id`, `cantidad` (±), `origen_type` / `origen_id`, `motivo`, `registrado_por` |
| `cobros` | `token` (único), `monto`, `creado_por` (tienda), `vence_at`, `pagado_por` nullable, `pagado_at` nullable |

**Saldos:** se cachean en columnas (`users.puntos`, `users.jlis`, `patrullas.puntos`) y se
actualizan **en la misma transacción** que el movimiento, con `lockForUpdate`. Los movimientos
son la fuente de verdad. Un comando `jota:reconciliar` recalcula los saldos desde los movimientos
y reporta diferencias.

**Idempotencia** (garantizada por restricciones únicas en base de datos, no solo por código):
- una aprobación por entrega
- una asistencia por persona y actividad
- un cumplimiento por patrulla y actividad
- un pago por cobro

### 5.4 Reglas de negocio críticas

1. **Visibilidad de misiones:** el participante ve una misión solo si su unidad está entre las
   destino y `now() >= lanza_at`. Puede entregar solo si la misión no está vencida ni cerrada.
2. **Entregas:**
   - Una entrega pendiente bloquea nuevas entregas de la misma persona (individual) o de la
     misma patrulla (por patrulla).
   - Después de una aprobación no se puede volver a entregar.
   - Después de un rechazo se puede volver a entregar mientras la misión siga abierta.
3. **Alcance del dirigente:** solo ve y valida entregas, sesiones, cumplimientos y penalizaciones
   de su unidad. El administrador, de todas. Se aplica con Policies, no en las vistas.
4. **Reparto de J-lis por patrulla:** ⌈P / n⌉, donde n son los integrantes activos de la patrulla
   al momento de la acreditación.
5. **No superposición de reservas:** al reservar, se rechaza si el intervalo se pisa con alguna
   reserva de la persona o de su patrulla. En una reserva por equipo, se verifica contra las
   reservas individuales de todos los integrantes (D3). La validación corre dentro de una
   transacción con bloqueo, para que dos reservas simultáneas no pasen ambas.
6. **Cupo:** el conteo de reservas del turno se hace con bloqueo, para que el cupo no se exceda
   por concurrencia.
7. **QR de asistencia rotativo:**
   - Token = HMAC(`secreto` de la sesión, ventana de 60 s).
   - Se acepta la ventana actual con 10 s de gracia por latencia.
   - La página pública regenera el QR en el navegador cada 60 s, a partir de un endpoint firmado.
   - El QR codifica una URL de la app. Al escanearla, el participante logueado registra
     asistencia.
   - Cerrar la sesión invalida el enlace público.
8. **Cobro en tienda:**
   - El token del cobro es aleatorio, de un solo uso y vence a los 2 minutos.
   - El pago ejecuta en una transacción: verificar que no esté pagado ni vencido → bloquear el
     saldo del participante → verificar saldo ≥ monto → insertar el movimiento −M → marcar como
     pagado.
   - El participante confirma el monto en pantalla antes de pagar.
9. **Ranking** (consulta con caché corta, 30 s, para no recalcular en cada polling):
   - Tropa y Caminantes: `patrullas.puntos + SUM(users.puntos de sus integrantes)`, ordenado.
   - Rovers: `users.puntos` de los participantes de la unidad.
   - La vista del beneficiario no expone ni nombres ni identificadores de otros equipos o
     personas, tampoco en el HTML ni en las respuestas de Livewire.

### 5.5 Autenticación y seguridad

- **Login con CI + PIN de 4 dígitos.** Decisión de producto: practicidad por sobre seguridad
  (ver R3).
- **Sin límite de intentos ni bloqueo**, ni por CI ni por IP: equivocarse el PIN no traba a
  nadie. Se acepta el riesgo (R3) a cambio de que entren rápido.
- **Activación:** solo con el CI, la persona crea su PIN. El reseteo por el administrador deja la
  cuenta sin PIN para que lo vuelva a crear.
- **Sesiones largas** (duración del evento). El administrador puede cerrar todas las sesiones
  de un usuario.
- **Imágenes:**
  - Disco privado, servidas por una ruta autorizada (no públicas).
  - Validación por contenido real (MIME), solo JPEG, PNG o WebP.
  - Se redimensionan a un máximo de 1600 px y se reescriben sin EXIF (elimina la ubicación
    GPS de las fotos de menores).
  - Límite de 8 MB por archivo y 4 imágenes por entrega.
- **Datos personales:** solo CI, nombre, grupo, unidad y patrulla. Sin teléfono, sin email, sin
  fecha de nacimiento. Excepción acordada el 16/10/2026 (§4.5 bis): los contactos JOTA y JOTI guardan
  datos mínimos de terceros (grupo, ciudad, unidad, país, indicativo), nunca nombres ni contactos.
- **Auditoría:** cada movimiento guarda quién lo registró. El panel muestra el historial por
  persona y por patrulla.
- **Pentest con strix** antes de producción: cero hallazgos Críticos o Altos.

### 5.6 Interfaces

**Beneficiario** (móvil):
- Inicio con saldo de puntos, saldo de J-lis y puntaje de su patrulla.
- Misiones disponibles, con estado propio o de la patrulla.
- Actividades y reservas.
- Botón **Escanear**, el mismo para asistencia y para pagar.
- Ranking de su unidad.
- Historial de movimientos.

**Dirigente** (móvil):
- Cola de validación con contador de pendientes.
- Revisión de entrega: ver texto e imágenes, aprobar o rechazar con motivo.
- Sesiones de asistencia: abrir y cerrar, y mostrar el QR en pantalla completa.
- Cumplimientos por equipo.
- Penalizaciones.

**Tienda** (móvil):
- Teclado numérico → QR de cobro → confirmación "Pagado por {nombre}".
- Devoluciones.
- Historial del puesto.

**Admin** (Filament):
- Estructura del evento: grupos, unidades y patrullas.
- Participantes: importación CSV y reseteo de PIN.
- Misiones, actividades y turnos.
- Validación global.
- Rankings completos.
- Movimientos y auditoría.
- Exportaciones.

### 5.7 Escaneo de QR

- **Escáner dentro de la app** (cámara en el navegador). Es el camino principal, porque así el
  escaneo ocurre en la misma sesión logueada.
- Si un chico escanea con la cámara del sistema, la URL abre el navegador predeterminado. En
  iOS, eso puede ser un navegador sin sesión: en ese caso pide login y después completa la
  acción.

### 5.8 Requerimientos no funcionales

| Requerimiento | Objetivo |
|---|---|
| Concurrencia | 300 usuarios activos simultáneos sin degradación (validar con prueba de carga) |
| Tiempo de respuesta | p95 < 500 ms en acciones de beneficiario con 4G |
| Disponibilidad durante el evento | 100 % en la ventana del evento. Despliegues congelados durante el evento |
| Integridad | 0 diferencias en `jota:reconciliar` al cierre |
| Accesibilidad | WCAG 2.1 AA en las interfaces móviles |
| Navegadores | Chrome y Safari móviles de los últimos 3 años, Android 9+ / iOS 15+ |
| Respaldo | Respaldo de base cada hora durante el evento + diario el resto del tiempo |
| Idioma | Español paraguayo, con tono cercano y scout |

---

## 6. Modelo de negocio y monetización

**Modalidad: a definir (D12).** Opciones:

- **a) Aporte de webparaguay al movimiento scout:** desarrollo sin cargo. El grupo cubre solo el
  hosting del período, o tampoco eso. Retorno: vitrina y patrón reutilizable.
- **b) Precio a costo:** el grupo cubre horas a tarifa reducida más hosting.
- **c) Mixto:** desarrollo como aporte; la reutilización en ediciones siguientes o en otros
  grupos y eventos se cobra como licencia o servicio por evento.

**Retorno estratégico (independiente de la modalidad):** el patrón "gamificación de eventos" se
vende después a colegios, a la ASP y a eventos corporativos con un costo marginal bajo.

---

## 7. Esfuerzo y recursos por etapa

| Fase | Descripción | Horas | Rol principal |
|---|---|---|---|
| 0 | Descubrimiento (hecho) y andamiaje del repo | 4–6 | Senior |
| 1 | UX: flujos por rol y wireframes | 10–14 | Senior + ux-flow-designer |
| 2 | Copywriting y sistema de diseño | 10–14 | Senior + impeccable |
| 3 | Núcleo: modelo, auth CI+PIN, registros contables | 16–22 | Senior |
| 4 | Misiones y entregas (imágenes, validación) | 14–18 | Senior + junior |
| 5 | Actividades: turnos, reservas, QR rotativo, cumplimientos | 14–18 | Senior |
| 6 | Tienda y J-lis: QR de cobro y devoluciones | 8–12 | Senior |
| 7 | Ranking y penalizaciones | 6–8 | Junior + senior |
| 8 | Panel Filament, importación, exportaciones | 12–16 | Junior + senior |
| 9 | Seguridad (strix) y endurecimiento | 6–8 | Senior |
| 10 | QA: Pest, Playwright E2E, prueba de carga | 10–14 | Junior + senior |
| 11 | Despliegue, ensayo general con dirigentes, soporte en el evento y cierre | 8–12 | Senior |
| | **Total** | **118–162 h** | |

**Rango calendario:** 5 a 7 semanas conviviendo con otros proyectos. El ensayo general con
dirigentes tiene que ser **al menos una semana antes** del evento.

**Costos de terceros:**

| Concepto | Costo |
|---|---|
| Hosting Plesk | Infraestructura propia — costo interno |
| SSL Let's Encrypt | Gratis |
| Filament, Livewire y paquetes | Gratis (MIT) |
| Dominio o subdominio | Subdominio de webparaguay sin costo, o dominio del grupo (D13) |
| Créditos de IA | Cubierto por la operación |

---

## 8. Riesgos, supuestos y decisiones abiertas

### 8.1 Riesgos

| # | Riesgo | Prob. | Impacto | Mitigación |
|---|---|---|---|---|
| R1 | Falla la conectividad en la Estación Terrena | Baja | **Crítico** | Prueba de señal en el lugar durante el ensayo. Plan B en papel para validaciones, con carga posterior por el admin |
| R2 | Errores de saldo por concurrencia (doble acreditación, saldo negativo) | Media | **Crítico** | Transacciones con bloqueo, restricciones únicas, `jota:reconciliar`, tests de concurrencia |
| R3 | Robo de cuenta: cualquiera que conozca un CI puede crear el PIN primero o adivinar uno de 4 dígitos | Media | Medio | **Riesgo aceptado** (decisión de producto: practicidad). Mitigar operativamente: activar en el momento de la acreditación, reseteo del admin, auditoría de cada movimiento |
| R4 | Cuello de botella de validación: pocos dirigentes y muchas entregas | **Alta** | Alto | Cola de validación optimizada para revisar en segundos. Contador visible. El admin valida cualquier unidad |
| R5 | Chicos sin celular o sin batería | **Alta** | Medio | La sesión puede abrirse en el celular de un compañero (cerrar sesión es un toque). Puntos de carga en el lugar |
| R6 | El escaneo con la cámara del sistema abre un navegador sin sesión (iOS) | Media | Medio | Escáner dentro de la app como camino principal. Login y retorno a la acción |
| R7 | Compartir foto del QR de asistencia | Media | Bajo | Riesgo aceptado por el cliente. El QR rota cada minuto y la sesión se cierra al terminar |
| R8 | Fotos de menores en las entregas | Media | Alto | Disco privado, EXIF eliminado, borrado posterior al evento, acceso solo de dirigentes de la unidad y del admin |
| R9 | Se pide alcance nuevo durante el build | Media | Medio | §4 "NO entra". Lo nuevo va a la edición 2027 |
| R10 | Carga de participantes tardía o con errores | Media | Alto | Importación CSV con validación y reporte de errores. Fecha límite de carga en el plan |
| R11 | Mal uso de la identidad visual de JOTA-JOTI o de la marca scout | Baja | Bajo | Confirmar con el grupo y la ASP qué logos se pueden usar (D11) |
| R12 | Se cae el servidor durante el evento | Baja | **Crítico** | Despliegues congelados, monitoreo, respaldo horario, responsable técnico en el lugar |

### 8.2 Supuestos

- Hay señal móvil y/o wifi suficiente en la Estación Terrena de Areguá.
- La mayoría de los participantes tiene celular propio con cámara.
- El administrador carga participantes y patrullas antes del evento.
- Hay al menos un dirigente con acceso por unidad durante todo el evento.
- La Manada no participa en la plataforma.
- El grupo es responsable de la política de conservación y borrado de las imágenes.

### 8.3 Decisiones abiertas (con valor por defecto)

| # | Decisión | Valor por defecto |
|---|---|---|
| D1 | Fecha y horario exacto del evento | — (necesario para el calendario) |
| D2 | Quién reserva en nombre de la patrulla | Cualquier integrante |
| D3 | Una reserva de patrulla choca con la reserva individual de un integrante | Se bloquea y se informa quién tiene el choque |
| D4 | Las estaciones JOTA dan puntos | Sí, configurable igual que los talleres (puede ser 0) |
| D5 | Cupo de patrullas por turno en actividades por equipo | Cupo opcional por turno |
| D6 | El dirigente ve nombres en el ranking de su unidad | No: solo el administrador ve nombres |
| D7 | El ranking mezcla los dos grupos scouts | Sí, un ranking por unidad |
| D8 | Largo del PIN y política de bloqueo | **Resuelta:** 4 dígitos, sin bloqueo ni límite de intentos |
| D9 | Congelar el ranking antes de la premiación | El admin deja de validar; congelamiento formal en 2027 |
| D10 | Integrante que cambia de patrulla durante el evento | Lo ya acreditado queda; lo nuevo va a la nueva patrulla |
| D11 | Identidad visual (logos JOTA-JOTI, del grupo, ASP) | Identidad propia del evento con flor de lis genérica, sin logos oficiales hasta confirmar |
| D12 | Modalidad económica | A definir con el grupo |
| D13 | Dominio | Subdominio de webparaguay (ej. `jotajoti.webparaguay.com`) |
| D14 | Los puntos pueden quedar negativos por penalización | Sí, los puntos pueden ser negativos (los J-lis no) |

---

## 9. Métricas de éxito / KPIs

### Al cierre del desarrollo

| Métrica | Objetivo |
|---|---|
| Hallazgos de seguridad Críticos/Altos de strix | 0 |
| Cobertura de reglas críticas (§5.4) con tests Pest | 100 % |
| Flujos E2E Playwright por rol | Todos en verde |
| Prueba de carga a 300 usuarios | p95 < 500 ms, 0 errores |
| Participantes cargados | 100 %, una semana antes |
| Ensayo general con dirigentes | Hecho, sin bloqueantes abiertos |

### Durante y después del evento

| Métrica | Objetivo |
|---|---|
| Participantes activados (crearon PIN) | ≥ 95 % en la primera hora |
| Diferencias en `jota:reconciliar` | **0** |
| Cobros con error o saldo negativo | **0** |
| Mediana entre entrega y validación | < 10 min |
| Caídas del servicio en la ventana del evento | 0 |
| Cuentas tomadas por terceros | 0 |
| Reclamos de puntaje sin respaldo en la auditoría | 0 |
| Imágenes borradas tras el evento | Sí, en el plazo que defina el grupo |

### Interno de webparaguay

| Métrica | Objetivo |
|---|---|
| Horas reales vs. estimadas | Desvío < 20 % |
| Núcleo de gamificación extraído y documentado | Sí, al cierre |
| Decisión sobre la edición 2027 | Tomada dentro de los 60 días posteriores al evento |

> El KPI que manda es **"diferencias en la reconciliación: 0"**. En un juego con premios, un
> punto o un J-lis mal contado es lo que todos recuerdan del evento. Todo lo demás es secundario
> frente a la integridad de los registros.
