# Informe de seguridad — Fase 9

Revisión del 16/10/2026 sobre la plataforma JOTA-JOTI 2026 (Laravel 12, Livewire 4, Filament 5).
Método: revisión manual del código + batería de tests automáticos (`tests/Feature/Seguridad`) + auditoría de
dependencias. **No se pudo correr `strix`**: está instalado pero necesita las credenciales de un modelo de IA y un
Docker en marcha, y no hay ninguna configurada. Queda pendiente contra el entorno de staging (ver más abajo).

**Resultado: 0 hallazgos Críticos, 0 Altos abiertos.** Los 7 hallazgos de abajo se corrigieron y tienen test.

## Hallazgos corregidos

| # | Severidad | Hallazgo | Corrección |
|---|---|---|---|
| 1 | Alto | La app no mandaba ningún encabezado de seguridad (sin CSP, se podía incrustar en otra página, sin `nosniff`). | `EncabezadosDeSeguridad` (CSP restrictiva de mismo origen, `X-Frame-Options: DENY`, `frame-ancestors 'none'`, `nosniff`, `Referrer-Policy`, `Permissions-Policy` con cámara solo del propio sitio, COOP/CORP, HSTS con https, `noindex`). |
| 2 | Medio | Los encabezados no llegaban al panel `/admin` ni a las páginas de error (tienen otra pila de middleware). | El middleware pasó a ser global. Test: `/admin/login`, 404 y JSON. |
| 3 | Medio | Un usuario sin permiso recibía 404 con ids que no existen y 403 con los que sí: permitía averiguar qué entregas, sesiones o actividades existen. | El rol se revisa antes de resolver el modelo de la ruta (`prependToPriorityList`). Test: todas las rutas × todos los roles dan 403 aunque el id sea inventado. |
| 4 | Medio | «Recordarme» duraba 400 días: en celulares compartidos la sesión seguiría abierta mucho después del evento. | `auth.guards.web.remember = 4320` (3 días, igual que la sesión). |
| 5 | Medio | En producción se atendía cualquier encabezado `Host`: se podían fabricar enlaces firmados y QR apuntando a otro sitio. | `trustHosts` en producción: solo el dominio de `APP_URL` (+ `TRUSTED_HOSTS`). Fuerza https en los enlaces generados. |
| 6 | Bajo | Escanear QR, ver y pagar cobros y la pantalla pública del QR no tenían ningún tope de uso. | Límites **por persona** (nunca por IP: cientos de participantes comparten la red del evento): 40/min para escanear y pagar, 30/min por sesión para el QR público. Test: un compañero en la misma red no se ve afectado. |
| 7 | Info | No había forma de comprobar que el servidor de producción estuviera bien configurado. | `php artisan jota:verificar-produccion`: revisa `APP_DEBUG`, https, cookie segura, cola, `public/hot`, fotos fuera de `public/`, migraciones, cuentas con el PIN de prueba, admin activo, etc. Plantilla `.env.production.example`. |

## Revisado y correcto (con test)

- **Policies y alcance por unidad**: el dirigente solo actúa sobre su unidad (cola, revisión, sesiones, cumplidas,
  penalizaciones, reservas); el admin sobre todas; el participante solo sobre lo suyo. Matriz completa de rutas por rol.
- **Panel `/admin`**: solo administradores activos. Los demás roles no lo ven.
- **Fotos**: disco privado, validadas por contenido real, re-codificadas sin EXIF, servidas solo por una ruta con
  Policy y solo ya procesadas. No hay nada en `public/`.
- **Ranking anónimo**: sin nombres ni CI en el HTML, en el estado de Livewire ni en el JSON.
- **QR de asistencia**: HMAC con secreto por sesión, ventana de 60 s con 10 s de gracia, comparación en tiempo
  constante; un token de una sesión no sirve en otra ni con un carácter cambiado.
- **QR de cobro**: token de 256 bits, un solo uso, vence a los 2 minutos, se paga por POST con CSRF; no aparece al
  serializar el cobro.
- **Idempotencia y concurrencia**: restricciones únicas en base de datos y pruebas con procesos simultáneos reales.
- **XSS**: Blade escapa todo; nombres y textos con HTML se muestran como texto en todas las pantallas. Las únicas
  salidas sin escapar son el SVG del QR (generado por el servidor) y el énfasis del copy (lo decide el texto, no un dato).
- **Inyección SQL**: todo con consultas parametrizadas; las búsquedas escapan `%` y `_`. Test con comillas y `DROP TABLE`.
- **Sesión**: cookie `HttpOnly` y `SameSite=Lax`; el id se renueva al ingresar y al salir; `Secure` en producción.
- **Datos secretos**: `pin_hash`, `secreto` de la sesión y `token` del cobro no salen al convertir a JSON.
- **Asignación masiva**: los saldos, el PIN y la activación no son asignables en masa.
- **Propiedades de Livewire**: las que identifican registros están bloqueadas (`#[Locked]`); cambiarlas desde el
  navegador falla.
- **Dependencias**: `composer audit` y `npm audit` en 0.
- **Producción**: `/diseno` (pantallas de ejemplo) no se publica; el seeder de prueba se niega a correr.

## Riesgos aceptados (decisiones de producto)

1. **PIN de 4 dígitos sin límite de intentos** (legajo R3/D8): alguien podría probar los 10.000 PIN de un CI.
   Mitigación sin bloquear: la plataforma deja un **aviso en el registro** (`storage/logs`) cuando una cuenta
   acumula 40 PIN incorrectos en 15 minutos (10 para dirigente, tienda y admin). El responsable técnico debe mirarlo
   durante el evento. Las cuentas de admin, dirigente y tienda deberían usar contraseña larga
   (`jota:crear-admin` ya lo exige para el admin).
2. **El mensaje de error distingue «CI no cargado» de «PIN incorrecto»**: permite saber qué CI existen. Es a propósito
   (que los chicos sepan qué les pasa); los CI no son secretos dentro del grupo.
3. **Asistencia por enlace GET** (el QR abre una dirección): un sitio malicioso podría hacer que el celular de alguien
   registre su propia asistencia, pero necesita el código vigente de ese minuto, que ya implica estar viendo el QR.
4. **La CSP admite scripts en línea y `eval`**: Livewire, Alpine y Filament lo necesitan. Igual no deja cargar ni
   enviar nada fuera del propio sitio.
5. **Filament mantiene su límite de intentos propio** en el login del panel (solo administradores).

## Pendiente

- **strix contra staging** (necesita credenciales del modelo y Docker). Cuando haya staging:
  `strix -n -m standard -t https://STAGING --instruction-file docs/seguridad/instrucciones-strix.md`
  Solo contra el entorno propio de pruebas, nunca contra producción con el evento en curso.
- Encabezados del servidor en Plesk: `expose_php=Off`, TLS 1.2+, redirección de http a https (ver Fase 11).
