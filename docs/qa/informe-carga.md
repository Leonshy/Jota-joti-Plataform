# Informe de la prueba de carga — 16/10/2026, 3:58:40 a. m.

**Escenario:** 300 participantes + 3 dirigentes + 1 tienda conectados a la vez durante 240 s (sondeo de pantallas cada 30 s), contra el servidor incorporado de PHP con 24 procesos y MySQL local, en una sola Mac. Es **más lento que Apache/PHP-FPM de producción** (sin caché de código y con la base compitiendo por la misma máquina): los tiempos son una cota pesimista.

**Total:** 4162 peticiones (17.3 por segundo), 0 con error de servidor.

## Tiempos por tipo de petición

| Petición | N | Errores | 429 | p50 | p95 | p99 | máx |
|---|---:|---:|---:|---:|---:|---:|---:|
| sondeo ranking | 665 | 0 | 0 | 44 ms | 127 ms | 1548 ms | 1971 ms |
| sondeo misiones | 554 | 0 | 0 | 43 ms | 94 ms | 1660 ms | 2027 ms |
| sondeo agenda | 552 | 0 | 0 | 46 ms | 104 ms | 1515 ms | 1738 ms |
| sondeo inicio | 454 | 0 | 0 | 47 ms | 118 ms | 1567 ms | 1733 ms |
| ingresar (página) | 304 | 0 | 0 | 1049 ms | 1672 ms | 2016 ms | 2158 ms |
| ingresar (Livewire) | 304 | 0 | 0 | 1470 ms | 1995 ms | 2251 ms | 2403 ms |
| escanear QR (asistencia) | 214 | 0 | 0 | 52 ms | 89 ms | 113 ms | 123 ms |
| dirigente: cola | 171 | 0 | 0 | 40 ms | 970 ms | 1502 ms | 1933 ms |
| tienda: sondeo (cada 2 s) | 112 | 0 | 0 | 38 ms | 866 ms | 1282 ms | 1494 ms |
| pago: pagar | 103 | 0 | 0 | 37 ms | 56 ms | 64 ms | 76 ms |
| pago: ver cobro | 95 | 0 | 0 | 42 ms | 115 ms | 150 ms | 150 ms |
| página ranking | 89 | 0 | 0 | 1057 ms | 1738 ms | 2046 ms | 2046 ms |
| página misiones | 75 | 0 | 0 | 979 ms | 1713 ms | 2103 ms | 2103 ms |
| página agenda | 75 | 0 | 0 | 1179 ms | 1625 ms | 1822 ms | 1822 ms |
| página inicio | 61 | 0 | 0 | 1134 ms | 1758 ms | 1902 ms | 1902 ms |
| entrega: abrir | 57 | 0 | 0 | 34 ms | 62 ms | 67 ms | 67 ms |
| entrega: enviar | 57 | 0 | 0 | 42 ms | 60 ms | 81 ms | 81 ms |
| dirigente: abrir entrega | 57 | 0 | 0 | 32 ms | 40 ms | 59 ms | 59 ms |
| dirigente: aprobar | 57 | 0 | 0 | 47 ms | 61 ms | 81 ms | 81 ms |
| agenda: abrir | 24 | 0 | 0 | 42 ms | 68 ms | 207 ms | 207 ms |
| agenda: reservar | 24 | 0 | 0 | 50 ms | 85 ms | 90 ms | 90 ms |
| entrega: foto (iniciar) | 19 | 0 | 0 | 28 ms | 63 ms | 63 ms | 63 ms |
| entrega: foto (subir archivo) | 19 | 0 | 0 | 38 ms | 45 ms | 45 ms | 45 ms |
| entrega: foto (terminar) | 19 | 0 | 0 | 34 ms | 52 ms | 52 ms | 52 ms |
| tienda: página | 1 | 0 | 0 | 132 ms | 132 ms | 132 ms | 132 ms |

## Lo que hicieron los usuarios

Ingresos 304 (fallidos 0) · escaneos 214 (ya registrados 0, otros 0) · pagos 95 (ya pagados por doble toque 8, otros 0) · entregas 57 (con foto 19) · aprobadas 57 · reservas 24 (rechazadas 0).

La cola terminó de procesar las fotos 1 s después de terminar la prueba.

## Integridad después de la carga

| Verificación | Resultado | Detalle |
|---|:-:|---|
| Asistencias en la base = escaneos exitosos | ✅ | 214 vs 214 |
| Ninguna asistencia duplicada (usuario × actividad) | ✅ | 0 |
| Cobros pagados = pagos exitosos (los dobles toques no cobran dos veces) | ✅ | 95 vs 95 |
| Ningún cobro con más de un descuento | ✅ | 0 |
| Pagos en el registro = cobros pagados | ✅ | 95 vs 95 |
| Entregas en la base = entregas enviadas | ✅ | 57 vs 57 |
| Aprobaciones en la base = aprobaciones hechas | ✅ | 57 vs 57 |
| Todas las fotos quedaron procesadas | ✅ | 19/19 |
| Reservas en la base = reservas hechas | ✅ | 24 vs 24 |
| Ningún saldo de LisCoins negativo | ✅ | 0 |
| Sin trabajos fallidos en la cola | ✅ | 0 |
| jota:reconciliar sin diferencias | ✅ | Sin diferencias: los saldos coinciden con los movimientos. |
| Sin errores en el registro de la aplicación | ✅ | 0 error(es) |
| Sin respuestas 5xx / 419 / caídas de conexión | ✅ | 0 de 4162 |
