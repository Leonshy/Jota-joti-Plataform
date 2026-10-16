# UC-10 · Historial — estados



```mermaid
stateDiagram-v2
    [*] --> Cargando
    Cargando --> Lista: hay movimientos
    Cargando --> Vacio: ninguno
    Cargando --> Error: falla la red
    Lista --> CargandoMas: llega al final
    CargandoMas --> Lista: ok
    Error --> Cargando: Reintentar
```
