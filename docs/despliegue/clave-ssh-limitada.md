# Clave SSH limitada (para que Claude despliegue y consulte sin contraseña)

Mismo criterio que en Cateura: una clave **exclusiva**, sin passphrase, que el servidor ata a un script fijo. Sin importar lo que
pida el cliente, el servidor ejecuta siempre `~/jota-ssh-comandos.sh`; nunca abre una consola libre.

## Qué puede hacer esa clave

| Comando | Qué hace |
|---|---|
| `deploy` | `deploy-jota.sh`: pull, composer, migraciones, cachés y verificador |
| `verificar` | `jota:verificar-produccion` |
| `respaldos` | `backup:list` |
| `consultar <CI>` | Solo lectura: datos, saldos y cantidad de registros de una persona |
| `simular-eliminacion <CI>` | Solo simulacro de `jota:eliminar-usuario-de-prueba`: qué se borraría y qué saldos se corregirían. No cambia nada |
| `actividad <palabra> [CI]` | Diagnóstico de un taller o estación: modalidad, unidades, turnos, cupos y reservas. Con un CI, prueba qué pasaría si esa persona intentara apuntarse (la prueba se deshace: no guarda nada) |
| `cola` | Solo lectura: trabajos pendientes y fallidos de la cola de fotos, con el motivo del fallo |
| `subir-build` | Recibe `build/` como tar por stdin (máx. 20 MB, solo rutas `build/…`, escribe solo dentro de `public/`) |

**No puede:** abrir una consola, leer el `.env` ni archivos, eliminar usuarios (solo simularlo), tocar saldos ni correr `artisan` libre. Eso sigue siendo
de una persona con su propia sesión.

## Instalación (una sola vez)
1. En el Mac, la clave `~/.ssh/jota_plesk` (ed25519, sin passphrase).
2. `bash ~/Desktop/instalar-clave-jota.sh` copia `deploy/ssh-comandos.sh` a `~/jota-ssh-comandos.sh` (fuera de la carpeta del sitio, no
   se actualiza con `git pull` a propósito) y agrega a `~/.ssh/authorized_keys`:
   ```
   command="/var/www/vhosts/naranja.com.py/jota-ssh-comandos.sh",restrict ssh-ed25519 AAAA… claude-jota-joti
   ```
   `restrict` apaga también el pty, los túneles y el reenvío del agente.

## Uso
```bash
ssh -i ~/.ssh/jota_plesk naranjacom@64.176.19.59 verificar
ssh -i ~/.ssh/jota_plesk naranjacom@64.176.19.59 consultar 12345678
# IMPORTANTE: subir el build ANTES de «deploy», y si falla, no seguir (el código nuevo necesita los scripts nuevos).
# COPYFILE_DISABLE=1 evita que el tar de macOS agregue metadatos ocultos que el servidor rechaza.
(cd public && COPYFILE_DISABLE=1 tar -cf - build) | ssh -i ~/.ssh/jota_plesk naranjacom@64.176.19.59 subir-build
ssh -i ~/.ssh/jota_plesk naranjacom@64.176.19.59 deploy
```
Cualquier otra cosa devuelve «Comando no permitido».

## Cambiar la lista de comandos
Se edita `deploy/ssh-comandos.sh` en el repo y se vuelve a copiar al servidor (`scp deploy/ssh-comandos.sh naranjacom@…:jota-ssh-comandos.sh`).
Es intencional que no se actualice sola con `git pull`: así un cambio en el repo no amplía el alcance de la clave.

## Revocar
Borrar la línea `claude-jota-joti` de `~/.ssh/authorized_keys` en el servidor (o `rm ~/jota-ssh-comandos.sh`). Con la contraseña de SSH
sigue entrando una persona.
