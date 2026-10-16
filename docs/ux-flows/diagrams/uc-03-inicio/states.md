# UC-03 · Inicio — estados



```mermaid
stateDiagram-v2
    [*] --> Cargando
    Cargando --> ConDatos: ok
    Cargando --> SinConexion: falla la red
    Cargando --> SesionVencida: 401
    ConDatos --> VacioDeNovedades: sin misiones ni reservas
    ConDatos --> ConDatos: se actualiza solo (polling)
    SinConexion --> Cargando: Reintentar
    SesionVencida --> [*]: Va a Ingresar
```
