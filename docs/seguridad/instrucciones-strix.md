# Instrucciones para strix — plataforma JOTA-JOTI 2026 (solo STAGING propio)

Alcance: únicamente el dominio de staging indicado en `--target`. Prohibido atacar producción, otros dominios o
servicios de terceros. No hagas denegación de servicio ni pruebas de carga destructivas.

Datos de prueba de staging (cargados con el seeder, NO existen en producción):
- Participante: CI 4000001, PIN 1234 (Tropa, con patrulla). Más participantes: 4000002…4000043.
- Dirigentes: CI 4000045 (Tropa), 4000046 (Caminantes), 4000047 (Rovers), PIN 1234.
- Tienda: CI 4000048, PIN 1234. Administrador: CI 4000049, PIN 1234 (panel en /admin).

Reglas del producto que NO son hallazgos (decisiones tomadas): el ingreso no tiene límite de intentos ni bloqueo;
el PIN es de 4 dígitos; el mensaje de ingreso distingue «CI no cargado» de «PIN incorrecto».

Enfocate en:
1. Control de acceso entre roles (participante / dirigente / tienda / admin) y entre unidades: un dirigente de una
   unidad no debe ver ni actuar sobre otra. Probá IDOR en `/app/misiones/{id}`, `/dirigente/entregas/{id}`,
   `/dirigente/asistencia/{id}`, `/entregas/imagenes/{id}`, `/cobros/{token}`.
2. Fotos de entregas: solo deben verse con sesión y permiso; nunca desde una URL pública.
3. Ranking del participante: no debe filtrar nombres, CI ni cuál es su equipo.
4. QR de asistencia (`/asistencia/{id}/registrar/{token}`) y de cobro (`/cobros/{token}`): reutilización, adivinación,
   doble pago, doble asistencia, condiciones de carrera.
5. Livewire: manipulación de propiedades y de llamadas en `/livewire/update`.
6. XSS almacenado (nombres, textos de entregas, títulos), inyección SQL en las búsquedas, CSRF en los POST.
7. Encabezados de seguridad, cookies, redirecciones abiertas, carga de archivos (tipo real, tamaño, EXIF).
