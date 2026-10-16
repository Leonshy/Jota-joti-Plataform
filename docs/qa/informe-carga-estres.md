# Informe de la prueba de carga — 16/10/2026, 4:01:20 a. m.

**Escenario:** 300 participantes + 3 dirigentes + 1 tienda conectados a la vez durante 149 s (sondeo de pantallas cada 3 s — ESTRÉS: más frecuente que en la app real), contra el servidor incorporado de PHP con 24 procesos y MySQL local, en una sola Mac. Es **más lento que Apache/PHP-FPM de producción** (sin caché de código y con la base compitiendo por la misma máquina): los tiempos son una cota pesimista.

**Total:** 13844 peticiones (92.8 por segundo), 17 con error de servidor.

## Tiempos por tipo de petición

| Petición | N | Errores | 429 | p50 | p95 | p99 | máx |
|---|---:|---:|---:|---:|---:|---:|---:|
| sondeo misiones | 3566 | 4 | 0 | 42 ms | 584 ms | 2628 ms | 7787 ms |
| sondeo inicio | 3145 | 4 | 0 | 46 ms | 293 ms | 2580 ms | 7787 ms |
| sondeo agenda | 3057 | 2 | 0 | 45 ms | 1038 ms | 2441 ms | 7783 ms |
| sondeo ranking | 2311 | 1 | 0 | 43 ms | 1033 ms | 2534 ms | 7784 ms |
| ingresar (página) | 304 | 3 | 0 | 1045 ms | 2509 ms | 7135 ms | 7784 ms |
| ingresar (Livewire) | 301 | 1 | 0 | 1442 ms | 2958 ms | 4813 ms | 7785 ms |
| escanear QR (asistencia) | 216 | 0 | 0 | 51 ms | 161 ms | 220 ms | 291 ms |
| dirigente: cola | 103 | 0 | 0 | 48 ms | 1573 ms | 2671 ms | 2772 ms |
| página misiones | 87 | 0 | 0 | 1282 ms | 2518 ms | 2894 ms | 2894 ms |
| página inicio | 79 | 2 | 0 | 1317 ms | 3070 ms | 7788 ms | 7788 ms |
| página agenda | 74 | 0 | 0 | 1009 ms | 2668 ms | 6069 ms | 6069 ms |
| tienda: sondeo (cada 2 s) | 66 | 0 | 0 | 43 ms | 1054 ms | 5066 ms | 5066 ms |
| pago: pagar | 66 | 0 | 0 | 38 ms | 80 ms | 128 ms | 128 ms |
| entrega: abrir | 61 | 0 | 0 | 36 ms | 112 ms | 241 ms | 241 ms |
| entrega: enviar | 61 | 0 | 0 | 60 ms | 153 ms | 276 ms | 276 ms |
| dirigente: abrir entrega | 60 | 0 | 0 | 40 ms | 131 ms | 315 ms | 315 ms |
| dirigente: aprobar | 60 | 0 | 0 | 55 ms | 199 ms | 226 ms | 226 ms |
| pago: ver cobro | 60 | 0 | 0 | 35 ms | 111 ms | 211 ms | 211 ms |
| página ranking | 56 | 0 | 0 | 1278 ms | 2639 ms | 3228 ms | 3228 ms |
| agenda: abrir | 25 | 0 | 0 | 37 ms | 170 ms | 253 ms | 253 ms |
| agenda: reservar | 25 | 0 | 0 | 62 ms | 186 ms | 210 ms | 210 ms |
| entrega: foto (iniciar) | 20 | 0 | 0 | 36 ms | 78 ms | 78 ms | 78 ms |
| entrega: foto (subir archivo) | 20 | 0 | 0 | 52 ms | 1191 ms | 1191 ms | 1191 ms |
| entrega: foto (terminar) | 20 | 0 | 0 | 44 ms | 106 ms | 106 ms | 106 ms |
| tienda: página | 1 | 0 | 0 | 394 ms | 394 ms | 394 ms | 394 ms |

## Lo que hicieron los usuarios

Ingresos 300 (fallidos 4) · escaneos 216 (ya registrados 0, otros 0) · pagos 60 (ya pagados por doble toque 6, otros 0) · entregas 61 (con foto 20) · aprobadas 60 · reservas 25 (rechazadas 0).

La cola terminó de procesar las fotos 0 s después de terminar la prueba.

## Integridad después de la carga

| Verificación | Resultado | Detalle |
|---|:-:|---|
| Asistencias en la base = escaneos exitosos | ✅ | 216 vs 216 |
| Ninguna asistencia duplicada (usuario × actividad) | ✅ | 0 |
| Cobros pagados = pagos exitosos (los dobles toques no cobran dos veces) | ✅ | 60 vs 60 |
| Ningún cobro con más de un descuento | ✅ | 0 |
| Pagos en el registro = cobros pagados | ✅ | 60 vs 60 |
| Entregas en la base = entregas enviadas | ✅ | 61 vs 61 |
| Aprobaciones en la base = aprobaciones hechas | ✅ | 60 vs 60 |
| Todas las fotos quedaron procesadas | ✅ | 20/20 |
| Reservas en la base = reservas hechas | ✅ | 25 vs 25 |
| Ningún saldo de LisCoins negativo | ✅ | 0 |
| Sin trabajos fallidos en la cola | ✅ | 0 |
| jota:reconciliar sin diferencias | ✅ | Sin diferencias: los saldos coinciden con los movimientos. |
| Sin errores en el registro de la aplicación | ✅ | 0 error(es) |
| Sin respuestas 5xx / 419 / caídas de conexión | ❌ | 17 de 13844 |
