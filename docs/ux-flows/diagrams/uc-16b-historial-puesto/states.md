# UC-16b · Historial del puesto — estados



```mermaid
stateDiagram-v2
    [*] --> Cargando
    Cargando --> Lista: hay cobros
    Cargando --> Vacio: ninguno
    Cargando --> Error: falla la red
    Lista --> Lista: se actualiza al volver
    Error --> Cargando: Reintentar
```
