# Carga de participantes (CSV)

Antes de importar, en `/admin` hay que crear a mano los **grupos** y las **unidades** (nombre exacto). Las **patrullas** se crean solas desde el CSV.

Plantilla: [plantilla-participantes.csv](plantilla-participantes.csv). Se abre con Excel o Google Sheets y se guarda como **CSV UTF-8**.

| Columna | Obligatoria | Notas |
|---|---|---|
| `ci` | sí | Solo números, 4 a 12 dígitos. Los puntos y guiones se ignoran. No se repite. |
| `nombre` | sí | Nombre y apellido. |
| `grupo` | sí (participantes) | Igual que el grupo ya creado en el panel. |
| `unidad` | sí | Igual que la unidad ya creada. |
| `patrulla` | según unidad | **Tropa y Caminantes: obligatoria.** Rovers: dejarla vacía (el equipo es toda la unidad). |
| `rol` | no | `beneficiario` (por defecto), `dirigente` o `tienda`. |

- El orden de las columnas no importa. Se aceptan variantes como `cedula`, `nombre y apellido`, `rama` o `equipo`.
- Máximo 2 MB y 5.000 filas.
- Es **todo o nada**: si una línea tiene error no se importa ninguna, y el reporte dice qué línea y por qué.
- En el panel, primero «Validar sin guardar»; si no hay errores, «Importar».
- Nadie tiene PIN: cada persona lo crea con su CI en el primer ingreso (`/activar`).
- Borrá las filas de ejemplo antes de importar.
