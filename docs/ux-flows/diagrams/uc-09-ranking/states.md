# UC-09 · Ranking — estados



```mermaid
stateDiagram-v2
    [*] --> Cargando
    Cargando --> Tabla: hay datos
    Cargando --> Vacio: nadie tiene puntaje
    Cargando --> SinConexion: falla la red
    Tabla --> Tabla: polling cada 30 s
    Tabla --> SinConexion: se corta la red
    SinConexion --> Tabla: vuelve la red
    Vacio --> Tabla: aparece el primer puntaje
```
