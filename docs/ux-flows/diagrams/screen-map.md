# Mapa general de pantallas

Todas las pantallas móviles y cómo se llega a cada una. El admin trabaja en el panel Filament (no se detalla). Los atajos de la barra inferior de cada rol se describen en `UX-FLOWS.md`.

```mermaid
graph TD
    Entrada((Abre la app)) --> Sesion{"¿Sesión abierta?"}
    Sesion -->|"No"| Ingresar["Ingresar"]
    Ingresar -->|"Primera vez"| Activar["Activar cuenta"]
    Activar --> Rol
    Ingresar -->|"CI + PIN correctos"| Rol{"¿Qué rol tiene?"}
    Sesion -->|"Sí"| Rol

    subgraph Participante["Participante · /app"]
        Inicio["Inicio"]
        Misiones["Misiones"]
        Mision["Detalle de misión"]
        Entregar["Entregar"]
        Escanear["Escanear"]
        Actividades["Actividades"]
        Turnos["Turnos y reserva"]
        Ranking["Ranking"]
        Historial["Historial"]
        ConfPago["Confirmar pago"]
        ResAsist["Resultado de asistencia"]
    end

    subgraph Dirigente["Dirigente · /dirigente"]
        Cola["Cola de validación"]
        Revisar["Revisar entrega"]
        Asistencia["Sesiones de asistencia"]
        QRPantalla["QR en pantalla completa"]
        Cumplimientos["Cumplimientos por equipo"]
        Penalizaciones["Penalizaciones"]
    end

    subgraph Tienda["Tienda · /tienda"]
        Cobrar["Cobrar"]
        QRCobro["QR de cobro"]
        Devolver["Devolver"]
        HistTienda["Historial del puesto"]
    end

    Rol -->|"Participante"| Inicio
    Rol -->|"Dirigente"| Cola
    Rol -->|"Tienda"| Cobrar
    Rol -->|"Admin"| Panel[["Panel /admin (Filament)"]]

    Inicio --> Misiones --> Mision --> Entregar --> Mision
    Inicio --> Escanear
    Escanear -->|"QR de asistencia"| ResAsist
    Escanear -->|"QR de cobro"| ConfPago
    Inicio --> Actividades --> Turnos
    Inicio --> Ranking
    Inicio --> Historial

    Cola --> Revisar --> Cola
    Cola --> Asistencia --> QRPantalla
    Cola --> Cumplimientos
    Cola --> Penalizaciones

    Cobrar --> QRCobro --> Cobrar
    Cobrar --> Devolver
    Cobrar --> HistTienda

    class Ingresar,Activar,Inicio,Misiones,Mision,Entregar,Escanear,Actividades,Turnos,Ranking,Historial,ConfPago,ResAsist,Cola,Revisar,Asistencia,QRPantalla,Cumplimientos,Penalizaciones,Cobrar,QRCobro,Devolver,HistTienda screen
    class Sesion,Rol decision

    classDef screen fill:#e8e8e8,stroke:#999,stroke-width:2px
    classDef decision fill:#fff3cd,stroke:#ffc107,stroke-width:2px
    classDef action fill:#d4edda,stroke:#28a745,stroke-width:1px
    classDef error fill:#f8d7da,stroke:#dc3545,stroke-width:1px
```
